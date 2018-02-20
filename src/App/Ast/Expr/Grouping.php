<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Grouping extends Expr 
{

    public function __construct(Expr $expression)
    {
        $this->expression = $expression;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitGroupingExpr($this);
   }

    /**
     * @var Expr
     */
    private $expression;

    /**
     * @return Expr
     */
    public function getExpression()
    {
        return $this->expression;
    }

}
