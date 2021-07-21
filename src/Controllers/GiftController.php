<?php

namespace Hanoivip\Gift\Controllers;

use Hanoivip\Gift\Requests\GeneratePersonalGift;
use Hanoivip\Gift\Requests\UseGift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use Hanoivip\Gift\Services\GiftService;
use Hanoivip\Gift\Requests\BatchGenerateGift;
use Hanoivip\Game\Services\ServerService;
use Hanoivip\Gift\MissionParamException;
use Hanoivip\GameContracts\Contracts\IGameOperator;

class GiftController extends Controller
{
    protected $gift;
    
    protected $servers;
    
    protected $game;
    
    public function __construct(
        GiftService $gift,
        ServerService $servers, 
        IGameOperator $game)
    {
        $this->gift = $gift;
        $this->servers = $servers;
        $this->game = $game;
    }
    
    public function personalGenerateUI(Request $request)
    {
        $uid = Auth::user()->getAuthIdentifier();
        $packages = $this->gift->getUserPackages($uid);
        if ($request->ajax())
            return $packages;
        else
            return view('hanoivip::generate-personal-code', ['packages' => $packages]);
    }
    
    public function generateUI(Request $request)
    {
        $packages = $this->gift->packges();
        if ($request->ajax())
            return $packages;
        else
            return view('hanoivip::generate-code', ['packages' => $packages]);
    }
    
    public function useUI(Request $request)
    {
        if ($request->ajax())
            return [];
        else
            return view('hanoivip::use-code');
    }
    
    public function use2UI(Request $request)
    {
        $servers = $this->servers->getAll();
        $data = ['servers' => $servers ];
        if ($this->game->supportMultiChar() &&
            $servers->isNotEmpty())
        {
            $user = Auth::user();
            $chars = $this->game->characters($user, $servers->first());
        }
        if ($request->has('error_message'))
            $data['error_message'] = $request->get('error_message');
        if (isset($chars))
            $data['roles'] = $chars;
        if ($request->ajax())
            return $data;
        else
            return view('hanoivip::use-code', $data);
    }
    
    public function batchGenerate(BatchGenerateGift $request)
    {
        $uid = Auth::user()->getAuthIdentifier();
        $package = $request->input('package');
        $count = $request->input('count');
        $codes = [];
        $message = '';
        $error_message = '';
        try 
        {
            $result = $this->gift->generate($package, $count, $uid);
            if (gettype($result) == "string")
                $error_message = $result;
            else if (gettype($result) == "array")
            {
                if (empty($result))
                    $error_message = __('hanoivip::gift.generate.fail');
                    else
                    {
                        $codes = $result;
                        $message = __('hanoivip::gift.generate.success');
                    }
            }
            else
                throw new Exception('Generate code unknown result type.');
        }
        catch (Exception $ex)
        {
            Log::error('GiftController batch gen gift code exception. Msg:' . $ex->getMessage());
            $error_message = __('hanoivip::gift.generate.exception');
        }
        if ($request->ajax())
            return ['codes' => $codes, 'message' => $message, 'error_message' => $error_message];
        else
        {
            return view('hanoivip::generate-result',
                ['codes' => $codes, 'message' => $message, 'error_message' => $error_message]);
        }
    }
    
    public function sysGenerate(Request $request)
    {
        $package = $request->input('package');
        $target = $request->input('target');
        $result = $this->gift->generate($package, 1, 0, $target);
        if (gettype($result) == "array")
            return $result[0];
        else
            abort(500);
    }
    
    public function generate(GeneratePersonalGift $request)
    {
        $uid = Auth::user()->getAuthIdentifier();
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
                    $error_message = __('hanoivip::gift.generate.fail');
                else
                {
                    $code = $result[0];
                    $message = __('hanoivip::gift.generate.success');
                }
            }
            else
                throw new Exception('Generate code unknown result type.');
        }
        catch (Exception $ex)
        {
            Log::error('GiftController gen gift code exception. Msg:' . $ex->getMessage());
            $error_message = __('hanoivip::gift.generate.exception');
        }
        if ($request->ajax())
            return ['code' => $code, 'message' => $message, 'error_message' => $error_message];
        else 
        {
            return view('hanoivip::user-generate-result', 
                ['code' => $code, 'message' => $message, 'error_message' => $error_message]);
        }
    }
    
    /**
     * Enable thread-safe per user
     */
    public function use(Request $request)
    {
        $user = Auth::user();
        $code = $request->input('code');
        $message = '';
        $error_message = '';
        if (empty($code))
        {
            $error_message = __('hanoivip::gift.use.fail');
            if ($request->ajax())
                return ['message' => $message, 'error_message' => $error_message];
            else
                return view('hanoivip::use-code', ['message' => $message, 'error_message' => $error_message]);
        }
        try 
        {
            // Get Params
            $server = null;
            $role = null;
            if ($request->has('svname'))
            {
                $svname = $request->get('svname');
                $server = $this->servers->getServerByName($svname);
            }
            if ($request->has('roleid'))
                $role = $request->get('roleid');
            // Enable lock & request to service
            $lock = "GiftUsing" . $user->getAuthIdentifier();
            try
            {
                if (!Cache::lock($lock, 120)->get())
                {
                    Log::error("Gift another gift using is in progress..");
                    $error_message = __('hanoivip::gift.use.too-fast');
                }
                else
                {
                    $result = $this->gift->use($user, $code, $server, $role);
                    if (gettype($result) == "string")
                    {
                        $error_message = $result;
                    }
                    else 
                    {
                        if ($result)
                            $message = __('hanoivip::gift.use.success');
                        else
                            $error_message = __('hanoivip::gift.use.fail');
                    }
                    Cache::lock($lock)->release();
                }
            }
            catch (MissionParamException $mpe)
            {
                Cache::lock($lock)->release();
                Log::debug('GiftController user is using game code');
                return response()->redirectToRoute('gift.use2.ui', ['error_message' => __('hanoivip::gift.use.missing-params')]);
            }
            finally 
            {
            }
        }
        catch (Exception $ex)
        {
            Log::error('GiftController use gift code exception. Msg:' . $ex->getMessage());
            $error_message = __('hanoivip::gift.use.exception');
        }
        if ($request->ajax())
            return ['message' => $message, 'error_message' => $error_message];
        else
            return view('hanoivip::use-code', ['message' => $message, 'error_message' => $error_message]);
    }
    
    public function statistics(Request $request)
    {
        return view('hanoivip::gift-stats');
    }
    
    public function history(Request $request)
    {
        $uid = Auth::user()->getAuthIdentifier();
        $histories = [];
        $error_message = '';
        try 
        {
            $histories = $this->gift->history($uid);
        }
        catch (Exception $ex)
        {
            Log::error('Gift get user generation history error. Msg:' . $ex->getMessage());
            $error_message = __('hanoivip::gift.history.exception');
        }
        if ($request->ajax())
            return [ 'histories' => $histories, 'error_message' => $error_message ];
        else
            return view('hanoivip::user-generate-history', 
                [ 'histories' => $histories, 'error_message' => $error_message ]);
    }
}