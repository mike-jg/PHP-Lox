<?php

namespace App;

interface LoxCallable
{
    public function arity(): int;

    public function call(Interpreter $interpreter, array $arguments);
}