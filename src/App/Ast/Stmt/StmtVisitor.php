<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Stmt;

interface StmtVisitor {
      public function visitBlockStmt(Block $stmt);
      public function visitExpressionStmt(Expression $stmt);
      public function visitClassDeclStmt(ClassDecl $stmt);
      public function visitFunctionDeclStmt(FunctionDecl $stmt);
      public function visitConditionalStmt(Conditional $stmt);
      public function visitPrntStmt(Prnt $stmt);
      public function visitFnReturnStmt(FnReturn $stmt);
      public function visitVariableStmt(Variable $stmt);
      public function visitWhileLoopStmt(WhileLoop $stmt);
      public function visitBreakStmtStmt(BreakStmt $stmt);
}
