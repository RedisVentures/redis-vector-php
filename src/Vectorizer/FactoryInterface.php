<?php

namespace RedisVentures\PredisVl\Vectorizer;

interface FactoryInterface
{
    /**
     * Creates Vectorizer with given configuration.
     *
     * @param string $vectorizer
     * @param string|null $model
     * @param array $configuration
     * @return VectorizerInterface
     */
    public function createVectorizer(
        string $vectorizer,
        string $model = null,
        array $configuration = []
    ): VectorizerInterface;
}