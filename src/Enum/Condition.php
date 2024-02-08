<?php

namespace Vladvildanov\PredisVl\Enum;

use Vladvildanov\PredisVl\Enum\Traits\EnumNames;

enum Condition: string
{
    use EnumNames;

    case equal = '==';
    case notEqual = '!=';
    case greaterThan = '>';
    case lowerThan = '<';
    case pattern = '%';
}