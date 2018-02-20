<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class WhileLoop extends Stmt 
{

    public function __construct(\App\Ast\Expr\Expr $condition, Stmt $body)
    {
        $this->condition = $condition;
        $this->body = $body;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitWhileLoopStmt($this);
   }

    /**
     * @var \App\Ast\Expr\Expr
     */
    private $condition;

    /**
     * @return \App\Ast\Expr\Expr
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @var Stmt
     */
    private $body;

    /**
     * @return Stmt
     */
    public function getBody()
    {
        return $this->body;
    }

}
