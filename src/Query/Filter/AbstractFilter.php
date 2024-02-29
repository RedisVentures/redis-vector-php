<?php

namespace RedisVentures\PredisVl\Query\Filter;


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
    abstract public function toExpression(): string;
}