<?php

namespace Hanoivip\Gift\Requests;

use Illuminate\Foundation\Http\FormRequest;


class UseGift extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        return [
            // can check length by config
            'code' => 'required|string'
        ];
    }
}
