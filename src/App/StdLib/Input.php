<?php declare(strict_types=1);

namespace App\StdLib;

use App\LoxClass;
use App\Output;

class Input extends LoxClass
{

    private $output;

    /**
     * @inheritDoc
     */
    public function __construct(Output $output)
    {
        $this->output = $output;
        parent::__construct("Input", null, [
            "string" => new StdClassMethod([$this, "string"], 1),
            "number" => new StdClassMethod([$this, "number"], 1)
        ]);
    }

    public function string(string $message): string
    {
        $this->output->print(" > $message ");
        return readline();
    }

    public function number(string $message): float
    {
        return (double) $this->string($message);
    }

}