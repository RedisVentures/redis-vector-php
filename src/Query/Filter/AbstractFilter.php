<?php

namespace Vladvildanov\PredisVl\Query\Filter;

use Vladvildanov\PredisVl\Enum\Condition;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * Mappings according to Redis Query Syntax
     *
     * @link https://redis.io/docs/interact/search-and-query/advanced-concepts/query_syntax/
     * @var array
     */
    protected array $conditionMappings = [
        '==' => '',
        '!=' => '-'
    ];

    /**
     * @inheritDoc
     */
    public function __construct(string $fieldName, Condition $condition, mixed $value)
    {
    }

    /**
     * @inheritDoc
     */
    abstract public function toExpression(): string;
}