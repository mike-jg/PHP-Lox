<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class Variable extends Stmt 
{

    public function __construct(\App\Token $name, ?\App\Ast\Expr\Expr $initializer)
    {
        $this->name = $name;
        $this->initializer = $initializer;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitVariableStmt($this);
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
     * @var \App\Ast\Expr\Expr
     */
    private $initializer;

    /**
     * @return \App\Ast\Expr\Expr
     */
    public function getInitializer()
    {
        return $this->initializer;
    }

}
