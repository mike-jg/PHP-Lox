<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class Expression extends Stmt 
{

    public function __construct(\App\Ast\Expr\Expr $expression)
    {
        $this->expression = $expression;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitExpressionStmt($this);
   }

    /**
     * @var \App\Ast\Expr\Expr
     */
    private $expression;

    /**
     * @return \App\Ast\Expr\Expr
     */
    public function getExpression()
    {
        return $this->expression;
    }

}
