<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Get extends Expr 
{

    public function __construct(Expr $object, \App\Token $name)
    {
        $this->object = $object;
        $this->name = $name;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitGetExpr($this);
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

}
