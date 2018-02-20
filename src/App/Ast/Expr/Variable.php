<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Variable extends Expr 
{

    public function __construct(\App\Token $name)
    {
        $this->name = $name;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitVariableExpr($this);
   }

    /**
     * @var \App\Token
     */
    private $name;

    /**
     * @return \App\Token
     */
    public function getName()
    {
        return $this->name;
    }

}
