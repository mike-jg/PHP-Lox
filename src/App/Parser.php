<?php declare(strict_types=1);

namespace App;

use App\Ast\Expr\Assign;
use App\Ast\Expr\Binary;
use App\Ast\Expr\Call;
use App\Ast\Expr\Expr;
use App\Ast\Expr\Get;
use App\Ast\Expr\Grouping;
use App\Ast\Expr\Literal;
use App\Ast\Expr\Logical;
use App\Ast\Expr\Set;
use App\Ast\Expr\Super;
use App\Ast\Expr\ThisExpr;
use App\Ast\Expr\Unary;
use App\Ast\Expr\Variable;
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
use App\Ast\Stmt\Variable as VariableStmt;
use App\Ast\Stmt\WhileLoop;

/**
 * Parse the tokens from the Scanner into an abstract syntax tree
 *
 * Supported expressions:
 *
 * Literals. Numbers, strings, Booleans, and nil.
 * Unary expressions. A prefix ! to perform a logical not, and - to negate a number.
 * Binary expressions. The infix arithmetic (+, -, *, /) and logic (==, !=, <, <=, >, >=) operators we know and love.
 * Parentheses for grouping.
 *
 * Grammar:
 *
 * program        → declaration* EOF ;
 *
 * declaration    → classDecl
 *                | funDecl
 *                | varDecl
 *                | statement
 *
 * classDecl      → "class" IDENTIFIER ( "<" IDENTIFIER )? "{" function* "}" ;
 * funDecl        → "fun" function ;
 * function       → IDENTIFIER "(" parameters? ")" block ;
 *
 * parameters     → IDENTIFIER ( "," IDENTIFIER )* ;
 *
 * varDecl        → "var" IDENTIFIER ( "=" expression )? ";" ;
 *
 * statement      → exprStmt
 *                | forStmt
 *                | ifStmt
 *                | printStmt
 *                | returnStmt
 *                | breakStmt
 *                | whileStmt
 *                | block;
 *
 * returnStmt     → "return" expression? ";" ;
 *
 * breakStmt      → "break;" ;
 *
 * forStmt        → "for" "(" ( varDecl | exprStmt | ";" )
 *                | expression? ";"
 *                | expression? ")" statement ;
 *
 * exprStmt       → expression ";" ; *
 * ifStmt         → "if" "(" expression ")" statement ( "else" statement )? ; * *
 * printStmt      → "print" expression ";" ;
 * whileStmt      → "while" "(" expression ")" statement ;
 * block          → "{" declaration "}" ; *
 *
 * expression     → assignment ;
 * assignment     → (call "." )? identifier "=" assignment
 *                | logic_or ;
 * logic_or       → logic_and ( "or" logic_and )* ;
 * logic_and      → equality ( "and" equality )* ;
 *
 * equality       → comparison ( ( "!=" | "==" ) comparison )* ;
 * comparison     → addition ( ( ">" | ">=" | "<" | "<=" ) addition )* ;
 * addition       → multiplication ( ( "-" | "+" ) multiplication )* ;
 * multiplication → unary ( ( "/" | "*" ) unary )* ;
 * unary          → ( "!" | "-" ) unary | call ;
 * call           → primary ( "(" arguments? ")" | "." IDENTIFIER )* ;
 * arguments      → expression ( "," expression )* ;
 * primary        → "false" | "true" | "nil" | "this"
 *                | NUMBER | STRING | IDENTIFIER
 *                | "(" expression ")"
 *                | "super" "." IDENTIFIER ;
 *
 */
class Parser
{

    private $tokens = [];
    private $current = 0;

    /**
     * @var ErrorReporter
     */
    private $reporter;

    /**
     * Parser constructor.
     *
     * @param array $tokens
     * @param ErrorReporter $reporter
     */
    public function __construct(array $tokens, ErrorReporter $reporter)
    {
        $this->tokens = $tokens;
        $this->reporter = $reporter;
    }

    public function parse(): array
    {
        $statements = [];
        while ( ! $this->isAtEnd()) {
            $statements[] = $this->declaration();
        }

        return $statements;
    }

    private function isAtEnd(): bool
    {
        return $this->peek()->getType() === TokenType::EOF;
    }

    private function peek(): Token
    {
        return $this->tokens[$this->current];
    }

    /**
     * declaration → classDecl | funDecl | varDecl | statement
     *
     * @return Stmt
     */
    private function declaration(): ?Stmt
    {
        try {
            if ($this->match(TokenType::CLASS_DEF)) {
                return $this->classDeclaration();
            }

            if ($this->match(TokenType::FUN)) {
                return $this->func("function");
            }

            if ($this->match(TokenType::VAR)) {
                return $this->varDeclaration();
            }

            return $this->statement();
        } catch (ParseError $error) {
            $this->synchronize();

            return null;
        }
    }

    private function match(string ...$tokenTypes): bool
    {
        foreach ($tokenTypes as $token) {
            if ($this->check($token)) {
                $this->advance();

                return true;
            }
        }

        return false;
    }

    private function check(string $tokenType): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        return $this->peek()->getType() === $tokenType;
    }

    private function advance()
    {
        if ( ! $this->isAtEnd()) {
            $this->current++;
        }

        return $this->previous();
    }

    private function previous(): Token
    {
        return $this->tokens[$this->current - 1];
    }

    /**
     * classDecl → "class" IDENTIFIER ( "<" IDENTIFIER )? "{" function* "}" ;
     *
     * @return ClassDecl
     */
    private function classDeclaration(): ClassDecl
    {
        $name = $this->consume(TokenType::IDENTIFIER, "Expect class name.");

        $superclass = null;
        if ($this->match(TokenType::LESS)) {
            $this->consume(TokenType::IDENTIFIER, "Expect superclass name.");
            $superclass = new Variable($this->previous());
        }

        $this->consume(TokenType::LEFT_BRACE, "Expect '{' before class body.");

        $methods = [];

        while ( ! $this->check(TokenType::RIGHT_BRACE) && ! $this->isAtEnd()) {
            $methods[] = $this->func("method");
        }

        $this->consume(TokenType::RIGHT_BRACE, "Expect '}' after class body.");

        return new ClassDecl($name, $superclass, $methods);
    }

    private function consume(string $tokenType, string $message)
    {
        if ($this->check($tokenType)) {
            return $this->advance();
        }

        throw $this->error($this->peek(), $message);
    }

    private function error(Token $token, $message): ParseError
    {
        $this->reporter->atToken($token, $message);

        return new ParseError();
    }

    /**
     * funDecl  → "fun" function ;
     * function → IDENTIFIER "(" parameters? ")" block ;
     *
     * @param string $type
     *
     * @return Stmt
     */
    private function func(string $type): Stmt
    {
        $name = $this->consume(TokenType::IDENTIFIER, "Expect $type name.");
        $this->consume(TokenType::LEFT_PAREN, "Expect '(' after $type name.");
        $params = [];
        if ( ! $this->check(TokenType::RIGHT_PAREN)) {
            do {
                if (count($params) >= 8) {
                    $this->error($this->peek(), "Cannot have more than 8 parameters.");
                }

                $params[] = $this->consume(TokenType::IDENTIFIER, "Expect parameter name.");
            } while ($this->match(TokenType::COMMA));
        }

        $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after parameters.");
        $this->consume(TokenType::LEFT_BRACE, "Expect '{' before $type body.");

        $body = $this->block();

        return new FunctionDecl($name, $params, $body);
    }

    /**
     * block → "{" declaration "}" ;
     *
     * @return array
     */
    private function block(): array
    {
        $statements = [];

        while ( ! $this->check(TokenType::RIGHT_BRACE) && ! $this->isAtEnd()) {
            $statements[] = $this->declaration();
        }

        $this->consume(TokenType::RIGHT_BRACE, "Expect '}' after block.");

        return $statements;
    }

    /**
     * varDecl → "var" IDENTIFIER ( "=" expression )? ";" ;
     *
     * @return VariableStmt
     */
    private function varDeclaration()
    {
        $name = $this->consume(TokenType::IDENTIFIER, "Expect variable name.");

        $initializer = null;
        if ($this->match(TokenType::EQUAL)) {
            $initializer = $this->expression();
        }

        $this->consume(TokenType::SEMICOLON, "Expect ';' after variable declaration.");

        return new VariableStmt($name, $initializer);
    }

    /**
     * expression → assignment ;
     *
     * @return Expr
     */
    private function expression(): Expr
    {
        return $this->assignment();
    }

    /**
     * assignment → (call "." )?  identifier "=" assignment | logic_or ;
     */
    private function assignment(): Expr
    {
        $expr = $this->logicOr();

        if ($this->match(TokenType::EQUAL)) {
            $equals = $this->previous();
            $value = $this->assignment();

            if ($expr instanceof VariableExpr) {
                return new Assign($expr->getName(), $value);
            } else if ($expr instanceof Get) {
                return new Set($expr->getObject(), $expr->getName(), $value);
            }

            $this->error($equals, "Invalid assignment target.");
        }

        return $expr;
    }

    /**
     *  logic_or → logic_and ( "or" logic_and )* ;
     */
    private function logicOr(): Expr
    {
        $expr = $this->logicAnd();

        while ($this->match(TokenType::LOGICAL_OR)) {
            $operator = $this->previous();
            $right = $this->logicAnd();
            $expr = new Logical($expr, $operator, $right);
        }

        return $expr;
    }

    /**
     * logic_and → equality ( "and" equality )* ;
     */
    private function logicAnd(): Expr
    {
        $expr = $this->equality();

        while ($this->match(TokenType::LOGICAL_AND)) {
            $operator = $this->previous();
            $right = $this->logicAnd();
            $expr = new Logical($expr, $operator, $right);
        }

        return $expr;
    }

    /**
     * equality → comparison ( ( "!=" | "==" ) comparison )* ;
     *
     * @return Expr
     */
    private function equality(): Expr
    {
        $expr = $this->comparison();

        while ($this->match(TokenType::EQUAL_EQUAL, TokenType::BANG_EQUAL)) {
            $operator = $this->previous();
            $right = $this->comparison();
            $expr = new Binary($expr, $operator, $right);
        }

        return $expr;
    }

    /**
     * comparison → addition ( ( ">" | ">=" | "<" | "<=" ) addition )* ;
     *
     * @return Expr
     */
    private function comparison(): Expr
    {
        $expr = $this->addition();

        while ($this->match(TokenType::GREATER, TokenType::GREATER_EQUAL, TokenType::LESS, TokenType::LESS_EQUAL)) {
            $operator = $this->previous();
            $right = $this->addition();
            $expr = new Binary($expr, $operator, $right);
        }

        return $expr;
    }

    /**
     * addition → multiplication ( ( "-" | "+" ) multiplication )* ;
     *
     * @return Expr
     */
    private function addition(): Expr
    {
        $expr = $this->multiplication();

        while ($this->match(TokenType::MINUS, TokenType::PLUS)) {
            $operator = $this->previous();
            $right = $this->multiplication();
            $expr = new Binary($expr, $operator, $right);
        }

        return $expr;
    }

    /**
     * multiplication → unary ( ( "/" | "*" ) unary )* ;
     *
     * @return Expr
     */
    private function multiplication(): Expr
    {
        $expr = $this->unary();

        while ($this->match(TokenType::SLASH, TokenType::STAR)) {
            $operator = $this->previous();
            $right = $this->unary();
            $expr = new Binary($expr, $operator, $right);
        }

        return $expr;
    }

    /**
     * unary → ( "!" | "-" ) unary | call ;
     *
     * @return Expr
     */
    private function unary(): Expr
    {
        if ($this->match(TokenType::BANG, TokenType::MINUS)) {
            $operator = $this->previous();
            $right = $this->unary();

            return new Unary($operator, $right);
        }

        return $this->call();
    }

    /**
     * call → primary ( "(" arguments? ")" | "." IDENTIFIER )* ;
     */
    private function call(): Expr
    {
        $expr = $this->primary();

        while (true) {
            if ($this->match(TokenType::LEFT_PAREN)) {
                $expr = $this->finishCall($expr);
            } else if ($this->match(TokenType::DOT)) {
                $name = $this->consume(TokenType::IDENTIFIER, "Expect property name after '.'.");
                $expr = new Get($expr, $name);
            } else {
                break;
            }
        }

        return $expr;
    }

    /**
     * primary → "true" | "false" | "null" | "this"
     *         | NUMBER | STRING | IDENTIFIER | "(" expression ")"
     *         | "super" "." IDENTIFIER ;
     *
     * @return Expr
     */
    private function primary(): Expr
    {
        if ($this->match(TokenType::THIS)) {
            return new ThisExpr($this->previous());
        }
        if ($this->match(TokenType::IDENTIFIER)) {
            return new VariableExpr($this->previous());
        }
        if ($this->match(TokenType::FALSE)) {
            return new Literal(false);
        }
        if ($this->match(TokenType::TRUE)) {
            return new Literal(true);
        }
        if ($this->match(TokenType::NIL)) {
            return new Literal(null);
        }
        if ($this->match(TokenType::NUMBER, TokenType::STRING)) {
            return new Literal($this->previous()->getLiteral());
        }
        if ($this->match(TokenType::SUPER)) {
            $keyword = $this->previous();
            $this->consume(TokenType::DOT, "Expect '.' after 'super'.");
            $method = $this->consume(TokenType::IDENTIFIER, "Expect superclass method name.");

            return new Super($keyword, $method);
        }
        if ($this->match(TokenType::LEFT_PAREN)) {
            $expr = $this->expression();
            $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after expression.");

            return new Grouping($expr);
        }

        throw $this->error($this->peek(), "Expect expression.");
    }

    private function finishCall(Expr $callee): Expr
    {
        $args = [];

        if ( ! $this->check(TokenType::RIGHT_PAREN)) {
            do {
                $args[] = $this->expression();
            } while ($this->match(TokenType::COMMA));

            if (count($args) > 8) {
                $this->error($this->previous(), "Cannot have more than 8 arguments.");
            }
        }

        $paren = $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after arguments.");

        return new Call($callee, $paren, $args);
    }

    /**
     * statement → exprStmt | breakStmt | ifStmt | printStmt | block | while | returnStmt ;
     *
     * @return Stmt
     */
    private function statement(): Stmt
    {
        if ($this->match(TokenType::BREAK)) {
            return $this->breakStatement();
        }

        if ($this->match(TokenType::FOR)) {
            return $this->forStatement();
        }

        if ($this->match(TokenType::WHILE)) {
            return $this->whileStatement();
        }

        if ($this->match(TokenType::IF)) {
            return $this->ifStatement();
        }

        if ($this->match(TokenType::PRINT)) {
            return $this->printStatement();
        }

        if ($this->match(TokenType::RETURN)) {
            return $this->returnStatement();
        }

        if ($this->match(TokenType::LEFT_BRACE)) {
            return new Block($this->block());
        }

        return $this->expressionStatement();
    }

    /**
     * breakStmt → "break;" ;
     *
     * @return Stmt
     */
    private function breakStatement(): Stmt
    {
        $keyword = $this->previous();
        $this->consume(TokenType::SEMICOLON, "Expect ';' after break.");
        return new BreakStmt($keyword);
    }

    /**
     * forStmt → "for" "(" ( varDecl | exprStmt | ";" )
     *         | expression? ";"
     *         | expression? ")" statement ;
     */
    private function forStatement()
    {
        $this->consume(TokenType::LEFT_PAREN, "Expect '(' after 'for'.");

        $initializer = $condition = $increment = null;

        // first clause is the initializer
        if ($this->match(TokenType::SEMICOLON)) {
            // initializer has been omitted
            $initializer = null;
        } else if ($this->match(TokenType::VAR)) {
            // standard variable declaration in the initializer
            $initializer = $this->varDeclaration();
        } else {
            // must be an expression at this point, but wrap it in a statement so
            // that the initializer is always a statement
            $initializer = $this->expressionStatement();
        }

        // next clause is the condition
        if ( ! $this->check(TokenType::SEMICOLON)) {
            $condition = $this->expression();
        }
        $this->consume(TokenType::SEMICOLON, "Expect ';' after loop condition.");

        if ( ! $this->check(TokenType::RIGHT_PAREN)) {
            $increment = $this->expression();
        }
        $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after clauses.");

        $body = $this->statement();

        if ($increment !== null) {
            $body = new Block([
                $body,
                new Expression($increment)
            ]);
        }
        if ($condition === null) {
            $condition = new Literal(true);
        }

        $body = new WhileLoop($condition, $body);

        if ($initializer !== null) {
            $body = new Block([
                $initializer,
                $body
            ]);
        }

        return $body;
    }

    /**
     * exprStmt → expression ";" ;
     */
    private function expressionStatement(): Expression
    {
        $value = $this->expression();
        $this->consume(TokenType::SEMICOLON, "Expect ';' after expression.");

        return new Expression($value);
    }

    /**
     * whileStmt →  "while" "(" expression ")" statement ;
     *
     * @return WhileLoop
     */
    private function whileStatement(): WhileLoop
    {
        $this->consume(TokenType::LEFT_PAREN, "Expect '(' after 'while'.");
        $condition = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after condition.");
        $body = $this->statement();

        return new WhileLoop($condition, $body);
    }

    /**
     * ifStmt → "if" "(" expression ")" statement ( "else" statement )? ;
     *
     * @return Conditional
     */
    private function ifStatement(): Conditional
    {
        $this->consume(TokenType::LEFT_PAREN, "Expect '(' after 'if'.");
        $condition = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, "Expect ')' after if condition.");

        $then = $this->statement();
        $else = null;
        if ($this->match(TokenType::ELSE)) {
            $else = $this->statement();
        }

        return new Conditional($condition, $then, $else);
    }

    /**
     * printStmt → "print" expression ";" ;
     */
    private function printStatement(): Prnt
    {
        $value = $this->expression();

        $this->consume(TokenType::SEMICOLON, "Expect ';' after expression.");

        return new Prnt($value);
    }

    /**
     * returnStmt → "return" expression? ";" ;
     */
    function returnStatement(): Stmt
    {
        $keyword = $this->previous();
        $value = null;
        if ( ! $this->check(TokenType::SEMICOLON)) {
            $value = $this->expression();
        }

        $this->consume(TokenType::SEMICOLON, "Expect ';' after return value.");

        return new FnReturn($keyword, $value);
    }

    /**
     * Try to recover from a parse error by discarding tokens until we've found
     * the beginning of an other statement - where it makes sense to begin parsing again
     */
    private function synchronize()
    {
        $this->advance();

        while ( ! $this->isAtEnd()) {
            if ($this->previous()->getType() === TokenType::SEMICOLON) {
                return;
            }

            switch ($this->peek()->getType()) {
                case TokenType::CLASS_DEF:
                case TokenType::FUN:
                case TokenType::VAR:
                case TokenType::FOR:
                case TokenType::IF:
                case TokenType::WHILE:
                case TokenType::PRINT:
                case TokenType::RETURN:
                    return;
            }

            $this->advance();
        }
    }
}