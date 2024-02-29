<?php

namespace RedisVentures\PredisVl\Unit\Query\Filter;

use PHPUnit\Framework\TestCase;
use RedisVentures\PredisVl\Enum\Condition;
use RedisVentures\PredisVl\Query\Filter\TextFilter;

class TextFilterTest extends TestCase
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
        $filter = new TextFilter('foo', $condition, $value);

        $this->assertSame($expectedExpression, $filter->toExpression());
    }

    public static function filterProvider(): array
    {
        return [
            'empty_value' => [Condition::equal, '', '*'],
            'equal' => [Condition::equal, 'bar', '@foo:(bar)'],
            'equal_multiple' => [Condition::equal, 'bar|baz', '@foo:(bar|baz)'],
            'not_equal' => [Condition::notEqual, 'bar', '-@foo:(bar)'],
            'pattern' => [Condition::pattern, 'bar*', "@foo:(w'bar*')"],
            'pattern_multiple' => [Condition::pattern, 'bar*|baz*', "@foo:(w'bar*|baz*')"],
        ];
    }
}