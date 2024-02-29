<?php

namespace RedisVentures\RedisVl\Enum;

use RedisVentures\RedisVl\Enum\Traits\EnumNames;

enum Unit: string
{
    use EnumNames;

    case kilometers = 'km';
    case meters = 'm';
    case miles = 'mi';
    case foots = 'ft';
}
