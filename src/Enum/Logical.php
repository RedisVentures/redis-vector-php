<?php

namespace Vladvildanov\PredisVl\Enum;

use Vladvildanov\PredisVl\Enum\Traits\EnumNames;

enum Logical: string
{
    use EnumNames;

    case and = '&&';
    case or = '||';
}
