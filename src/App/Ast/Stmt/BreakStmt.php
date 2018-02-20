<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class BreakStmt extends Stmt 
{

    public function __construct(\App\Token $token)
    {
        $this->token = $token;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitBreakStmtStmt($this);
   }

    /**
     * @var \App\Token
     */
    private $token;

    /**
     * @return \App\Token
     */
    public function getToken()
    {
        return $this->token;
    }

}
