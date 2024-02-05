<?php

namespace Unit;

use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\CreateArguments;
use Vladvildanov\PredisVl\Factory;

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
}