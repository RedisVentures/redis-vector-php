<?php

namespace RedisVentures\PredisVl\Vectorizer;

use Exception;

class Factory implements FactoryInterface
{
    /**
     * @var array|string[]
     */
    private array $classMappings = [
        'openai' => OpenAIVectorizer::class,
    ];

    /**
     * Allows to provide additional class mappings for custom vectorizers.
     *
     * Example: ['foo' => Foo::class, 'bar' => Bar::class]
     *
     * @param array $additionalMappings
     */
    public function __construct(array $additionalMappings = [])
    {
        if (!empty($additionalMappings)) {
            $this->classMappings = array_merge($this->classMappings, $additionalMappings);
        }
    }

    /**
     * @inheritDoc
     */
    public function createVectorizer(string $vectorizer, string $model = null, array $configuration = []): VectorizerInterface
    {
        if (!array_key_exists(strtolower($vectorizer), $this->classMappings)) {
            throw new Exception('Given vectorizer does not exists.');
        }

        $class = $this->classMappings[strtolower($vectorizer)];

        return new $class($model, $configuration);
    }
}