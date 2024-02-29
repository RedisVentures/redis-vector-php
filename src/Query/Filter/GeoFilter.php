<?php

namespace RedisVentures\RedisVl\Query\Filter;

use JetBrains\PhpStorm\ArrayShape;
use RedisVentures\RedisVl\Enum\Condition;
use RedisVentures\RedisVl\Enum\Unit;

class GeoFilter extends AbstractFilter
{
    /**
     * Creates geo filter based on condition.
     * Values should be provided as a specific-shaped array described as ArrayShape attribute.
     *
     * Only equal, notEqual conditions are allowed.
     *
     * @param string $fieldName
     * @param Condition $condition
     * @param array{lon: float, lat: float, radius: int, unit: Unit} $value
     */
    public function __construct(
        protected readonly string    $fieldName,
        protected readonly Condition $condition,
        #[ArrayShape([
            'lon' => 'float',
            'lat' => 'float',
            'radius' => 'int',
            'unit' => Unit::class
        ])] protected readonly array $value
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toExpression(): string
    {
        $condition = $this->conditionMappings[$this->condition->value];

        $lon = (float) $this->value['lon'];
        $lat = (float) $this->value['lat'];

        return "$condition@$this->fieldName:[{$lon} {$lat} {$this->value['radius']} {$this->value['unit']->value}]";
    }
}