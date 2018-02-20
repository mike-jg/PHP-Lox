<?php declare(strict_types=1);

namespace App;

final class TokenType
{
    const LEFT_PAREN = "LEFT_PAREN";
    const RIGHT_PAREN = "RIGHT_PAREN";
    const LEFT_BRACE = "LEFT_BRACE";
    const RIGHT_BRACE = "RIGHT_BRACE";
    const COMMA = "COMMA";
    const DOT = "DOT";
    const MINUS = "MINUS";
    const PLUS = "PLUS";
    const SEMICOLON = "SEMICOLON";
    const SLASH = "SLASH";
    const STAR = "STAR";
    const BANG = "BANG";
    const BANG_EQUAL = "BANG_EQUAL";
    const EQUAL = "EQUAL";
    const EQUAL_EQUAL = "EQUAL_EQUAL";
    const GREATER = "GREATER";
    const GREATER_EQUAL = "GREATER_EQUAL";
    const LESS = "LESS";
    const LESS_EQUAL = "LESS_EQUAL";
    const IDENTIFIER = "IDENTIFIER";
    const STRING = "STRING";
    const NUMBER = "NUMBER";
    const LOGICAL_AND = "AND";
    const CLASS_DEF = "CLASS_DEF";
    const ELSE = "ELSE";
    const FALSE = "FALSE";
    const FUN = "FUN";
    const FOR = "FOR";
    const IF = "IF";
    const NIL = "NIL";
    const LOGICAL_OR = "OR";
    const PRINT = "PRINT";
    const RETURN = "RETURN";
    const BREAK = "BREAK";
    const SUPER = "SUPER";
    const THIS = "THIS";
    const TRUE = "TRUE";
    const VAR = "VAR";
    const WHILE = "WHILE";
    const EOF = "EOF";

    private function __construct()
    {
    }
}