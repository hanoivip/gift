<?php

namespace Hanoivip\Gift;

use Illuminate\Database\Eloquent\Model;

class GiftCode extends Model
{
    protected $fillable = ['gift_code', 'pack', 'generate_uid', 'target'];
    
    public $timestamps = false;
}
