<?php

namespace Geoks\ApiBundle\Utils;

class CalcUtil
{
    public function unitConvert($fromValue, $fromUnit, $toUnit)
    {
        switch ($fromUnit)
        {
            case "ml":
                if ($toUnit == "cl") {
                    $result = $fromValue / 10;
                } elseif ($toUnit == "l") {
                    $result = $fromValue / 1000;
                } else {
                    $result = $fromValue;
                }
                break;
            case "cl":
                if ($toUnit == "ml") {
                    $result = $fromValue * 10;
                } elseif ($toUnit == "l") {
                    $result = $fromValue / 100;
                } else {
                    $result = $fromValue;
                }
                break;
            case "l":
                if ($toUnit == "ml") {
                    $result = $fromValue * 1000;
                } elseif ($toUnit == "cl") {
                    $result = $fromValue * 100;
                } else {
                    $result = $fromValue;
                }
                break;
            case "mg":
                if ($toUnit == "g") {
                    $result = $fromValue / 1000;
                } else {
                    $result = $fromValue;
                }
                break;
            case "g":
                if ($toUnit == "mg") {
                    $result = $fromValue * 1000;
                } else {
                    $result = $fromValue;
                }
                break;
            default;
                $result = null;
                break;
        }

        return $result;
    }
}