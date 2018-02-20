<?php

namespace App;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class PrintOutput implements Output
{

    private $consoleOutput;
    private $formatter;

    /**
     * PrintOutput constructor.
     */
    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
        $this->formatter = new OutputFormatter();
    }

    public function print(string $message)
    {
        $message = str_replace('\n', PHP_EOL, $message);
        $this->consoleOutput->write($message);
    }

    public function printError(string $message)
    {
        $message = str_replace('\n', PHP_EOL, $message);
        $escapedMsg = $this->formatter->escape($message);
        $this->consoleOutput->write("<error>$escapedMsg</error>");
    }

}