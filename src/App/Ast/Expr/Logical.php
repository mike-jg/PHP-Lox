<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Logical extends Expr 
{

    public function __construct(Expr $left, \App\Token $operator, Expr $right)
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitLogicalExpr($this);
   }

    /**
     * @var Expr
     */
    private $left;

    /**
     * @return Expr
     */
    public function getLeft()
    {
        return $this->left;
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
