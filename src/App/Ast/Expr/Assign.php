<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Assign extends Expr 
{

    public function __construct(\App\Token $name, Expr $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitAssignExpr($this);
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

    /**
     * @var Expr
     */
    private $value;

    /**
     * @return Expr
     */
    public function getValue()
    {
        return $this->value;
    }

}
