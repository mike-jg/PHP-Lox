<?php

namespace App;

class RuntimeError extends \RuntimeException
{

    /**
     * @var Token
     */
    private $token;

    /**
     * RuntimeError constructor.
     *
     * @param Token $token
     */
    public function __construct(Token $token, string $message)
    {
        parent::__construct($message);
        $this->token = $token;
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
}