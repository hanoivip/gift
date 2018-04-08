<?php

namespace Hanoivip\Gift\Contracts;

interface IGift
{
    public function generate($package, $count = 10, $genUid = 0, $target = null);
    
    public function use($user, $code);
}