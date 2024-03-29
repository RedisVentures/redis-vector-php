<?php

namespace RedisVentures\RedisVl\Query\Filter;

use RedisVentures\RedisVl\Enum\Condition;

class TextFilter extends AbstractFilter
{
    /**
     * Creates text filter based on condition.
     * Value should be provided as a string, it's possible to provide multiple values with separator
     * to modify the behaviour.
     *
     * Example: "foo|bar" - matching values that contains foo OR bar
     * "foo bar" - matching values that contains foo AND bar
     * "%foobaz%" - fuzzy search
     *
     * Condition can be set to "pattern" to perform:index, suffix or infix search.
     *
     * @param string $fieldName
     * @param Condition $condition
     * @param string $value
     */
    public function __construct(protected string $fieldName, protected Condition $condition, protected string $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function toExpression(): string
    {
        if (empty($this->value)) {
            return '*';
        }

        if ($this->condition === Condition::equal || $this->condition === Condition::notEqual) {
            $condition = $this->conditionMappings[$this->condition->value];

            return "$condition@$this->fieldName:($this->value)";
        }

        return "@$this->fieldName:(w'$this->value')";
    }
}