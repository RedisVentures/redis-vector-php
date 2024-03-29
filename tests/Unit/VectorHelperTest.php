<?php

namespace RedisVentures\RedisVl\Unit;

use PHPUnit\Framework\TestCase;
use RedisVentures\RedisVl\VectorHelper;

class VectorHelperTest extends TestCase
{
    /**
     * @return void
     */
    public function testToBytes(): void
    {
        $expectedString = pack('f*', 0.00001, 0.00002, 0.00003);

        $this->assertSame($expectedString, VectorHelper::toBytes([0.00001, 0.00002, 0.00003]));
    }
}