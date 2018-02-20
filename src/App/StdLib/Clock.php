<?php declare(strict_types=1);

namespace App\StdLib;

use App\Interpreter;
use App\LoxCallable;

class Clock implements LoxCallable
{
    public function arity(): int
    {
        return 0;
    }

    public function call(Interpreter $interpreter, array $arguments)
    {
        return (double) time();
    }

}