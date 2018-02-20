<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

class Block extends Stmt 
{

    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

   public function accept(StmtVisitor $visitor)
   {
       return $visitor->visitBlockStmt($this);
   }

    /**
     * @var array
     */
    private $statements;

    /**
     * @return array
     */
    public function getStatements()
    {
        return $this->statements;
    }

}
