<?php

namespace RedisVentures\RedisVl\Unit\Query\Filter;

use PHPUnit\Framework\TestCase;
use RedisVentures\RedisVl\Enum\Condition;
use RedisVentures\RedisVl\Enum\Logical;
use RedisVentures\RedisVl\Query\Filter\TagFilter;

class TagFilterTest extends TestCase
{
    /**
     * @dataProvider filterProvider
     * @param Condition $condition
     * @param $value
     * @param string $expectedQuery
     * @return void
     */
    public function testToExpression(Condition $condition, $value, string $expectedQuery): void
    {
        $filter = new TagFilter('foo', $condition, $value);

        $this->assertSame($expectedQuery, $filter->toExpression());
    }

    public static function filterProvider(): array
    {
        return [
            'single_tag_equal' => [Condition::equal, 'bar', '@foo:{bar}'],
            'single_tag_not_equal' => [Condition::notEqual, 'bar', '-@foo:{bar}'],
            'multiple_tags_equal_or' => [Condition::equal, ['conjunction' => Logical::or, 'tags' => ['bar', 'baz']], '@foo:{bar | baz}'],
            'multiple_tags_equal_and' => [Condition::equal, ['conjunction' => Logical::and, 'tags' => ['bar', 'baz']], '@foo:{bar} @foo:{baz}'],
            'wrong_condition' => [Condition::greaterThan, 'bar', '@foo:{bar}'],
        ];
    }
}