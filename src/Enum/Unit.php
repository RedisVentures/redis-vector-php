<?php

namespace RedisVentures\PredisVl\Enum;

use RedisVentures\PredisVl\Enum\Traits\EnumNames;

enum Unit: string
{
    use EnumNames;

    case kilometers = 'km';
    case meters = 'm';
    case miles = 'mi';
    case foots = 'ft';
}
