<?php

namespace RedisVentures\RedisVl\Unit\Vectorizer;

use Exception;
use PHPUnit\Framework\TestCase;
use RedisVentures\RedisVl\Vectorizer\Factory;
use RedisVentures\RedisVl\Vectorizer\OpenAIVectorizer;

class FactoryTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testCreateVectorizer(): void
    {
        $factory = new Factory();
        $vectorizer = $factory->createVectorizer('openai', 'test model');

        $this->assertSame('test model', $vectorizer->getModel());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateVectorizerWithAdditionalMappings(): void
    {
        $factory = new Factory(['openai' => OpenAIVectorizer::class]);
        $vectorizer = $factory->createVectorizer('openai', 'test model');

        $this->assertSame('test model', $vectorizer->getModel());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateVectorizerThrowsErrorOnNonExistingVectorizer(): void
    {
        $factory = new Factory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Given vectorizer does not exists.');

        $factory->createVectorizer('foobar');
    }
}