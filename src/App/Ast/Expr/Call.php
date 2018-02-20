<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

class Call extends Expr 
{

    public function __construct(Expr $callee, \App\Token $paren, array $arguments)
    {
        $this->callee = $callee;
        $this->paren = $paren;
        $this->arguments = $arguments;
    }

   public function accept(ExprVisitor $visitor)
   {
       return $visitor->visitCallExpr($this);
   }

    /**
     * @var Expr
     */
    private $callee;

    /**
     * @return Expr
     */
    public function getCallee()
    {
        return $this->callee;
    }

    /**
     * @var \App\Token
     */
    private $paren;

    /**
     * @return \App\Token
     */
    public function getParen()
    {
        return $this->paren;
    }

    /**
     * @var array
     */
    private $arguments;

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

}
