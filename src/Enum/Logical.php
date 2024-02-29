<?php

namespace RedisVentures\RedisVl\Enum;

use RedisVentures\RedisVl\Enum\Traits\EnumNames;

enum Logical: string
{
    use EnumNames;

    case and = '&&';
    case or = '||';
}
