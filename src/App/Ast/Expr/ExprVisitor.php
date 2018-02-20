<?php

/**
 * THIS FILE IS AUTO-GENERATED, DO NOT CHANGE IT MANUALLY
 */
namespace App\Ast\Expr;

interface ExprVisitor {
      public function visitAssignExpr(Assign $expr);
      public function visitBinaryExpr(Binary $expr);
      public function visitCallExpr(Call $expr);
      public function visitGetExpr(Get $expr);
      public function visitSetExpr(Set $expr);
      public function visitSuperExpr(Super $expr);
      public function visitThisExprExpr(ThisExpr $expr);
      public function visitGroupingExpr(Grouping $expr);
      public function visitLiteralExpr(Literal $expr);
      public function visitLogicalExpr(Logical $expr);
      public function visitUnaryExpr(Unary $expr);
      public function visitVariableExpr(Variable $expr);
}
