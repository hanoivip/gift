<?php

namespace Hanoivip\Gift\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Người chơi sinh code cho người khác. 
 * Mục đích chính là để mời bạn chơi.
 *
 */
class BatchGenerateGift extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        // default: count = 1, sender = current user
        return [
            'package' => 'required|string', // Gift package code
            'count' => 'required|integer', // Target User, validate: not same as current user
        ];
    }
}
