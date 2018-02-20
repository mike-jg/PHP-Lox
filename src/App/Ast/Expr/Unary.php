<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Unary extends Expr 
{

    public function __construct(\App\Token $operator, Expr $right)
    {
        $this->operator = $operator;
        $this->right = $right;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitUnaryExpr($this);
   }

    /**
     * @var \App\Token
     */
    private $operator;

    /**
     * @return \App\Token
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @var Expr
     */
    private $right;

    /**
     * @return Expr
     */
    public function getRight()
    {
        return $this->right;
    }

}
