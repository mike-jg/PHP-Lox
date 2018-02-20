<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Super extends Expr 
{

    public function __construct(\App\Token $keyword, \App\Token $method)
    {
        $this->keyword = $keyword;
        $this->method = $method;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitSuperExpr($this);
   }

    /**
     * @var \App\Token
     */
    private $keyword;

    /**
     * @return \App\Token
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @var \App\Token
     */
    private $method;

    /**
     * @return \App\Token
     */
    public function getMethod()
    {
        return $this->method;
    }

}
