<?php

namespace RedisVentures\PredisVl\Enum;

use RedisVentures\PredisVl\Enum\Traits\EnumNames;

enum Logical: string
{
    use EnumNames;

    case and = '&&';
    case or = '||';
}
