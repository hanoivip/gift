<?php

namespace Hanoivip\Gift\ViewObjects;

class GiftVO
{
    /**
     * Gift package code
     * @var string
     */
    public $code;
    public $title;
    /**
     * 
     * @var GiftRewardVO[]
     */
    public $rewards;
}