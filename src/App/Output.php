<?php declare(strict_types=1);

namespace App;

interface Output
{
    public function print (string $message);
    public function printError(string $message);
}