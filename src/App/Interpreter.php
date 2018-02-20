<?php declare(strict_types=1);

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
use App\StdLib\Clock;
use App\StdLib\Input;
use App\StdLib\LoxArray;
use SplObjectStorage;

class Interpreter implements ExprVisitor, StmtVisitor
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Environment
     */
    private $globals;

    /**
     * Variable resolutions
     *
     * Stores the number of steps a declared variable is in the environment chain
     *
     * @var SplObjectStorage
     */
    private $locals;

    /**
     * @var Output
     */
    private $output;

    /**
     * @var ErrorReporter
     */
    private $reporter;

    /**
     * Interpreter constructor.
     *
     * @param Output $output
     * @param ErrorReporter $reporter
     */
    public function __construct(Output $output, ErrorReporter $reporter)
    {
        $this->globals = new Environment();
        $this->environment = $this->globals;
        $this->locals = new SplObjectStorage();
        $this->output = $output;
        $this->reporter = $reporter;

        $this->globals->define("clock", new Clock());
        $this->globals->define("Array", new LoxArray());
        $this->globals->define("Input", new Input($output));
    }

    public function resolve(Expr $expr, int $depth)
    {
        $this->locals->attach($expr, $depth);
    }

    public function interpret(array $statements)
    {
        try {
            foreach ($statements as $statement) {
                $this->execute($statement);
            }
        } catch (RuntimeError $e) {
            $this->reporter->runtimeError($e);
        }
    }

    private function execute(Stmt $statement)
    {
        $statement->accept($this);
    }

    public function visitBreakStmtStmt(BreakStmt $stmt)
    {
        throw new IterationBreak();
    }

    public function visitFnReturnStmt(FnReturn $stmt)
    {
        $value = null;
        if ($stmt->getValue() !== null) {
            $value = $this->evaluate($stmt->getValue());
        }

        // as a return can occur deep in the callstack,
        // returns are trowables as it's the easiest
        // way to unwind the stack and get back out
        throw new ReturnValue($value);
    }

    private function evaluate(Expr $expr)
    {
        return $expr->accept($this);
    }

    public function visitFunctionDeclStmt(FunctionDecl $stmt)
    {
        $function = new LoxFunction($stmt, $this->environment, false);
        $this->environment->define($stmt->getName()->getLexeme(), $function);

        return null;
    }

    public function visitClassDeclStmt(ClassDecl $stmt)
    {
        $this->environment->define($stmt->getName()->getLexeme(), null);

        $superclass = null;
        if ($stmt->getSuperclass() !== null) {
            $superclass = $this->evaluate($stmt->getSuperclass());
            if ( ! ($superclass instanceof LoxClass)) {
                throw new RuntimeError($stmt->getName(), "Superclass must be a class.");
            }
            $this->environment = new Environment($this->environment);
            $this->environment->define("super", $superclass);
        }

        $methods = [];
        foreach ($stmt->getMethods() as $method) {
            $fn = new LoxFunction($method, $this->environment, $method->getName()->getLexeme() === "init");
            $methods[$method->getName()->getLexeme()] = $fn;
        }

        $class = new LoxClass($stmt->getName()->getLexeme(), $superclass, $methods);

        if ($superclass !== null) {
            $this->environment = $this->environment->getEnclosing();
        }

        $this->environment->assign($stmt->getName(), $class);
    }

    public function visitBlockStmt(Block $stmt)
    {
        $this->executeBlock($stmt->getStatements(), new Environment($this->environment));
    }

    public function executeBlock(array $statements, Environment $env)
    {
        $previousEnv = $this->environment;
        try {
            $this->environment = $env;

            foreach ($statements as $stmt) {
                $this->execute($stmt);
            }
        } finally {
            $this->environment = $previousEnv;
        }
    }

    public function visitExpressionStmt(Expression $stmt)
    {
        $this->evaluate($stmt->getExpression());

        return null;
    }

    public function visitVariableStmt(Variable $stmt)
    {
        $value = null;
        if ($stmt->getInitializer() !== null) {
            $value = $this->evaluate($stmt->getInitializer());
        }

        $this->environment->define($stmt->getName()->getLexeme(), $value);

        return null;
    }

    public function visitWhileLoopStmt(WhileLoop $stmt)
    {
        try {
            while ($this->isTruthy($this->evaluate($stmt->getCondition()))) {
                $this->execute($stmt->getBody());
            }
        } catch (IterationBreak $break) {

        }

        return null;
    }

    /**
     * nil and false are falsey, everything else is truthy
     *
     * @return bool
     */
    private function isTruthy($expr): bool
    {
        if ($expr === null) {
            return false;
        }
        if (is_bool($expr)) {
            return $expr;
        }

        return true;
    }

    public function visitConditionalStmt(Conditional $stmt)
    {
        if ($this->isTruthy($this->evaluate($stmt->getCondition()))) {
            $this->execute($stmt->getThenBranch());
        } else if ($stmt->getElseBranch() !== null) {
            $this->execute($stmt->getElseBranch());
        }

        return null;
    }

    public function visitPrntStmt(Prnt $stmt)
    {
        $value = $this->stringify($this->evaluate($stmt->getExpression()));
        $this->output->print($value);

        return null;
    }

    private function stringify($value): string
    {
        if ($value === null) {
            return "nil";
        }
        if ($value === true) {
            return "true";
        }
        if ($value === false) {
            return "false";
        }

        return (string)$value;
    }

    public function visitThisExprExpr(ThisExpr $expr)
    {
        return $this->lookupVariable($expr->getKeyword(), $expr);
    }

    private function lookupVariable(Token $name, Expr $expr)
    {
        if ($this->locals->contains($expr)) {
            $distance = $this->locals->offsetGet($expr);
            if ($distance !== null) {
                return $this->environment->getAt($distance, $name->getLexeme());
            }
        }

        return $this->globals->get($name);
    }

    public function visitSuperExpr(Super $expr)
    {
        $distance = $this->locals->offsetGet($expr);
        /* @var $superclass LoxClass */
        $superclass = $this->environment->getAt($distance, "super");

        // 'this' is always one level nearer than 'super's environment
        $instance = $this->environment->getAt($distance - 1, "this");

        $method = $superclass->findMethod($instance, $expr->getMethod()->getLexeme());

        if ($method === null) {
            throw new RuntimeError($expr->getMethod(), "Undefined property '" . $expr->getMethod()->getLexeme() . "'.");
        }

        return $method;
    }

    public function visitSetExpr(Set $expr)
    {
        $obj = $this->evaluate($expr->getObject());

        if ( ! ($obj instanceof LoxInstance)) {
            throw new RuntimeError($expr->getName(), "Only instances have fields.");
        }

        $value = $this->evaluate($expr->getValue());

        $obj->set($expr->getName(), $value);

        return $value;
    }

    public function visitGetExpr(Get $expr)
    {
        $obj = $this->evaluate($expr->getObject());
        if ($obj instanceof LoxInstance) {
            return $obj->get($expr->getName());
        }

        throw new RuntimeError($expr->getName(), "Only instances have properties.");
    }

    public function visitAssignExpr(Assign $expr)
    {
        $value = $this->evaluate($expr->getValue());

        if ($this->locals->contains($expr)) {
            $distance = $this->locals->offsetGet($expr);
            if ($distance !== null) {
                $this->environment->assignAt($distance, $expr->getName(), $value);

                return $value;
            }
        }

        $this->globals->assign($expr->getName(), $value);

        return $value;
    }

    public function visitVariableExpr(VariableExpr $expr)
    {
        return $this->lookupVariable($expr->getName(), $expr);
    }

    public function visitCallExpr(Call $expr)
    {
        $callee = $this->evaluate($expr->getCallee());

        $args = [];
        foreach ($expr->getArguments() as $arg) {
            $args[] = $this->evaluate($arg);
        }

        if ( ! $callee instanceof LoxCallable) {
            throw new RuntimeError($expr->getParen(), "Can only call functions and classes.");
        }
        if (count($args) !== $callee->arity()) {
            throw new RuntimeError($expr->getParen(),
                sprintf("Expected %d arguments but got %d.", $callee->arity(), count($args)));
        }

        return $callee->call($this, $args);
    }

    public function visitLogicalExpr(Logical $expr)
    {
        $left = $this->evaluate($expr->getLeft());

        if ($expr->getOperator()->getType() === TokenType::LOGICAL_OR) {
            if ($this->isTruthy($left)) {
                return $left;
            }
        } else {
            if ( ! $this->isTruthy($left)) {
                return $left;
            }
        }

        return $this->evaluate($expr->getRight());
    }

    public function visitBinaryExpr(Binary $expr)
    {
        $left = $this->evaluate($expr->getLeft());
        $right = $this->evaluate($expr->getRight());

        switch ($expr->getOperator()->getType()) {
            case TokenType::GREATER:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                return (double)$left > (double)$right;

            case TokenType::GREATER_EQUAL:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                return (double)$left >= (double)$right;

            case TokenType::LESS:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                return (double)$left < (double)$right;

            case TokenType::LESS_EQUAL:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                return (double)$left <= (double)$right;

            case TokenType::MINUS:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                return (double)$left - (double)$right;

            case TokenType::SLASH:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                if ($right == 0) {
                    throw new RuntimeError($expr->getOperator(), "Division by zero.");
                }

                return (double)$left / (double)$right;

            case TokenType::STAR:
                $this->checkNumberOperands($expr->getOperator(), $left, $right);

                return (double)$left * (double)$right;

            case TokenType::PLUS:
                if (is_string($left) && is_string($right)) {
                    return $left . $right;
                } else if (is_double($left) && is_double($right)) {
                    return (double)$left + (double)$right;
                }

                throw new RuntimeError($expr->getOperator(), "Operands must be two numbers or two strings.");

            case TokenType::BANG_EQUAL:
                return ! $this->isEqual($left, $right);

            case TokenType::EQUAL_EQUAL:
                return $this->isEqual($left, $right);
        }

        return null;
    }

    private function checkNumberOperands(Token $operator, $left, $right)
    {
        if (is_double($left) && is_double($right)) {
            return;
        }

        throw new RuntimeError($operator, "Operands must be numbers.");
    }

    private function isEqual($left, $right): bool
    {
        // nil is only equal to nil
        if ($left === null && $right === null) {
            return true;
        }
        if ($left === null) {
            return false;
        }

        return $left === $right;
    }

    public function visitGroupingExpr(Grouping $expr)
    {
        return $this->evaluate($expr->getExpression());
    }

    public function visitLiteralExpr(Literal $expr)
    {
        return $expr->getValue();
    }

    public function visitUnaryExpr(Unary $expr)
    {
        $right = $this->evaluate($expr->getRight());

        switch ($expr->getOperator()->getType()) {
            case TokenType::MINUS:
                $this->checkNumberOperand($expr->getOperator(), $right);

                return -(double)$right;

            case TokenType::BANG:
                return ! $this->isTruthy($right);
        }

        return null;
    }

    private function checkNumberOperand(Token $operator, $operand)
    {
        if (is_double($operand)) {
            return;
        }

        throw new RuntimeError($operator, "Operand must be a number.");
    }
}