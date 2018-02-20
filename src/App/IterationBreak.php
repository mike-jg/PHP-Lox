<?php

namespace App;

use Throwable;

class IterationBreak extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Break");
    }


}