<?php

namespace AppTest;

use App\BufferedErrorReporter;
use App\Interpreter;
use App\Parser;
use App\Resolver;
use App\Scanner;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class IntegrationTest extends TestCase
{

    const TEST_DIR = __DIR__ . "/../../test";

    /**
     *
     */
    public function testAllIntegrations()
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                static::TEST_DIR
            )
        );

        /* @var $f \SplFileInfo */
        foreach ($files as $f) {
            $this->runTestsInFile($f);
        }
    }

    private function runTestsInFile(\SplFileInfo $file)
    {
        if ($file->getExtension() !== "lox") {
            return;
        }

        $expectedErrors = [];
        $expectedOutput = [];

        $code = file_get_contents($file->getPathname());

        $errorExpect = "|// (Error.*)|";
        $syntaxErrorExpect = "|\[.*line (\d+)\] (Error.+)|";
        $expect = "|// expect: ?(.*)|";
        $expectRuntimeError = "|// expect runtime error: ?(.*)|";

        $lineNumber = 1;
        $assertions = 0;
        $assertionsRun = 0;
        foreach (explode("\n", $code) as $line) {
            if (preg_match($syntaxErrorExpect, $line, $matches)) {
                $expectedErrors[] = $matches[0];
                $assertions++;
            } else if (preg_match($expect, $line, $matches)) {
                $expectedOutput[] = $matches[1];
                $assertions++;
            } else if (preg_match($expectRuntimeError, $line, $matches)) {
                $expectedErrors[] = $matches[1] . "\n[line $lineNumber]";
                $assertions++;
            }else if (preg_match($errorExpect, $line, $matches)) {
                $expectedErrors[] = "[line $lineNumber] " . $matches[1];
                $assertions++;
            }
            $lineNumber++;
        }

        $errorReporter = new BufferedErrorReporter();
        $output = new TestOutput();

        $scanner = new Scanner($code, $errorReporter);
        $tokens = $scanner->scanTokens();

        $parser = new Parser($tokens, $errorReporter);
        $statements = $parser->parse();

        $interpreter = new Interpreter($output, $errorReporter);

        $resolver = new Resolver($interpreter, $errorReporter);
        if ( ! $errorReporter->hadError()) {
            $resolver->resolve($statements);
            if ( ! $errorReporter->hadError()) {
                $interpreter->interpret($statements);
            }
        }

        $printStatements = $output->getPrints();

        while ($expectedOutput) {
            $actual = array_shift($printStatements);
            $expected = array_shift($expectedOutput);
            $this->assertEquals($expected, $actual, $file->getPathname());
            $assertionsRun++;
        }

        $errorStatements = $errorReporter->getErrorBuffer();

        while ($expectedErrors) {
            $actual = array_shift($errorStatements);
            $expected = array_shift($expectedErrors);
            $this->assertEquals($expected, $actual, $file->getPathname());
            $assertionsRun++;
        }

        if ($assertions !== $assertionsRun) {
            $this->assertSame($assertions, $assertionsRun, "Not all assertions have been run.");
        }
        if ($assertions === 0) {
            $this->fail("No assertions found in file " . $file->getPathname() . ".");
        }
    }

}