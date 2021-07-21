<?php

namespace Hanoivip\Gift\Services;

use Carbon\Carbon;
use Hanoivip\Game\Server;
use Hanoivip\GameContracts\Contracts\IGameOperator;
use Hanoivip\GateClient\Facades\BalanceFacade;
use Hanoivip\Gift\GiftCode;
use Hanoivip\Gift\GiftPackage;
use Hanoivip\Gift\UserGift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Auth\Authenticatable;
use Hanoivip\Events\Gift\TicketReceived;
use Hanoivip\Gift\MissionParamException;
use Hanoivip\Gift\ViewObjects\GiftUsageVO;
use Hanoivip\Gift\ViewObjects\GiftVO;
use Hanoivip\Gift\ViewObjects\GiftRewardVO;
use Hanoivip\GameContracts\ViewObjects\UserVO;
use Hanoivip\GameContracts\ViewObjects\ServerVO;

class GiftService
{
    const DEFAULT_LENGTH = 8;
    
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
                return __('hanoivip::gift.package.timeout');
            }
        }
        $limit = $template->limit;
        if (!empty($limit))
        {
            $count = GiftCode::where('pack', $package)->count();
            if ($count >= $limit)
            {
                Log::error('Gift gift package ' . $package . ' has limit');
                return __('hanoivip::gift.package.out_of_stock');
            }
        }
        if ($template->const_code)
        {
            Log::error('Gift gift package ' . $package . ' is const code');
            return __('hanoivip::gift.package.is_const');
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
     * - Trường hợp code hằng thì package tồn tại
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
     * @param Server $server
     * @param string $role
     * @return boolean|string True if success, String reason when fail
     */
    public function use($user, $code, $server = null, $role = null)
    {
        $uid = $user->getAuthIdentifier();
        $giftCode = GiftCode::where('gift_code', $code)->first();
        if (empty($giftCode))
        {
            // Có thể là const code
            $package = GiftPackage::where('pack_code', $code)->first();
            if (empty($package))
                return __('hanoivip::gift.usage.not-exists');
            else
            {
                if (empty($package->const_code))
                    return __('hanoivip::gift.usage.const_code_mis_conf');
                else
                {
                    // Const code found!
                    $giftCode = new GiftCode();
                    $giftCode->gift_code = $code . '@' . uniqid();
                    $giftCode->pack = $code;
                    $giftCode->save();
                }
            }
        }
        $package = GiftPackage::where('pack_code', $giftCode->pack)->first();
        if (empty($package))
            throw new Exception('Gift gift code template does not exists ' . $giftCode->pack);
        $now = Carbon::now();
        $end_time = $package->end_time;
        if (!empty($end_time))
        {
            $endTime = new Carbon($end_time);
            if ($now >= $endTime)
                return __('hanoivip::gift.usage.time_out');
        }
        // Kiểm tra đã bị sử dụng chưa
        $usageUid = $giftCode->usage_uid;
        if (!empty($usageUid))
        {
            if ($usageUid == $uid)
                return __('hanoivip::gift.usage.already_used');
            else
                return __('hanoivip::gift.usage.other_already_used');
        }
        // Kiểm tra đã dùng loại code này chưa
        $gifts = GiftCode::where('pack', $package->pack_code)
        ->where('usage_uid', $uid)
        ->get();
        if (!$gifts->isEmpty())
        {
            return __('hanoivip::gift.usage.once_only');
        }
        $target = $giftCode->target;
        if (!empty($target))
        {
            if ($user->getAuthIdentifierName() != $target)// &&
                //TODO: change $user from array => Authenticatable, can not access email
                //$user['email'] != $target)
                return __('hanoivip::gift.usage.not_yours');
        }
        // Check limit
        if ($package->limit > 0)
        {
            $count = GiftCode::where('pack', $package->pack_code)->count();
            if ($count >= $package->limit)
                return __('hanoivip::gift.usage.limited');
        }
        // Check server scope
        if (!empty($server) && !empty($package->server_include))
        {
            $includes = json_decode($package->server_include, true);
            if (!empty($includes) &&
                !in_array($server->ident, $includes))
                return __('hanoivip::gift.usage.server-not-allowed');
        }
        if (!empty($server) && !empty($package->server_exclude))
        {
            $excludes = json_decode($package->server_exclude, true);
            if (!empty($excludes) &&
                in_array($server->ident, $excludes))
                return __('hanoivip::gift.usage.server-is-prohibited');
        }
        // Rewarding user: TODO: make enqueue job here
        $rewards = json_decode($package->rewards, true);
        if (!empty($rewards))
        {
            $this->sendRewards($user, $rewards, 'CodeUsage:' . $code, $server, $role);
        }
        else
            Log::error('Gift package ' . $package->pack_code . ' has no rewards');
        // Mark used
        $giftCode->usage_uid = $uid;
        $giftCode->use_time = $now;
        $giftCode->save();
        return true;
    }
    
    protected function forwardCode($user, $server, $code, $params)
    {
        
    }
    
    // TODO: move to queue
    protected function sendRewards($user, $rewards, $reason = "", $server, $role)
    {
        foreach ($rewards as $reward)
        {
            $type = $reward['type'];
            switch ($type)
            {
                case RewardTypes::GAME_ITEMS:
                    if (empty($server)) 
                    {
                        throw new MissionParamException();
                    }
                    break;
            }
        }
        $uid = $user->getAuthIdentifier();
        foreach ($rewards as $reward)
        {
            $type = $reward['type'];
            $id = $reward['id'];
            $count = $reward['count'];
            switch ($type)
            {
                case RewardTypes::BALANCE:
                    BalanceFacade::add($uid, $count, $reason, $id);
                    break;
                case RewardTypes::TICKET:
                    event(new TicketReceived($uid, $id, $count));
                    break;
                case RewardTypes::GAME_ITEMS:
                    $operator = app()->make(IGameOperator::class);
                    if (!$operator->sendItem($server, $user, $id, $count, ['roleid' => $role]))
                        throw new Exception("Gift send game reward fail");
                    break;
                case RewardTypes::GAME_CODE:
                    /** @var IGameOperator $operator */
                    $operator = app()->make(IGameOperator::class);
                    $operator->useCode(new UserVO($uid, ""), $server, $id, ['roleid' => $role]);
                default:
                    Log::debug('Gift reward type ' . $type . ' doest not supported now!');
                    throw new Exception("Gift reward type not supported");
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
    
    /**
     * 
     * @param string $product
     * @return GiftPackage[]
     */
    private function packageByProduct($product)
    {
        if (empty($product))
        {
            $records = GiftPackage::all();
        }
        else
        {
            $records = GiftPackage::where('game_code', $product)->get();
        }
        return $records;
    }
    
    /**
     * Check for a product has any gift available
     * @param string $product String of product code
     * @return boolean
     */
    public function hasGift($product)
    {
        $records = $this->packageByProduct($product);
        return $records->isNotEmpty();
    }
    /**
     * Get all available gift of a product
     * @param string $product Product code
     * @return GiftVO[]
     */
    public function getGifts($product)
    {
        $records = $this->packageByProduct($product);
        $objs = [];
        if ($records->isNotEmpty())
        {
            foreach ($records as $record)
            {
                $obj = new GiftVO();
                $obj->code = $record->pack_code;
                $obj->title = $record->name;
                $obj->rewards = [];
                $rewards = json_decode($record->rewards);
                if (!empty($rewards))
                {
                    foreach ($rewards as $r)
                    {
                        $r_obj = new GiftRewardVO();
                        $r_obj->title = $r->title;
                        $r_obj->count = $r->count;
                        $r_obj->image = $r->image;
                        $obj->rewards[] = $r_obj;
                    }
                }
                $objs[] = $obj;
            }
        }
        return $objs;
    }
    /**
     * Get all package by product code?
     * @param string $code Product code
     * @return GiftPackage
     */
    public function getByCode($code)
    {
        
    }
    /**
     * Get detail code usage status
     * @param number $uid User ID
     * @param string $code Product code
     * @return array key: package code, value: boolean
     */
    public function getGiftUsage($uid, $product)
    {
        if (empty($product))
            $usages = UserGift::where('user_id', $uid)->get();
        else
            $usages = UserGift::where('user_id', $uid)->where('game_code', $product)->get();
        $objs = [];
        foreach ($usages as $usage)
        {
            $objs[$usage->pack] = $usage->used;
        }
        return $objs;
    }
}