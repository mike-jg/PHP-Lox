<?php

namespace App;

use Exception;

class ReturnValue extends Exception
{
    private $value;

    /**
     * ReturnValue constructor.
     *
     * @param $value
     */
    public function __construct($value)
    {
        parent::__construct("", 0, null);
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}