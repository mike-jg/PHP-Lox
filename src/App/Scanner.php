<?php declare(strict_types=1);

namespace App;

/**
 * Tokenize the source code
 */
class Scanner
{

    private static $keywords = [
        "and"    => TokenType::LOGICAL_AND,
        "class"  => TokenType::CLASS_DEF,
        "else"   => TokenType::ELSE,
        "false"  => TokenType::FALSE,
        "for"    => TokenType::FOR,
        "fun"    => TokenType::FUN,
        "if"     => TokenType::IF,
        "nil"    => TokenType::NIL,
        "or"     => TokenType:: LOGICAL_OR,
        "print"  => TokenType::PRINT,
        "return" => TokenType::RETURN,
        "super"  => TokenType::SUPER,
        "this"   => TokenType::THIS,
        "true"   => TokenType::TRUE,
        "var"    => TokenType::VAR,
        "while"  => TokenType::WHILE,
        "break"  => TokenType::BREAK
    ];

    /**
     * Application source code
     *
     * @var string
     */
    private $source;

    /**
     * @var Token[]
     */
    private $tokens = [];

    /**
     * Start of current lexeme in byte-position
     *
     * @var int
     */
    private $start = 0;

    /**
     * Current pointer position
     *
     * e.g. which byte we are looking at in the source
     *
     * @var int
     */
    private $current = 0;

    /**
     * Current line in the source code
     *
     * @var int
     */
    private $line = 1;

    /**
     * @var ErrorReporter
     */
    private $reporter;

    /**
     * Scanner constructor.
     *
     * @param string $source
     * @param ErrorReporter $reporter
     */
    public function __construct(string $source, ErrorReporter $reporter)
    {
        $this->source = $source;
        $this->reporter = $reporter;
    }

    /**
     * @return Token[]
     */
    public function scanTokens(): array
    {
        while ( ! $this->isAtEnd()) {
            $this->start = $this->current;
            $this->scanToken();
        }

        $this->tokens[] = new Token(TokenType::EOF, "", null, $this->line);

        return $this->tokens;
    }

    /**
     * Scan the next token
     */
    private function scanToken()
    {
        $char = $this->advance();
        switch ($char) {
            case " ":
            case "\r":
            case "\t":
                break;

            case "\n":
                $this->line++;
                break;

            case "(":
                $this->addToken(TokenType::LEFT_PAREN);
                break;

            case ")":
                $this->addToken(TokenType::RIGHT_PAREN);
                break;

            case "{":
                $this->addToken(TokenType::LEFT_BRACE);
                break;

            case "}":
                $this->addToken(TokenType::RIGHT_BRACE);
                break;

            case ",":
                $this->addToken(TokenType::COMMA);
                break;

            case ".":
                $this->addToken(TokenType::DOT);
                break;

            case "-":
                $this->addToken(TokenType::MINUS);
                break;

            case "+":
                $this->addToken(TokenType::PLUS);
                break;

            case ";":
                $this->addToken(TokenType::SEMICOLON);
                break;

            case "*":
                $this->addToken(TokenType::STAR);
                break;

            case "!":
                $this->addToken(
                    $this->match("=") ? TokenType::BANG_EQUAL : TokenType::BANG
                );
                break;

            case "=":
                $this->addToken(
                    $this->match("=") ? TokenType::EQUAL_EQUAL : TokenType::EQUAL
                );
                break;

            case "<":
                $this->addToken(
                    $this->match("=") ? TokenType::LESS_EQUAL : TokenType::LESS
                );
                break;

            case ">":
                $this->addToken(
                    $this->match("=") ? TokenType::GREATER_EQUAL : TokenType::GREATER
                );
                break;

            case "/":
                if ($this->match("/")) {
                    // single line comment //
                    // keep throwing away chars until we reach a newline and the comment ends
                    while ($this->peek() !== "\n" && ! $this->isAtEnd()) {
                        $this->advance();
                    }
                } else if ($this->match("*")) {
                    // block comment
                    // keep throwing away chars until we find a closing comment */
                    while ($this->peek() !== "*" && $this->peekNext() !== "/" && ! $this->isAtEnd()) {
                        $this->advance();
                        if (substr($this->source, $this->current, 1) === "\n") {
                            $this->line++;
                        }
                    }

                    if ($this->isAtEnd()) {
                        $this->reporter->atLine($this->line, "Unterminated block comment.");
                    } else {
                        // throw away the closing */
                        $this->advance();
                        $this->advance();
                    }

                } else {
                    $this->addToken(TokenType::SLASH);
                }
                break;

            case '"':
                $this->string();
                break;

            default:
                if ($this->isDigit($char)) {
                    $this->number();
                } else if ($this->isAlpha($char)) {
                    $this->identifier();
                } else {
                    $this->reporter->atLine($this->line, sprintf("Unexpected character: '%s'", $char));
                }
                break;
        }
    }

    private function isAlpha(string $char): bool
    {
        return preg_match("/^([a-z]|[A-Z]|_)+$/", $char) === 1;
    }

    private function isAlphaNumeric(string $char): bool
    {
        return $this->isDigit($char) || $this->isAlpha($char);
    }

    /**
     * consume an identifier
     */
    private function identifier()
    {
        while ($this->isAlphaNumeric($this->peek())) {
            $this->advance();
        }

        $text = substr($this->source, $this->start, $this->current - $this->start);
        $type = TokenType::IDENTIFIER;

        if (array_key_exists($text, self::$keywords)) {
            $type = self::$keywords[$text];
        }

        $this->addToken($type);
    }

    /**
     * consume a string
     */
    private function string()
    {
        while ($this->peek() !== '"' && ! $this->isAtEnd()) {
            if ($this->peek() === "\n") {
                $this->line++;
            }

            $this->advance();
        }

        if ($this->isAtEnd()) {
            $this->reporter->atLine($this->line, "Unterminated string.");

            return;
        }

        // consume the closing quote
        $this->advance();

        // trim the surrounding quotes
        $stringValue = substr($this->source, $this->start + 1, $this->current - $this->start - 2);

        $this->addToken(TokenType::STRING, $stringValue);
    }

    private function isDigit($char)
    {
        return preg_match("/^[0-9]+$/", $char) === 1;
    }

    private function number()
    {
        while ($this->isDigit($this->peek())) {
            $this->advance();
        }

        // fractional part
        if ($this->peek() === "." && $this->isDigit($this->peekNext())) {

            // consume the .
            $this->advance();

            while ($this->isDigit($this->peek())) {
                $this->advance();
            }
        }

        $this->addToken(TokenType::NUMBER, (double)substr($this->source, $this->start, $this->current - $this->start));
    }

    /**
     * Add a new token to the stream
     *
     * @param string $type
     * @param null $literal
     */
    private function addToken(string $type, $literal = null)
    {
        $text = substr($this->source, $this->start, $this->current - $this->start);
        $this->tokens[] = new Token($type, $text, $literal, $this->line);
    }

    /**
     * Advance the scanner and return the current char
     *
     * @return string
     */
    private function advance(): string
    {
        return substr($this->source, $this->current++, 1);
    }

    /**
     * Has the source end been reached?
     *
     * @return bool
     */
    private function isAtEnd(): bool
    {
        return $this->current >= strlen($this->source);
    }

    /**
     * Check whether the current char matches $expectedChar
     *
     * If it does then consume it
     *
     * @param string $expectedChar
     *
     * @return bool
     */
    private function match(string $expectedChar): bool
    {
        if ($this->isAtEnd()) {
            return false;
        }

        if (substr($this->source, $this->current, 1) !== $expectedChar) {
            return false;
        }

        $this->current++;

        return true;
    }

    /**
     * Return the next char without advancing the pointer
     *
     * @return string
     */
    private function peek(): string
    {
        if ($this->isAtEnd()) {
            return "\0";
        }

        return substr($this->source, $this->current, 1);
    }

    private function peekNext(): string
    {
        if ($this->current + 1 >= strlen($this->source)) {
            return "\0";
        }

        return substr($this->source, $this->current + 1, 1);
    }

}