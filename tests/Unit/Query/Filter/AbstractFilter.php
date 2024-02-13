<?php

namespace Vladvildanov\PredisVl\Unit\Query\Filter;

use PHPUnit\Framework\TestCase;
use Vladvildanov\PredisVl\Query\Filter\FilterInterface;

class AbstractFilter extends TestCase
{
    /**
     * @var FilterInterface
     */
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class() extends \Vladvildanov\PredisVl\Query\Filter\AbstractFilter
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