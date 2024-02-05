<?php

namespace Vladvildanov\PredisVl;

class VectorHelper
{
    /**
     * Convert vector represented as an array of floats into bytes string representation.
     *
     * @param float[] $vector
     * @return string
     */
    public static function toBytes(array $vector): string
    {
        return pack('f*', ...$vector);
    }
}