<?php

namespace Vladvildanov\PredisVl\Enum;

use Vladvildanov\PredisVl\Enum\Traits\EnumNames;

enum Unit: string
{
    use EnumNames;

    case kilometers = 'km';
    case meters = 'm';
    case miles = 'mi';
    case foots = 'ft';
}
