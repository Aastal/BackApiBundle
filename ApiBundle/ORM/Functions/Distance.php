<?php

namespace Geoks\ApiBundle\ORM\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * "DISTANCE" "(" LatitudeFrom, LongitudeFrom, LatitudeTo, LongitudeTo ")"
 */
class Distance extends FunctionNode
{
    protected $fromLat;
    protected $fromLng;
    protected $toLat;
    protected $toLng;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fromLat = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->fromLng = $parser->SimpleArithmeticExpression();
        $parser->match(Lexer::T_COMMA);

        $this->toLat = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);

        $this->toLng = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        // In Meters
        return sprintf(
            '((ACOS(SIN(%s * PI() / 180) * SIN(%s * PI() / 180) + COS(%s * PI() / 180) * COS(%s * PI() / 180)' .
            ' * COS((%s - %s) * PI() / 180)) * 180 / PI()) * 60 * %s)',
            $this->fromLat->dispatch($sqlWalker),
            $this->toLat->dispatch($sqlWalker),
            $this->fromLat->dispatch($sqlWalker),
            $this->toLat->dispatch($sqlWalker),
            $this->fromLng->dispatch($sqlWalker),
            $this->toLng->dispatch($sqlWalker),
            '1.1515 * 1609.34'
        );
    }
}
