<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Literal extends Expr 
{

    public function __construct($value)
    {
        $this->value = $value;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitLiteralExpr($this);
   }

    private $value;

    public function getValue()
    {
        return $this->value;
    }

}
