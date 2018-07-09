<?php

namespace Hanoivip\Gift\Services;

use Carbon\Carbon;
use Hanoivip\Gift\GiftCode;
use Hanoivip\Gift\GiftPackage;
use Hanoivip\PaymentClient\BalanceUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Auth\Authenticatable;
use Hanoivip\Events\Gift\TicketReceived;

class GiftService
{
    const DEFAULT_LENGTH = 8;
    
    protected $balances;
    
    public function __construct(
        BalanceUtil $balances)
    {
        $this->balances = $balances;
    }
    
    /**
     * Sinh mã. 
     * 
     * Tác nhân:
     * + Other subsystem
     * + Admin
     * + User
     * 
     * @param string $package Package code
     * @param number $count Number of code to generate
     * @param number $genUid Sender identification, Default admin ID = 0
     * @param string $target Allowed user to use this code
     * 
     * @return array|string Array of code generated
     */
    public function generate($package, $count = 10, $genUid = 0, $target = null)
    {
        $template = GiftPackage::where('pack_code', $package)->first();
        if (empty($template))
            throw new Exception('Gift gift package template not exists.');
        //if ($genUid > 0 && $targetUid > 0 && $genUid == $targetUid)
        //    throw new Exception('Gift can not generate for your self');
        $now = Carbon::now();
        $end_time = $template->end_time;
        if (!empty($end_time))
        {
            $endTime = new Carbon($end_time);
            if ($now >= $endTime)
            {
                Log::error('Gift gift package ' . $package . ' timeout. Now allow to generate more');
                return __('gift.package.timeout');
            }
        }
        $limit = $template->limit;
        if (!empty($limit))
        {
            $count = GiftCode::where('pack', $package)->count();
            if ($count >= $limit)
            {
                Log::error('Gift gift package ' . $package . ' has limit ');
                return __('gift.package.out_of_stock');
            }
        }
        // Generate
        $length = config('gift.length', self::DEFAULT_LENGTH);
        $codes = [];
        for ($i=1; $i<=$count; ++$i)
        {
            $code = $this->generateCode($template->prefix, $length);
            $codes[] = $code;
            Log::debug('....' . $code);
            // Save to database
            GiftCode::create([
                'gift_code' => $code, 
                'pack' => $package,
                'generate_uid' => $genUid,
                'target' => $target,
            ]);
        }
        
        return $codes;
    }
    
    protected function generateCode($prefix, $length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randstring = empty($prefix) ? '' : $prefix;
        for ($i = 0; $i < $length; $i++) 
        {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randstring;
        
    }
    
    
    /**
     * Người chơi sử dụng 1 code nhất định
     * 
     * Điều khiện đầu:
     * + Người chơi đã lấy được 1 mã
     * 
     * Kiểm tra:
     * + Chưa từng sử dụng loại code này
     * + Mã tồn tại
     * + Còn thời hạn sử dụng
     * + Chưa có người dùng (người khác hoặc tự mình)
     * + Được phép sử dụng 
     * 
     * Thực hiện
     * + Đánh dấu sử dụng
     * + Phát phần thưởng dựa trên cấu hình
     * 
     * TODO: điều kiện sử dụng code
     * 1. Đăng ký trước ngày...
     * 2. Đã là vip mấy..
     * 3. Đăng nhập được bao nhiêu lần ..
     * 
     * @param Authenticatable $user
     * @param string $code
     * @return boolean|string True if success, String reason when fail
     */
    public function use($user, $code)
    {
        $uid = $user->getAuthIdentifier();
        $giftCode = GiftCode::where('gift_code', $code)->first();
        if (empty($giftCode))
            return __('gift.usage.not-exists');
        $package = GiftPackage::where('pack_code', $giftCode->pack)->first();
        if (empty($package))
            throw new Exception('Gift gift code template does not exists ' . $giftCode->pack);
        $now = Carbon::now();
        $end_time = $package->end_time;
        if (!empty($end_time))
        {
            $endTime = new Carbon($end_time);
            if ($now >= $endTime)
                return __('gift.usage.time_out');
        }
        // Kiểm tra đã bị sử dụng chưa
        $usageUid = $giftCode->usage_uid;
        if (!empty($usageUid))
        {
            if ($usageUid == $uid)
                return __('gift.usage.already_used');
            else
                return __('gift.usage.other_already_used');
        }
        // Kiểm tra đã dùng loại code này chưa
        $gifts = GiftCode::where('pack', $package->pack_code)
        ->where('usage_uid', $uid)
        ->get();
        if (!$gifts->isEmpty())
        {
            return __('gift.usage.once_only');
        }
        $target = $giftCode->target;
        if (!empty($target))
        {
            if ($user->getAuthIdentifierName() != $target)// &&
                //TODO: change $user from array => Authenticatable, can not access email
                //$user['email'] != $target)
                return __('gift.usage.not_yours');
        }
        // Check limit
        if ($package->limit > 0)
        {
            $count = GiftCode::where('pack', $package->pack_code)->count();
            if ($count >= $package->limit)
                return __('gift.usage.limited');
        }
        // Rewarding user
        $rewards = json_decode($package->rewards, true);
        if (!empty($rewards))
            $this->sendRewards($uid, $rewards, 'CodeUsage:' . $code);
        else
            Log::error('Gift package ' . $package->pack_code . ' has no rewards');
        // Mark used
        $giftCode->usage_uid = $uid;
        $giftCode->use_time = $now;
        $giftCode->save();
        return true;
    }
    
    protected function sendRewards($uid, $rewards, $reason = "")
    {
        foreach ($rewards as $reward)
        {
            $type = $reward['type'];
            $id = $reward['id'];
            $count = $reward['count'];
            switch ($type)
            {
                case RewardTypes::BALANCE:
                    $this->balances->add($uid, $count, $reason, $id);
                    break;
                case RewardTypes::TICKET:
                    event(new TicketReceived($uid, $id, $count));
                    break;
                default:
                    Log::debug('Gift reward type ' . $type . ' doest not supported now!');
                    break;
            }
        }
    }
    
    /**
     * Lấy danh sách các gói dành riêng cho người dùng.
     * 
     * Người dùng có thể dùng các gói này để sinh mã cho mình để đi trao cho người khác.
     * 
     * 
     * @param number $uid
     * @return GiftPackage[]
     */
    public function getUserPackages($uid)
    {
        return GiftPackage::where('allow_users', true)->get();
    }
    
    /**
     * Lấy về danh sách các gói hiện tại
     * 
     * @return array Array of GiftPackage
     */
    public function packges($code = null)
    {
        if (!empty($code))
        {
            $package = GiftPackage::where('pack_code', $code)->get();
            if (!$package->isEmpty())
                return $package->first();
            else 
                return null;
        }
        else
        {
            return GiftPackage::all();
        }
    }
    
    /**
     * Tạo mới package từ dữ liệu người dùng khai báo.
     * Dữ liệu đã được kiểm tra trước.
     * 
     * 
     * @param array $package
     */
    public function createPackage($package)
    {
        DB::table('gift_packages')->insert($package);
    }
    
    /**
     * 
     * @param string $code
     */
    public function removePackage($code)
    {
        $record = GiftPackage::where('pack_code', $code);
        if (!empty($record))
            $record->delete();
    }
    
    /**
     * 
     * @param array $package
     */
    public function updatePackage($package)
    {
        $record = GiftPackage::where('pack_code', $package['code'])->first();
        $record->update($package);
    }
    
    /**
     * 
     * @param number $genUid
     * @return GiftCode[]
     */
    public function history($genUid)
    {
        $codes = GiftCode::where('generate_uid', $genUid)->get();
        return $codes->toArray();
    }
}