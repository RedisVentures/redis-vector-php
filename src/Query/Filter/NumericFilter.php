<?php

namespace Vladvildanov\PredisVl\Query\Filter;

use Exception;
use Vladvildanov\PredisVl\Enum\Condition;

class NumericFilter extends AbstractFilter
{
    /**
     * Creates numeric filter based on condition.
     * Value can be provided as integer (single value) and in case of between condition as array of two integers.
     */
    public function __construct(
        protected string $fieldName,
        protected Condition $condition,
        protected mixed $value
    ) {
        parent::__construct($fieldName, $condition, $value);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function toExpression(): string
    {
        if ($this->condition === Condition::equal || $this->condition === Condition::notEqual) {
            $condition = $this->conditionMappings[$this->condition->value];
            return "$condition@$this->fieldName:[$this->value $this->value]";
        }

        if ($this->condition === Condition::greaterThan) {
            return "@$this->fieldName:[($this->value +inf]";
        }

        if ($this->condition === Condition::greaterThanOrEqual) {
            return "@$this->fieldName:[$this->value +inf]";
        }

        if ($this->condition === Condition::lowerThan) {
            return "@$this->fieldName:[-inf ($this->value]";
        }

        if ($this->condition === Condition::lowerThanOrEqual) {
            return "@$this->fieldName:[-inf $this->value]";
        }

        if ($this->condition === Condition::between && !is_array($this->value)) {
            throw new Exception('Between condition requires int[] as $ value');
        }

        return "@$this->fieldName:[{$this->value[0]} {$this->value[1]}]";
    }
}