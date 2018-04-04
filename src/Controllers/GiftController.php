<?php

namespace Hanoivip\Gift\Controllers;

use Hanoivip\Gift\Requests\GeneratePersonalGift;
use Hanoivip\Gift\Requests\UseGift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Hanoivip\Gift\Services\GiftService;

class GiftController extends Controller
{
    protected $gift;
    
    public function __construct(GiftService $gift)
    {
        $this->gift = $gift;    
    }
    
    // TODO: find advance way
    protected function getUserPackages($uid)
    {
        $packages = [];
        $all = $this->gift->getUserPackages($uid);
        foreach ($all as $p)
            $packages[$p->pack_code] = $p->name;
        return $packages;
    }
    
    public function personalGenerateUI(Request $request)
    {
        $uid = Auth::guard('token')->user()['id'];
        $packages = $this->getUserPackages($uid);
        
        if ($request->ajax())
            return $packages;
        else
            return view('hanoivip::generate-personal-code', ['packages' => $packages]);
    }
    
    public function useUI(Request $request)
    {
        if ($request->ajax())
            return [];
        else
            return view('hanoivip::use-code');
    }
    
    public function generate(GeneratePersonalGift $request)
    {
        $uid = Auth::guard('token')->user()['id'];
        $package = $request->input('package');
        $target = $request->input('target');
        $code = '';
        $message = '';
        $error_message = '';
        try 
        {
            $result = $this->gift->generate($package, 1, $uid, $target);  
            if (gettype($result) == "string")
                $error_message = $result;
            else if (gettype($result) == "array")
            {
                if (empty($result))
                    $error_message = __('gift.generate.fail');
                else
                {
                    $code = $result[0];
                    $message = __('gift.generate.success');
                }
            }
            else
                throw new Exception('Generate code unknown result type.');
        }
        catch (Exception $ex)
        {
            Log::error('GiftController gen gift code exception. Msg:' . $ex->getMessage());
            $error_message = __('gift.generate.exception');
        }
        if ($request->ajax())
            return ['code' => $code, 'message' => $message, 'error_message' => $error_message];
        else 
        {
            return view('hanoivip::user-generate-result', 
                ['code' => $code, 'message' => $message, 'error_message' => $error_message]);
        }
    }
    
    public function use(UseGift $request)
    {
        $user = Auth::guard('token')->user();
        $code = $request->input('code');
        $message = '';
        $error_message = '';
        try 
        {
            $result = $this->gift->use($user, $code);
            if (gettype($result) == "string")
            {
                $error_message = $result;
            }
            else 
            {
                if ($result)
                    $message = __('gift.use.success');
                else
                    $error_message = __('gift.use.fail');
            }
        }
        catch (Exception $ex)
        {
            Log::error('GiftController use gift code exception. Msg:' . $ex->getMessage());
            $error_message = __('gift.use.exception');
        }
        if ($request->ajax())
            return ['message' => $message, 'error_message' => $error_message];
        else
            return view('hanoivip::use-code', ['message' => $message, 'error_message' => $error_message]);
    }
    
    public function statistics(Request $request)
    {
        
    }
    
    public function history(Request $request)
    {
        $uid = Auth::guard('token')->user()['id'];
        $histories = [];
        $error_message = '';
        try 
        {
            $histories = $this->gift->history($uid);
        }
        catch (Exception $ex)
        {
            Log::error('Gift get user generation history error. Msg:' . $ex->getMessage());
            $error_message = __('gift.history.exception');
        }
        if ($request->ajax())
            return [ 'histories' => $histories, 'error_message' => $error_message ];
        else
            return view('hanoivip::user-generate-history', 
                [ 'histories' => $histories, 'error_message' => $error_message ]);
    }
}