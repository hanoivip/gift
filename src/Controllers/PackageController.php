<?php

namespace Hanoivip\Gift\Controllers;

use Carbon\Carbon;
use Hanoivip\Gift\Requests\CreatePackage;
use Hanoivip\Gift\Requests\ViewPackage;
use Hanoivip\Gift\Services\GiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Hanoivip\Gift\GiftPackage;

class PackageController extends Controller
{
    protected $gift;
    
    public function __construct(GiftService $gift)
    {
        $this->gift = $gift;    
    }
    
    public function list(Request $request)
    {
        $packages = [];
        $error = '';
        try 
        {
            $packages = $this->gift->packges();
        }
        catch (Exception $ex)
        {
            Log::error('Package list all exception. Msg:' . $ex->getMessage());
            $error = __('gift.package.list.exception');
        }
        if ($request->ajax())
            return ['error' => $error, 'packages' => $packages];
        else 
            return view('hanoivip::package-list', ['error' => $error, 'packages' => $packages]);
    }
    
    public function new(Request $request)
    {
        if ($request->ajax())
            return [];
        else
            return view('hanoivip::package-new');
    }
    
    public function view(ViewPackage $request)
    {
        $code = $request->input('code');
        $package = null;
        $error = '';
        try 
        {
            $package = $this->gift->packges($code);
        } 
        catch (Exception $ex) 
        {
            Log::error('Package view exception. Msg:' . $ex->getMessage());
            $error = __('gift.package.view.exception');
        }
        if ($request->ajax())
            return ['error' => $error, 'package' => $package];
        else 
            return view('hanoivip::package-detail', ['error' => $error, 'package' => $package]);
    }
    
    public function remove(ViewPackage $request)
    {
        $code = $request->input('code');
        $package = null;
        $message = '';
        $error_message = '';
        try
        {
            if ($this->gift->removePackage($code))
                $message = __('gift.package.remove.success');
            else
                $error_message = __('gift.package.remove.fail');
        }
        catch (Exception $ex)
        {
            Log::error('Package remove exception. Msg:' . $ex->getMessage());
            $error = __('gift.package.remove.exception');
        }
        if ($request->ajax())
            return ['message' => $message, 'error_message' => $error_message];
        else
            return view('hanoivip::package-process-result', ['message' => $message, 'error_message' => $error_message]);
    }
    
    public function create(CreatePackage $request)
    {
        $message = '';
        $error_message = '';
        try 
        {
            $package = $this->gift->packges($request->input('code'));
            if (!empty($package))
                $error_message = __('gift.package.create.duplicated_code');
            else 
            {
                $data = $request->all();
                unset($data['_token']);
                $data['start_time'] = Carbon::parse($data['start_time']);
                $data['end_time'] = Carbon::parse($data['end_time']);
                $this->gift->createPackage($data);
                $message = __('gift.package.create.success');
            }
        }
        catch (Exception $ex)
        {
            Log::error('Package create exception. Msg:' . $ex->getMessage());
            $error = __('gift.package.create.exception');
        }
        if ($request->ajax())
            return ['message' => $message, 'error_message' => $error_message];
        else
            return view('hanoivip::package-process-result', ['message' => $message, 'error_message' => $error_message]);
    }
    
    public function update(CreatePackage $request)
    {
        $message = '';
        $error_message = '';
        try
        {
            $package = $this->gift->packges($request->input('code'));
            if (!empty($package))
                $error_message = __('gift.package.update.not_found');
            else
            {
                $this->gift->updatePackage($request->all());
                $message = __('gift.package.update.success');
            }
        }
        catch (Exception $ex)
        {
            Log::error('Package update exception. Msg:' . $ex->getMessage());
            $error = __('gift.package.update.exception');
        }
        if ($request->ajax())
            return ['message' => $message, 'error_message' => $error_message];
        else
            return view('hanoivip::package-process-result', ['message' => $message, 'error_message' => $error_message]);
    }
}