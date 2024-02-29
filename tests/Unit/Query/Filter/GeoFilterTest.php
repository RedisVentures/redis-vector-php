<?php

namespace RedisVentures\PredisVl\Unit\Query\Filter;

use PHPUnit\Framework\TestCase;
use RedisVentures\PredisVl\Enum\Condition;
use RedisVentures\PredisVl\Enum\Unit;
use RedisVentures\PredisVl\Query\Filter\GeoFilter;

class GeoFilterTest extends TestCase
{
    /**
     * @dataProvider filterProvider
     * @param Condition $condition
     * @param $value
     * @param string $expectedExpression
     * @return void
     */
    public function testToExpression(Condition $condition, $value, string $expectedExpression): void
    {
        $filter = new GeoFilter('foo', $condition, $value);

        $this->assertSame($expectedExpression, $filter->toExpression());
    }

    public static function filterProvider(): array
    {
        return [
            'equal' => [
                Condition::equal,
                ['lon' => 10.111, 'lat' => 11.111, 'radius' => 100, 'unit' => Unit::kilometers],
                '@foo:[10.111 11.111 100 km]'
            ],
            'not_equal' => [
                Condition::notEqual,
                ['lon' => 10.111, 'lat' => 11.111, 'radius' => 100, 'unit' => Unit::kilometers],
                '-@foo:[10.111 11.111 100 km]'
            ],
        ];
    }
}