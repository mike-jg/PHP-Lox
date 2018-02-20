<?php declare(strict_types=1);

namespace App;

use App\Ast\Expr\Binary;
use App\Ast\Expr\Grouping;
use App\Ast\Expr\Literal;
use App\Ast\Expr\Unary;

final class Lox
{

    private function __construct()
    {
    }

    public static function main(array $args)
    {
        $output = new PrintOutput();

        if (count($args) === 1) {
            return self::repl();
        } else if (count($args) === 2) {
            if (file_exists($args[1]) && is_readable($args[1])) {
                return self::run(file_get_contents($args[1]));
            }
            $output->printError("Invalid input file specified: '" . $args[1] . "'\n");
        }

        $output->print("Usage: ./bin/lox\n");
        $output->print("Usage: ./bin/lox [filename]\n");

        return 1;
    }

    private static function repl()
    {
        $errorReporter = new BufferedErrorReporter();
        $output = new PrintOutput();
        $interpreter = new Interpreter($output, $errorReporter);
        $resolver = new Resolver($interpreter, $errorReporter);
        $line = "";

        $output->print("Lox REPL, type quit to quit.\n");

        while (true) {
            $output->print("> ");
            $line .= readline();
            if (in_array($line, ["quit", "exit", "q", ":q"])) {
                break;
            }

            readline_add_history($line);

            $scanner = new Scanner($line, $errorReporter);
            $tokens = $scanner->scanTokens();

            // ensure full statements and control structures/class definitions
            //
            // a positive, matching number of opening/closing braces would
            // indicate a complete control structure
            if (($leftBraces = self::countTokens($tokens, TokenType::LEFT_BRACE)) > 0) {
                if ($leftBraces !== self::countTokens($tokens, TokenType::RIGHT_BRACE)) {
                    continue;
                }
            } // otherwise wait for a full statement (a line ending in a semicolon)
            else if ($tokens[count($tokens) - 2]->getType() !== TokenType::SEMICOLON) {
                continue;
            }

            $parser = new Parser($tokens, $errorReporter);
            $statements = $parser->parse();

            if ($errorReporter->hadError()) {
                $output->printError(implode("\n", $errorReporter->getErrorBuffer()));
                $output->print("\n");
                $errorReporter = new BufferedErrorReporter();
                $line = "";
                continue;
            }

            $resolver->resolve($statements);

            if ($errorReporter->hadError()) {
                $output->printError(implode("\n", $errorReporter->getErrorBuffer()));
                $output->print("\n");
                $errorReporter = new BufferedErrorReporter();
                $line = "";
                continue;
            }

            $interpreter->interpret($statements);

            if ($errorReporter->hadRuntimeError()) {
                $output->printError(implode("\n", $errorReporter->getErrorBuffer()));
                $output->print("\n");
                $errorReporter = new BufferedErrorReporter();
                $line = "";
                continue;
            }

            $line = "";
        }

        $output->print("Bye!\n");

        return 0;
    }

    private static function countTokens(array $tokens, $type)
    {
        return count(array_filter($tokens, function (Token $token) use ($type) {
            return $token->getType() === $type;
        }));
    }

    private static function run(string $code)
    {
        $errorReporter = new BufferedErrorReporter();
        $output = new PrintOutput();

        $scanner = new Scanner($code, $errorReporter);
        $tokens = $scanner->scanTokens();

        $parser = new Parser($tokens, $errorReporter);
        $statements = $parser->parse();

        if ($errorReporter->hadError()) {
            $output->printError(implode("\n", $errorReporter->getErrorBuffer()));
            $output->print("\n");

            return 1;
        }

        $interpreter = new Interpreter($output, $errorReporter);

        $resolver = new Resolver($interpreter, $errorReporter);
        $resolver->resolve($statements);

        if ($errorReporter->hadError()) {
            $output->printError(implode("\n", $errorReporter->getErrorBuffer()));
            $output->print("\n");

            return 1;
        }

        $interpreter->interpret($statements);

        if ($errorReporter->hadRuntimeError()) {
            $output->printError(implode("\n", $errorReporter->getErrorBuffer()));
            $output->print("\n");

            return 1;
        }

        return 0;
    }

}