<?php declare(strict_types=1);

namespace AppTest;

use App\Output;

class TestOutput implements Output
{
    private $prints = [];
    private $errors = [];

    public function print(string $message)
    {
        $this->prints[] = $message;
    }

    public function printError(string $message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return array
     */
    public function getPrints(): array
    {
        return $this->prints;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}