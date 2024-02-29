<?php

namespace RedisVentures\PredisVl\Unit\Query\Filter;

use Exception;
use PHPUnit\Framework\TestCase;
use RedisVentures\PredisVl\Enum\Condition;
use RedisVentures\PredisVl\Query\Filter\NumericFilter;

class NumericFilterTest extends TestCase
{
    /**
     * @dataProvider filterProvider
     * @param Condition $condition
     * @param mixed $value
     * @param string $expectedExpression
     * @return void
     * @throws Exception
     */
    public function testToExpressionReturnsCorrectExpression(
        Condition $condition,
        mixed $value,
        string $expectedExpression
    ): void {
        $filter = new NumericFilter('foo', $condition, $value);

        $this->assertSame($expectedExpression, $filter->toExpression());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testToExpressionThrowsExceptionOnIncorrectValue(): void
    {
        $filter = new NumericFilter('foo', Condition::between, 10);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Between condition requires int[] as $ value');

        $filter->toExpression();
    }

    public static function filterProvider(): array
    {
        return [
            'equal' => [Condition::equal, 10, '@foo:[10 10]'],
            'not_equal' => [Condition::notEqual, 10, '-@foo:[10 10]'],
            'greater_than' => [Condition::greaterThan, 10, '@foo:[(10 +inf]'],
            'greater_than_or_equal' => [Condition::greaterThanOrEqual, 10, '@foo:[10 +inf]'],
            'lower_than' => [Condition::lowerThan, 10, '@foo:[-inf (10]'],
            'lower_than_or_equal' => [Condition::lowerThanOrEqual, 10, '@foo:[-inf 10]'],
            'between' => [Condition::between, [10, 20], '@foo:[10 20]'],
        ];
    }
}