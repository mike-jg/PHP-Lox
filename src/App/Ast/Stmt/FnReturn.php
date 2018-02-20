<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class FnReturn extends Stmt 
{

    public function __construct(\App\Token $keyword, ?\App\Ast\Expr\Expr $value)
    {
        $this->keyword = $keyword;
        $this->value = $value;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitFnReturnStmt($this);
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
     * @var \App\Ast\Expr\Expr
     */
    private $value;

    /**
     * @return \App\Ast\Expr\Expr
     */
    public function getValue()
    {
        return $this->value;
    }

}
