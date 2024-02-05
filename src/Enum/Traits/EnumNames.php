<?php

namespace Vladvildanov\PredisVl\Enum\Traits;

trait EnumNames
{
    /**
     * @return array
     */
    public static function names(): array
    {
        return array_map(static fn($enum) => $enum->name, static::cases());
    }

    /**
     * @param string $name
     * @return self
     */
    public static function fromName(string $name): self
    {
        return constant("self::$name");
    }
}
