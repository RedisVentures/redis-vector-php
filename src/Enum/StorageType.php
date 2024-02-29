<?php

namespace RedisVentures\PredisVl\Enum;

enum StorageType: string
{
    case hash = 'HASH';
    case json = 'JSON';
}
