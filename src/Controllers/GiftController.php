<?php

namespace Hanoivip\Gift\Controllers;

use Hanoivip\Gift\Requests\GeneratePersonalGift;
use Hanoivip\Gift\Requests\UseGift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
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
            $packages[$p->code] = $packages[$p->name];
        return $packages;
    }
    
    public function personalGenerateUI(Request $request)
    {
        $uid = Auth::guard('token')->user()['id'];
        $packages = $this->getUserPackages($uid);
        
        if ($request->ajax())
            return $packages;
        else
            return view('generate-personal-code', ['packages' => $packages]);
    }
    
    public function useUI(Request $request)
    {
        if ($request->ajax())
            return [];
        else
            return view('use-code');
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
            $packages = $this->getUserPackages($uid);
            return view('generate-personal-code', 
                ['code' => $code, 'packages' => $packages, 'message' => $message, 'error_message' => $error_message]);
        }
    }
    
    public function use(UseGift $request)
    {
        $uid = Auth::guard('token')->user()['id'];
        $code = $request->input('code');
        $message = '';
        $error_message = '';
        try 
        {
            $result = $this->gift->use($uid, $code);
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
            return view('use-code', ['message' => $message, 'error_message' => $error_message]);
    }
    
    public function statistics(Request $request)
    {
        
    }
}