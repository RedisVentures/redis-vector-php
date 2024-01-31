<?php

namespace Vladvildanov\PredisVl\Vectorizer;

interface VectorizerInterface
{
    /**
     * Convert text into it's vector representation.
     *
     * @param string $text
     * @return float[]|string[]
     */
    public function embed(string $text): array;

    /**
     * Convert multiple text chunks into it's single vector representation.
     *
     * @param string[] $texts
     * @return float[]|string[]
     */
    public function batchEmbed(array $texts): array;

    /**
     * Returns model name of current vectorizer.
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Returns vectorizer configuration.
     *
     * @return array
     */
    public function getConfiguration(): array;
}