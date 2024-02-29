<?php

namespace RedisVentures\RedisVl\Enum;

use RedisVentures\RedisVl\Enum\Traits\EnumNames;

enum Condition: string
{
    use EnumNames;

    case equal = '==';
    case notEqual = '!=';
    case greaterThan = '>';
    case greaterThanOrEqual = '>=';
    case lowerThan = '<';
    case lowerThanOrEqual = '<=';
    case pattern = '%';
    case between = 'between';
}
