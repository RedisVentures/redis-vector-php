<?php

namespace Vladvildanov\PredisVl\Query\Filter;

use Vladvildanov\PredisVl\Enum\Logical;

class AggregateFilter extends AbstractFilter implements AggregateFilterInterface
{
    /**
     * @var array
     */
    private array $filters = [];

    /**
     * Mapping for logical operators according to query syntax.
     *
     * @var array
     * @link https://redis.io/docs/interact/search-and-query/query/combined/
     */
    private array $conjunctionMapping = [
        '&&' => ' ',
        '||' => ' | ',
    ];

    /**
     * Creates an aggregated filter based on the set of given filters.
     *
     * If the same logical operator should be applied to all filters, you could pass them here in constructor.
     * If you need a combination of logical operators use and() or() methods to build suitable aggregate filter.
     *
     * @param FilterInterface $filters
     * @param Logical $conjunction
     */
    public function __construct($filters = [], Logical $conjunction = Logical::and)
    {
        if (!empty($filters)) {
            $this->addFilters($filters, $conjunction);
        }
    }

    /**
     * @inheritDoc
     */
    public function and(FilterInterface ...$filter): AggregateFilterInterface
    {
        $this->addFilters(func_get_args(), Logical::and);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function or(FilterInterface ...$filter): AggregateFilterInterface
    {
        $this->addFilters(func_get_args(), Logical::or);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toExpression(): string
    {
        return trim(implode('', $this->filters), ' |');
    }

    /**
     * Add filters with given conjunction into array.
     *
     * @param FilterInterface[] $filters
     * @param Logical $conjunction
     * @return void
     */
    private function addFilters(array $filters, Logical $conjunction): void
    {
        if (count($filters) === 1) {
            $this->filters[] = $this->conjunctionMapping[$conjunction->value];
            $this->filters[] = $filters[0]->toExpression();
        } else {
            if (!empty($this->filters)) {
                $this->filters[] = ' ';
            }

            foreach ($filters as $i => $filter) {
                $this->filters[] = $filter->toExpression();

                if ($i !== count($filters) - 1) {
                    $this->filters[] = $this->conjunctionMapping[$conjunction->value];
                }
            }
        }
    }
}