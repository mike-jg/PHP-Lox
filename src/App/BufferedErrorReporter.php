<?php

namespace App;

class BufferedErrorReporter implements ErrorReporter
{

    private $errorBuffer = [];
    private $hadRuntimeError = false;
    private $hadError = false;

    public function atLine(int $line, string $message)
    {
        self::report($line, "", $message);
    }

    public function atToken(Token $token, string $message)
    {
        if ($token->getType() === TokenType::EOF) {
            self::report($token->getLine(), "at end", $message);
        } else {
            self::report($token->getLine(), "at '" . $token->getLexeme() . "'", $message);
        }
    }

    public function runtimeError(RuntimeError $error)
    {
        $this->errorBuffer[] = sprintf("%s\n[line %d]", $error->getMessage(), $error->getToken()->getLine());
        $this->hadRuntimeError = true;
    }

    private function report(int $line, string $where, string $message)
    {
        if ($where) {
            $err = sprintf("[line %d] Error %s: %s", $line, $where, $message);;
        } else {
            $err = sprintf("[line %d] Error: %s", $line, $message);
        }
        $this->errorBuffer[] = $err;
        $this->hadError = true;
    }

    /**
     * @return bool
     */
    public function hadRuntimeError(): bool
    {
        return $this->hadRuntimeError;
    }

    /**
     * @return bool
     */
    public function hadError(): bool
    {
        return $this->hadError;
    }

    /**
     * @return array
     */
    public function getErrorBuffer(): array
    {
        return $this->errorBuffer;
    }
}