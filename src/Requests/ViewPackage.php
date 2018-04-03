<?php

namespace Hanoivip\Gift\Requests;

use Illuminate\Foundation\Http\FormRequest;


class ViewPackage extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        return [
            // can check length by config
            'pack_code' => 'required|string'
        ];
    }
}
