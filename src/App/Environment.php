<?php declare(strict_types=1);

namespace App;

/**
 * Represent the bindings between variables and their names
 */
class Environment
{
    private $values = [];

    /**
     * @var Environment
     */
    private $enclosing;

    public function __construct(?Environment $enclosing = null)
    {
        $this->enclosing = $enclosing;
    }

    /**
     * @return Environment
     */
    public function getEnclosing(): Environment
    {
        return $this->enclosing;
    }

    public function define(string $name, $value): void
    {
        $this->values[$name] = $value;
    }

    public function get(Token $name)
    {
        if (array_key_exists($name->getLexeme(), $this->values)) {
            return $this->values[$name->getLexeme()];
        }

        if ($this->enclosing !== null) {
            return $this->enclosing->get($name);
        }

        throw new RuntimeError($name, "Undefined variable '{$name->getLexeme()}'.");
    }

    public function getAt(int $distance, string $name)
    {
        return $this->ancestor($distance)->values[$name];
    }

    public function assignAt(int $distance, Token $name, $value)
    {
        $this->ancestor($distance)->values[$name->getLexeme()] = $value;
    }

    /**
     * Get the environment at the given distance away
     *
     * @param int $distance
     *
     * @return Environment
     */
    private function ancestor(int $distance): Environment
    {
        $env = $this;
        for ($i = 0; $i < $distance; $i++) {
            $env = $env->enclosing;
        }

        return $env;
    }

    public function assign(Token $name, $value): void
    {
        if (array_key_exists($name->getLexeme(), $this->values)) {
            $this->values[$name->getLexeme()] = $value;

            return;
        }

        if ($this->enclosing !== null) {
            $this->enclosing->assign($name, $value);

            return;
        }

        throw new RuntimeError($name, "Undefined variable '{$name->getLexeme()}'.");
    }
}