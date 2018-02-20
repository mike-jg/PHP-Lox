<?php declare(strict_types=1);

namespace App;

class LoxInstance
{

    /**
     * @var LoxClass
     */
    private $class;

    private $fields = [];

    /**
     * LoxInstance constructor.
     *
     * @param LoxClass $class
     */
    public function __construct(LoxClass $class)
    {
        $this->class = $class;
    }

    public function get(Token $name)
    {
        if (array_key_exists($name->getLexeme(), $this->fields)) {
            return $this->fields[$name->getLexeme()];
        }

        $method = $this->class->findMethod($this, $name->getLexeme());
        if ($method !== null) {
            return $method;
        }

        throw new RuntimeError($name, "Undefined property '" . $name->getLexeme() . "'.");
    }

    public function set(Token $name, $value)
    {
        $this->fields[$name->getLexeme()] = $value;
    }

    public function __toString(): string
    {
        return $this->class->getName() . " instance";
    }

}