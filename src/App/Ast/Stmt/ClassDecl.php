<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class ClassDecl extends Stmt 
{

    public function __construct(\App\Token $name, ?\App\Ast\Expr\Expr $superclass, array $methods)
    {
        $this->name = $name;
        $this->superclass = $superclass;
        $this->methods = $methods;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitClassDeclStmt($this);
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
    private $superclass;

    /**
     * @return \App\Ast\Expr\Expr
     */
    public function getSuperclass()
    {
        return $this->superclass;
    }

    /**
     * @var array
     */
    private $methods;

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

}
