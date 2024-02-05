<?php

namespace Vladvildanov\PredisVl;

use Predis\Command\Argument\Search\CreateArguments;

interface FactoryInterface
{
    /**
     * Creates builder object for index command arguments.
     *
     * @return CreateArguments
     */
    public function createIndexBuilder(): CreateArguments;
}