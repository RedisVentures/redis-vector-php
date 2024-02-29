<?php

namespace RedisVentures\RedisVl\Enum;

enum StorageType: string
{
    case hash = 'HASH';
    case json = 'JSON';
}
