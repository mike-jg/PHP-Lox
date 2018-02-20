<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class FunctionDecl extends Stmt 
{

    public function __construct(\App\Token $name, array $parameters, array $body)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->body = $body;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitFunctionDeclStmt($this);
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
     * @var array
     */
    private $parameters;

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @var array
     */
    private $body;

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

}
