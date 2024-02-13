<?php

namespace Vladvildanov\PredisVl\Query\Filter;

interface AggregateFilterInterface extends FilterInterface
{
    /**
     * Creates an aggregated filter with intersection (AND).
     *
     * @param FilterInterface ...$filter
     * @return AggregateFilterInterface
     */
    public function and(FilterInterface...$filter): AggregateFilterInterface;

    /**
     * Creates an aggregated filter with union (OR).
     *
     * @param FilterInterface ...$filter
     * @return AggregateFilterInterface
     */
    public function or(FilterInterface...$filter): AggregateFilterInterface;
}