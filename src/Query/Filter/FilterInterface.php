<?php

namespace RedisVentures\PredisVl\Query\Filter;

interface FilterInterface
{
    /**
     * Returns expression-string representation of current filter.
     *
     * @return string
     */
    public function toExpression(): string;
}