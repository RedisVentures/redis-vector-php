<?php

namespace RedisVentures\RedisVl\Unit\Query\Filter;

use PHPUnit\Framework\TestCase;
use RedisVentures\RedisVl\Enum\Condition;
use RedisVentures\RedisVl\Enum\Logical;
use RedisVentures\RedisVl\Enum\Unit;
use RedisVentures\RedisVl\Query\Filter\AggregateFilter;
use RedisVentures\RedisVl\Query\Filter\GeoFilter;
use RedisVentures\RedisVl\Query\Filter\NumericFilter;
use RedisVentures\RedisVl\Query\Filter\TagFilter;
use RedisVentures\RedisVl\Query\Filter\TextFilter;

class AggregateFilterTest extends TestCase
{
    /**
     * @return void
     */
    public function testAnd(): void
    {
        $filter = (new AggregateFilter())->and(
            new TextFilter('foo', Condition::equal, 'bar'),
            new NumericFilter('bar', Condition::greaterThan, 100)
        );

        $this->assertSame('@foo:(bar) @bar:[(100 +inf]', $filter->toExpression());
    }

    /**
     * @return void
     */
    public function testOr(): void
    {
        $filter = (new AggregateFilter())->or(
            new TextFilter('foo', Condition::equal, 'bar'),
            new NumericFilter('bar', Condition::greaterThan, 100),
            new TagFilter('baz', Condition::equal, 'bar')
        );

        $this->assertSame('@foo:(bar) | @bar:[(100 +inf] | @baz:{bar}', $filter->toExpression());
    }

    /**
     * @dataProvider filterProvider
     * @param array $filters
     * @param Logical|null $conjunction
     * @param string $expectedExpression
     * @return void
     */
    public function testToExpressionWithSameLogicalOperator(
        array $filters,
        ?Logical $conjunction,
        string $expectedExpression
    ): void {
        $filter = new AggregateFilter($filters, $conjunction);

        $this->assertSame($expectedExpression, $filter->toExpression());
    }

    /**
     * @return void
     */
    public function testToExpressionCreatesCombinedLogicalOperatorExpression(): void
    {
        $filter = new AggregateFilter(
            [
                new TextFilter('foo', Condition::equal, 'bar'),
                new NumericFilter('bar', Condition::greaterThan, 100)
            ],
            Logical::and
        );

        $filter = $filter
            ->or(
                new TagFilter('baz', Condition::notEqual, 'bar'),
                new TagFilter('bal', Condition::notEqual, 'bak')
            )
            ->and(new GeoFilter(
                'bad',
                Condition::equal,
                ['lon' => 10.111, 'lat' => 11.111, 'radius' => 100, 'unit' => Unit::kilometers]
            ));

        $this->assertSame(
            '@foo:(bar) @bar:[(100 +inf] -@baz:{bar} | -@bal:{bak} @bad:[10.111 11.111 100 km]',
            $filter->toExpression()
        );
    }

    public static function filterProvider(): array
    {
        return [
            'empty' => [
                [],
                Logical::and,
                ''
            ],
            'AND' => [
                [
                    new TextFilter('foo', Condition::equal, 'bar'),
                    new NumericFilter('bar', Condition::greaterThan, 100)
                ],
                Logical::and,
                '@foo:(bar) @bar:[(100 +inf]'
            ],
            'OR' => [
                [
                    new TextFilter('foo', Condition::equal, 'bar'),
                    new NumericFilter('bar', Condition::greaterThan, 100),
                    new TagFilter('baz', Condition::equal, 'bar')
                ],
                Logical::or,
                '@foo:(bar) | @bar:[(100 +inf] | @baz:{bar}'
            ],
        ];
    }
}