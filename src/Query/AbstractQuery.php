<?php

namespace RedisVentures\RedisVl\Query;

use Predis\Command\Argument\Search\SearchArguments;
use RedisVentures\RedisVl\Factory;
use RedisVentures\RedisVl\FactoryInterface;
use RedisVentures\RedisVl\Query\Filter\FilterInterface;

abstract class AbstractQuery implements QueryInterface
{
    /**
     * @var FactoryInterface
     */
    protected FactoryInterface $factory;

    /**
     * @var array{first: int, limit: int}|null
     */
    protected ?array $pagination;

    public function __construct(protected ?FilterInterface $filter = null, FactoryInterface $factory = null)
    {
        $this->factory = $factory ?? new Factory();
    }

    /**
     * @inheritDoc
     */
    public function getFilter(): FilterInterface
    {
        return $this->filter;
    }

    /**
     * @inheritDoc
     */
    abstract public function getSearchArguments(): SearchArguments;

    /**
     * @inheritDoc
     */
    abstract public function getQueryString(): string;

    /**
     * @inheritDoc
     */
    public function setPagination(int $first, int $limit): void
    {
        $this->pagination['first'] = $first;
        $this->pagination['limit'] = $limit;
    }
}