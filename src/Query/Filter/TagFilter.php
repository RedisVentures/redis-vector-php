<?php

namespace RedisVentures\PredisVl\Query\Filter;

use JetBrains\PhpStorm\ArrayShape;
use RedisVentures\PredisVl\Enum\Condition;
use RedisVentures\PredisVl\Enum\Logical;

class TagFilter extends AbstractFilter
{
    /**
     * Creates tag filter based on condition.
     * Value can be provided as a string (single tag) or as an array (multiple tags).
     *
     * @param string $fieldName
     * @param Condition $condition
     * @param string|array $value
     */
    public function __construct(
        protected string    $fieldName,
        protected Condition $condition,
        #[ArrayShape([
            'conjunction' => Logical::class,
            'tags' => 'array',
        ])] protected mixed $value
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toExpression(): string
    {
        $condition = $this->conditionMappings[$this->condition->value];

        if (is_string($this->value)) {
            return "{$condition}@{$this->fieldName}:{{$this->value}}";
        }

        if ($this->value['conjunction'] === Logical::or) {
            $query = "{$condition}@{$this->fieldName}:{";

            foreach ($this->value['tags'] as $tag) {
                $query .= $tag . " | ";
            }

            return trim($query, '| ') . '}';
        }

        $query = '';

        foreach ($this->value['tags'] as $tag) {
            $query .= "{$condition}@{$this->fieldName}:{{$tag}} ";
        }

        return trim($query);
    }
}