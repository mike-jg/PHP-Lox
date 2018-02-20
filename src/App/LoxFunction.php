<?php declare(strict_types=1);

namespace App;

use App\Ast\Stmt\FunctionDecl;

class LoxFunction implements LoxCallable
{

    /**
     * @var FunctionDecl
     */
    private $declaration;

    /**
     * @var Environment
     */
    private $closure;

    private $isConstructor;

    /**
     * LoxFunction constructor.
     *
     * @param FunctionDecl $declaration
     * @param Environment $closure
     * @param bool $isConstructor
     */
    public function __construct(?FunctionDecl $declaration, ?Environment $closure, bool $isConstructor)
    {
        $this->declaration = $declaration;
        $this->closure = $closure;
        $this->isConstructor = $isConstructor;
    }

    public function bind(LoxInstance $instance)
    {
        $env = new Environment($this->closure);
        $env->define("this", $instance);

        return new LoxFunction($this->declaration, $env, $this->isConstructor);
    }

    /**
     * @return Environment
     */
    public function getClosure(): Environment
    {
        return $this->closure;
    }

    public function arity(): int
    {
        return count($this->declaration->getParameters());
    }

    public function call(Interpreter $interpreter, array $arguments)
    {
        $env = new Environment($this->closure);
        $params = $this->declaration->getParameters();

        for ($i = 0, $c = count($params); $i < $c; $i++) {
            $env->define($params[$i]->getLexeme(), $arguments[$i]);
        }

        try {
            $interpreter->executeBlock($this->declaration->getBody(), $env);
        } catch (ReturnValue $return) {
            return $return->getValue();
        }

        // constructor always returns 'this'
        if ($this->isConstructor) {
            return $this->closure->getAt(0, "this");
        }

        return null;
    }

    public function __toString(): string
    {
        return "<fn " . $this->declaration->getName()->getLexeme() . ">";
    }
}