<?php

namespace RedisVentures\PredisVl;

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SearchArguments;

/**
 * Simple factory for general purpose objects creation.
 */
class Factory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createIndexBuilder(): CreateArguments
    {
        return new CreateArguments();
    }

    /**
     * @inheritDoc
     */
    public function createSearchBuilder(): SearchArguments
    {
        return new SearchArguments();
    }
}