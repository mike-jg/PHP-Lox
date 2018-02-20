<?php

namespace App\StdLib;

use App\LoxClass;

class LoxArray extends LoxClass
{

    private $values = [];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("Array", null, [
            "get"    => new StdClassMethod([$this, "get"], 1),
            "push"   => new StdClassMethod([$this, "push"], 1),
            "length" => new StdClassMethod([$this, "length"], 0),
            "pop"    => new StdClassMethod([$this, "pop"], 0),
        ]);
    }

    public function get(int $index)
    {
        return $this->values[$index];
    }

    public function push($value)
    {
        $this->values[] = $value;
    }

    public function length()
    {
        return (double)count($this->values);
    }

    public function pop()
    {
        return array_pop($this->values);
    }

}

