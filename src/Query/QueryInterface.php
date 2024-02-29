<?php

namespace RedisVentures\PredisVl\Query;

use Predis\Command\Argument\Search\SearchArguments;
use RedisVentures\PredisVl\Query\Filter\FilterInterface;

interface QueryInterface
{
    /**
     * Returns filter for current query.
     *
     * @return FilterInterface|null
     */
    public function getFilter(): ?FilterInterface;

    /**
     * Returns Predis search builder object.
     *
     * @return SearchArguments
     */
    public function getSearchArguments(): SearchArguments;

    /**
     * Returns generated query string.
     *
     * @return string
     */
    public function getQueryString(): string;

    /**
     * Set the paging parameters for the query to limit the results between first and num_results.
     *
     * @param int $first
     * @param int $limit
     * @return void
     */
    public function setPagination(int $first, int $limit): void;
}