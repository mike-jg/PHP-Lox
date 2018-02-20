<?php declare(strict_types=1);

namespace App;

interface ErrorReporter
{

    public function atLine(int $line, string $message);

    public function atToken(Token $token, string $message);

    public function runtimeError(RuntimeError $error);

}