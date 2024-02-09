<?php

namespace Vladvildanov\PredisVl\Query\Filter;

use Vladvildanov\PredisVl\Enum\Condition;

interface FilterInterface
{
    /**
     * Creates field-based filter.
     *
     * @param string $fieldName
     * @param Condition $condition
     * @param mixed $value
     */
    public function __construct(string $fieldName, Condition $condition, mixed $value);

    /**
     * Returns expression-string representation of current filter.
     *
     * @return string
     */
    public function toExpression(): string;
}