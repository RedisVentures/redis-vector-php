<?php

namespace RedisVentures\PredisVl\Unit\Query\Filter;

use PHPUnit\Framework\TestCase;
use RedisVentures\PredisVl\Query\Filter\FilterInterface;

class AbstractFilter extends TestCase
{
    /**
     * @var FilterInterface
     */
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class() extends \RedisVentures\PredisVl\Query\Filter\AbstractFilter
        {
            public function toExpression(): string
            {
                return 'foobar';
            }
        };
    }

    /**
     * @return void
     */
    public function testToExpression(): void
    {
        $this->assertSame('foobar', $this->testClass->toExpression());
    }
}