<?php

namespace RedisVentures\RedisVl\Unit;

use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SearchArguments;
use RedisVentures\RedisVl\Factory;

class FactoryTest extends TestCase
{
    /**
     * @return void
     */
    public function testCreateIndexBuilder(): void
    {
        $factory = new Factory();
        $this->assertInstanceOf(CreateArguments::class, $factory->createIndexBuilder());
    }

    /**
     * @return void
     */
    public function testCreateSearchBuilder(): void
    {
        $factory = new Factory();
        $this->assertInstanceOf(SearchArguments::class, $factory->createSearchBuilder());
    }
}