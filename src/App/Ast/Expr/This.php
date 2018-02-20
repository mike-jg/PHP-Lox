<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class This extends Expr
{

    public function __construct(\App\Token $keyword)
    {
        $this->keyword = $keyword;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitThisExpr($this);
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

}
