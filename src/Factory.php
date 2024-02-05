<?php

namespace Vladvildanov\PredisVl;

use Predis\Command\Argument\Search\CreateArguments;

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
}