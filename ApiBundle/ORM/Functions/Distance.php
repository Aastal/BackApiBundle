<?php

namespace AppBundle\ORM\Functions;

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
        $earthDiameterInKM = 1.609344 * 3956 * 2;

        $sql = '('.$earthDiameterInKM.' * ASIN(SQRT(POWER(' .
            'SIN(('.$this->fromLat->dispatch($sqlWalker).' - ABS('.$this->toLat->dispatch($sqlWalker).')) * PI() / 180 / 2), 2) + ' .
            'COS('.$this->fromLat->dispatch($sqlWalker).' * PI() / 180) * COS(ABS('.$this->toLat->dispatch($sqlWalker).') * PI() / 180) * ' .
            'POWER(SIN(('.$this->fromLng->dispatch($sqlWalker).' - '.$this->toLng->dispatch($sqlWalker).') * PI() / 180 / 2), 2) ' .
            ')))';

        return $sql;
    }
}