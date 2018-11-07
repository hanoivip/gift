<?php

namespace Hanoivip\Gift\Requests;

use Illuminate\Foundation\Http\FormRequest;


class CreatePackage extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        return [
            'pack_code' => 'required|string',
            'name' => 'required|string',
            'limit' => 'integer',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'rewards' => 'required|string',
            'const_code' => 'boolean',
            'server_include' => 'string',
            'server_exclude' => 'string',
            'allow_users' => 'integer'
        ];
    }
}
