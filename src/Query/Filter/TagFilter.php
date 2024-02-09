<?php

namespace Vladvildanov\PredisVl\Query\Filter;

use JetBrains\PhpStorm\ArrayShape;
use Vladvildanov\PredisVl\Enum\Condition;
use Vladvildanov\PredisVl\Enum\Logical;

class TagFilter implements FilterInterface
{
    /**
     * Mappings according to Redis Query Syntax
     *
     * @link https://redis.io/docs/interact/search-and-query/advanced-concepts/query_syntax/
     * @var array
     */
    private array $conditionMappings = [
        '==' => '',
        '!=' => '-'
    ];

    /**
     * Creates tag filter based on condition.
     * Value can be provided as a string (single tag) or as an array (multiple tags).
     *
     * @param string $fieldName
     * @param Condition $condition
     * @param string|array $value
     */
    public function __construct(
        private readonly string    $fieldName,
        private readonly Condition $condition,
        #[ArrayShape([
            'conjunction' => Logical::class,
            'tags' => 'array',
        ])] private $value
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