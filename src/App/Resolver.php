<?php

namespace App;

use App\Ast\Expr\Assign;
use App\Ast\Expr\Binary;
use App\Ast\Expr\Call;
use App\Ast\Expr\Expr;
use App\Ast\Expr\ExprVisitor;
use App\Ast\Expr\Get;
use App\Ast\Expr\Grouping;
use App\Ast\Expr\Literal;
use App\Ast\Expr\Logical;
use App\Ast\Expr\Set;
use App\Ast\Expr\Super;
use App\Ast\Expr\ThisExpr;
use App\Ast\Expr\Unary;
use App\Ast\Expr\Variable as VariableExpr;
use App\Ast\Stmt\Block;
use App\Ast\Stmt\BreakStmt;
use App\Ast\Stmt\ClassDecl;
use App\Ast\Stmt\Conditional;
use App\Ast\Stmt\Expression;
use App\Ast\Stmt\FnReturn;
use App\Ast\Stmt\FunctionDecl;
use App\Ast\Stmt\Prnt;
use App\Ast\Stmt\Stmt;
use App\Ast\Stmt\StmtVisitor;
use App\Ast\Stmt\Variable;
use App\Ast\Stmt\WhileLoop;
use SplStack;


/**
 * Variable resolution pass
 */
class Resolver implements ExprVisitor, StmtVisitor
{

    const FUNC_TYPE_NONE = 0;
    const FUNC_TYPE_FUNCTION = 1;
    const FUNC_TYPE_CONSTRUCTOR = 2;
    const FUNC_TYPE_METHOD = 3;

    const CLASS_TYPE_NONE = 10;
    const CLASS_TYPE_CLASS = 11;
    const CLASS_TYPE_SUBCLASS = 12;

    const LOOP_TYPE_NONE = 20;
    const LOOP_TYPE_LOOP = 21;

    /**
     * Are we currently in a class?
     *
     * For help resolving 'this'
     *
     * @var int
     */
    private $currentClassType = self::CLASS_TYPE_NONE;

    /**
     * Which kind of function we are currently in
     *
     * This is so that return statements outside function scopes can be banned
     *
     * @var int
     */
    private $currentFunction = self::FUNC_TYPE_NONE;

    /**
     * Which kind of loop are we currently in
     *
     * This is so that break statements outside of loops can be banned
     *
     * @var int
     */
    private $currentLoop = self::LOOP_TYPE_NONE;


    /**
     * @var Interpreter
     */
    private $interpreter;

    /**
     * @var array
     */
    private $scopes = [];

    /**
     * @var ErrorReporter
     */
    private $reporter;

    /**
     * Resolver constructor.
     *
     * @param Interpreter $interpreter
     * @param ErrorReporter $reporter
     */
    public function __construct(Interpreter $interpreter, ErrorReporter $reporter)
    {
        $this->interpreter = $interpreter;
        $this->reporter = $reporter;
    }

    public function resolve($stmtsOrExprs)
    {
        if ($stmtsOrExprs instanceof Expr || $stmtsOrExprs instanceof Stmt) {
            $stmtsOrExprs->accept($this);
        } else if (is_array($stmtsOrExprs)) {
            foreach ($stmtsOrExprs as $stmtOrExpr) {
                $this->resolve($stmtOrExpr);
            }
        } else {
            throw new \InvalidArgumentException(
                __METHOD__ . " requires that argument is array or instance of Expr or Stmt. '" . gettype($stmtsOrExprs) . "' given."
            );
        }
    }

    private function &peekScope()
    {
        return $this->scopes[count($this->scopes) - 1];
    }

    private function beginScope()
    {
        $this->scopes[] = [];
    }

    private function endScope()
    {
        array_pop($this->scopes);
    }

    /**
     * Declaration of a variable
     *
     * @param Token $name
     */
    private function declare(Token $name)
    {
        if (empty($this->scopes)) {
            return;
        }

        $scope = &$this->peekScope();

        if (array_key_exists($name->getLexeme(), $scope)) {
            $this->reporter->atToken($name, "Variable with this name already declared in this scope.");
        }

        $scope[$name->getLexeme()] = false;
    }

    /**
     * Definition of a variable - assigning it a variable
     *
     * @param Token $name
     */
    private function define(Token $name)
    {
        if (empty($this->scopes)) {
            return;
        }

        $scope = &$this->peekScope();
        $scope[$name->getLexeme()] = true;
    }

    private function resolveLocal(Expr $expression, Token $name)
    {
        for ($i = count($this->scopes) - 1; $i >= 0; $i--) {
            if (array_key_exists($name->getLexeme(), $this->scopes[$i])) {
                // pass in the number of scopes between
                // the current inner most scope and the scope
                // where the variable was found
                $this->interpreter->resolve($expression, count($this->scopes) - 1 - $i);

                return;
            }
        }
    }

    public function visitThisExprExpr(ThisExpr $expr)
    {
        if ($this->currentClassType === self::CLASS_TYPE_NONE) {
            $this->reporter->atToken($expr->getKeyword(), "Cannot use 'this' outside of a class.");

            return;
        }

        $this->resolveLocal($expr, $expr->getKeyword());
    }

    public function visitSetExpr(Set $expr)
    {
        $this->resolve($expr->getValue());
        $this->resolve($expr->getObject());
    }

    public function visitGetExpr(Get $expr)
    {
        $this->resolve($expr->getObject());
    }

    public function visitClassDeclStmt(ClassDecl $stmt)
    {
        $this->declare($stmt->getName());
        $this->define($stmt->getName());

        $enclosingClass = $this->currentClassType;
        $this->currentClassType = self::CLASS_TYPE_CLASS;

        if ($stmt->getSuperclass() !== null) {
            $this->currentClassType = self::CLASS_TYPE_SUBCLASS;
            $this->resolve($stmt->getSuperclass());
            $this->beginScope();
            $peek = &$this->peekScope();
            $peek["super"] = true;
        }

        $this->beginScope();
        $scope = &$this->peekScope();
        $scope["this"] = true;

        foreach ($stmt->getMethods() as $func) {
            $decl = self::FUNC_TYPE_METHOD;
            if ($func->getName()->getLexeme() === "init") {
                $decl = self::FUNC_TYPE_CONSTRUCTOR;
            }
            $this->resolveFunction($func, $decl);
        }

        $this->endScope();

        if ($stmt->getSuperclass() !== null) {
            $this->endScope();
        }

        $this->currentClassType = $enclosingClass;
    }

    public function visitSuperExpr(Super $expr)
    {
        if ($this->currentClassType === self::CLASS_TYPE_NONE) {
            $this->reporter->atToken($expr->getKeyword(), "Cannot use 'super' outside of a class.");
        } else if ($this->currentClassType !== self::CLASS_TYPE_SUBCLASS) {
            $this->reporter->atToken($expr->getKeyword(), "Cannot use 'super' in a class with no superclass.");
        }

        $this->resolveLocal($expr, $expr->getKeyword());
    }

    private function resolveFunction(FunctionDecl $fn, int $functionType)
    {
        $enclosingFunction = $this->currentFunction;
        $this->currentFunction = $functionType;

        $this->beginScope();
        foreach ($fn->getParameters() as $param) {
            $this->declare($param);
            $this->define($param);
        }
        $this->resolve($fn->getBody());
        $this->endScope();

        $this->currentFunction = $enclosingFunction;
    }

    public function visitAssignExpr(Assign $expr)
    {
        $this->resolve($expr->getValue());
        $this->resolveLocal($expr, $expr->getName());
    }

    public function visitBinaryExpr(Binary $expr)
    {
        $this->resolve($expr->getLeft());
        $this->resolve($expr->getRight());
    }

    public function visitCallExpr(Call $expr)
    {
        $this->resolve($expr->getCallee());

        foreach ($expr->getArguments() as $arg) {
            $this->resolve($arg);
        }
    }

    public function visitGroupingExpr(Grouping $expr)
    {
        $this->resolve($expr->getExpression());
    }

    public function visitLiteralExpr(Literal $expr)
    {
    }

    public function visitLogicalExpr(Logical $expr)
    {
        $this->resolve($expr->getLeft());
        $this->resolve($expr->getRight());
    }

    public function visitUnaryExpr(Unary $expr)
    {
        $this->resolve($expr->getRight());
    }

    public function visitVariableExpr(VariableExpr $expr)
    {
        if ( ! empty($this->scopes)) {
            $topScope = $this->peekScope();

            if (array_key_exists($expr->getName()->getLexeme(), $topScope) &&
                $topScope[$expr->getName()->getLexeme()] === false) {
                $this->reporter->atToken($expr->getName(), "Cannot read local variable in its own initializer.");
            }
        }

        $this->resolveLocal($expr, $expr->getName());
    }

    public function visitBlockStmt(Block $stmt)
    {
        $this->beginScope();
        $this->resolve($stmt->getStatements());
        $this->endScope();
    }

    public function visitExpressionStmt(Expression $stmt)
    {
        $this->resolve($stmt->getExpression());
    }

    public function visitFunctionDeclStmt(FunctionDecl $stmt)
    {
        $this->declare($stmt->getName());
        $this->define($stmt->getName());

        $this->resolveFunction($stmt, static::FUNC_TYPE_FUNCTION);
    }

    public function visitBreakStmtStmt(BreakStmt $stmt)
    {
        if ($this->currentLoop === self::LOOP_TYPE_NONE) {
            $this->reporter->atToken($stmt->getToken(), "Cannot break outside of a loop.");
        }
    }

    public function visitConditionalStmt(Conditional $stmt)
    {
        $this->resolve($stmt->getCondition());
        $this->resolve($stmt->getThenBranch());
        if ($stmt->getElseBranch() !== null) {
            $this->resolve($stmt->getElseBranch());
        }
    }

    public function visitPrntStmt(Prnt $stmt)
    {
        $this->resolve($stmt->getExpression());
    }

    public function visitFnReturnStmt(FnReturn $stmt)
    {
        if ($this->currentFunction === static::FUNC_TYPE_NONE) {
            $this->reporter->atToken($stmt->getKeyword(), "Cannot return from top-level code.");
        }

        if ($stmt->getValue() !== null) {
            if ($this->currentFunction === static::FUNC_TYPE_CONSTRUCTOR) {
                $this->reporter->atToken($stmt->getKeyword(), "Cannot return a value from a constructor.");
            }

            $this->resolve($stmt->getValue());
        }
    }

    public function visitVariableStmt(Variable $stmt)
    {
        $this->declare($stmt->getName());
        if ($stmt->getInitializer() !== null) {
            $this->resolve($stmt->getInitializer());
        }
        $this->define($stmt->getName());
    }

    public function visitWhileLoopStmt(WhileLoop $stmt)
    {
        $this->resolve($stmt->getCondition());

        $oldLoopType = $this->currentLoop;
        $this->currentLoop = self::LOOP_TYPE_LOOP;

        $this->resolve($stmt->getBody());

        $this->currentLoop = $oldLoopType;
    }


}