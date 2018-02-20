<?php declare(strict_types=1);

namespace App;

class Token
{
    private $type;

    /**
     * Lexeme is a meaningful sequence of characters found in the source code
     *
     * e.g. var (a keyword)
     * e.g. language (an identifier)
     * e.g. "test" (a string literal)
     *
     * @var string
     */
    private $lexeme;

    /**
     * Literal is the literal value
     *
     * e.g. for "test" the literal would be the string test
     *
     * @var mixed
     */
    private $literal;
    private $line;

    /**
     * Token constructor.
     *
     * @param $type
     * @param $lexeme
     * @param $literal
     * @param $line
     */
    public function __construct(string $type, string $lexeme, $literal, int $line)
    {
        $this->type = $type;
        $this->lexeme = $lexeme;
        $this->literal = $literal;
        $this->line = $line;
    }

    /**
     * @return mixed
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLexeme(): string
    {
        return $this->lexeme;
    }

    /**
     * @return mixed
     */
    public function getLiteral()
    {
        return $this->literal;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    public function __toString(): string
    {
        return sprintf("%s %s %s", $this->type, $this->lexeme, $this->literal);
    }

}