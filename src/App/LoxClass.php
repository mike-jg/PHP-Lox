<?php declare(strict_types=1);

namespace App;

class LoxClass implements LoxCallable
{

    private $name;

    /**
     * @var LoxFunction[]
     */
    private $methods;

    private $superclass;

    /**
     * LoxClass constructor.
     *
     * @param string $name
     * @param LoxClass|null $superclass
     * @param array $methods
     */
    public function __construct(string $name, ?LoxClass $superclass, array $methods)
    {
        $this->name = $name;
        $this->superclass = $superclass;
        $this->methods = $methods;
    }

    /**
     * @return LoxFunction
     */
    private function getConstructor(): ?LoxFunction
    {
        return isset($this->methods["init"]) ? $this->methods["init"] : null;
    }

    public function arity(): int
    {
        $constructor = $this->getConstructor();
        if ($constructor === null) {
            return 0;
        }

        return $constructor->arity();
    }


    public function call(Interpreter $interpreter, array $arguments)
    {
        $inst = new LoxInstance($this);

        // check for constructor
        $constructor = $this->getConstructor();
        if ($constructor !== null) {
            $constructor->bind($inst)->call($interpreter, $arguments);
        }

        return $inst;
    }

    public function findMethod(LoxInstance $instance, string $name): ?LoxFunction
    {
        if (array_key_exists($name, $this->methods)) {
            return $this->methods[$name]->bind($instance);
        }

        if ($this->superclass !== null) {
            return $this->superclass->findMethod($instance, $name);
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}