<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class Conditional extends Stmt 
{

    public function __construct(\App\Ast\Expr\Expr $condition, Stmt $thenBranch, ?Stmt $elseBranch)
    {
        $this->condition = $condition;
        $this->thenBranch = $thenBranch;
        $this->elseBranch = $elseBranch;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitConditionalStmt($this);
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
    private $thenBranch;

    /**
     * @return Stmt
     */
    public function getThenBranch()
    {
        return $this->thenBranch;
    }

    /**
     * @var Stmt
     */
    private $elseBranch;

    /**
     * @return Stmt
     */
    public function getElseBranch()
    {
        return $this->elseBranch;
    }

}
