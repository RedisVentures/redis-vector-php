<?php

namespace RedisVentures\PredisVl;

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SearchArguments;

interface FactoryInterface
{
    /**
     * Creates builder object for index command arguments.
     *
     * @return CreateArguments
     */
    public function createIndexBuilder(): CreateArguments;

    /**
     * Creates builder object for search command arguments.
     *
     * @return SearchArguments
     */
    public function createSearchBuilder(): SearchArguments;
}