<?php

namespace Hanoivip\Gift\Services;

use Carbon\Carbon;
use Hanoivip\Gift\GiftCode;
use Hanoivip\Gift\GiftPackage;
use Hanoivip\PaymentClient\BalanceUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GiftService
{
    const DEFAULT_LENGTH = 8;
    
    protected $balances;
    
    public function __construct(BalanceUtil $balances)
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
     * @param number $targetUid Allowed user to use this code
     * 
     * @return array|string Array of code generated
     */
    public function generate($package, $count = 10, $genUid = 0, $targetUid = 0)
    {
        $template = GiftPackage::find($package);
        if (empty($template))
            throw new Exception('Gift gift package template not exists.');
        if ($genUid > 0 && $targetUid > 0 && $genUid == $targetUid)
            throw new Exception('Gift can not generate for your self');
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
            $count = GiftCode::where('code', $package)->count();
            if ($count >= $limit)
            {
                Log::error('Gift gift package ' . $package . ' has limit ');
                return __('gift.package.out_of_stock');
            }
        }
        // Generate
        $length = config('gift.length', DEFAULT_LENGTH);
        $codes = [];
        for ($i=1; $i<=$count; ++$i)
        {
            $code = $this->generateCode($template->prefix, $length);
            $codes[] = $code;
            // Save to database
            GiftCode::create([
                'code' => $code, 
                'pack' => $package,
                'generate_uid' => $genUid,
                'target_uid' => $targetUid,
            ]);
        }
        
        return $codes;
    }
    
    protected function generateCode($prefix, $length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randstring = empty($prefix) ? '' : $prefix;
        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, strlen($characters))];
        }
        return $randstring;
        
    }
    
    public function getUsageNumber($pack)
    {
        
    }
    
    /**
     * Người chơi sử dụng 1 code nhất định
     * 
     * Điều khiện đầu:
     * + Người chơi đã lấy được 1 mã
     * 
     * Kiểm tra:
     * + Mã tồn tại
     * + Còn thời hạn sử dụng
     * + Chưa có người dùng (người khác hoặc tự mình)
     * + Được phép sử dụng 
     * 
     * Thực hiện
     * + Đánh dấu sử dụng
     * + Phát phần thưởng dựa trên cấu hình
     * 
     * 
     * 
     * @param number $uid
     * @param string $code
     * @return boolean|string True if success, String reason when fail
     */
    public function use($uid, $code)
    {
        $giftCode = GiftCode::where('code', $code)->get();
        if ($giftCode->isEmpty())
            throw new Exception('Gift user ' . $uid . ' submited non-exists code ' . $code);
        $package = GiftPackage::where('pack_code', $giftCode->pack)->get();
        if ($package->isEmpty())
            throw new Exception('Gift gift code template does not exists ' . $giftCode->pack);
        $now = Carbon::now();
        $end_time = $package->end_time;
        if (!empty($end_time))
        {
            $endTime = new Carbon($end_time);
            if ($now >= $endTime)
                return __('gift.usage.time_out');
        }
        $usageUid = $giftCode->usage_uid;
        if (!empty($usageUid))
        {
            if ($usageUid == $uid)
                return __('gift.usage.once_only');
            else
                return __('gift.usage.other_already_used');
        }
        $targetUid = $giftCode->target_uid;
        if (!empty($targetUid))
        {
            if ($targetUid != $uid)
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
        $giftCode->usage_time = $now->timestamp;
        $giftCode->save();
        
        return true;
    }
    
    protected function sendRewards($uid, $rewards, $reason = "")
    {
        foreach ($rewards as $reward)
        {
            $type = $reward['type'];
            $id = $reward['id'];
            $count = $rewards['count'];
            switch ($type)
            {
                case RewardTypes::BALANCE:
                    $this->balances->add($uid, $count, $reason, $id);
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
        return GiftPackage::where('allow_users', true)->all();
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
            $package = GiftPackage::all();
            return $package->toArray();
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
}