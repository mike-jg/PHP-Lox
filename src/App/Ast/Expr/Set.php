<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Set extends Expr 
{

    public function __construct(Expr $object, \App\Token $name, Expr $value)
    {
        $this->object = $object;
        $this->name = $name;
        $this->value = $value;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitSetExpr($this);
   }

    /**
     * @var Expr
     */
    private $object;

    /**
     * @return Expr
     */
    public function getObject()
    {
        return $this->object;
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
