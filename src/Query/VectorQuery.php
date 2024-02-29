<?php

namespace RedisVentures\PredisVl\Query;

use Predis\Command\Argument\Search\SearchArguments;
use RedisVentures\PredisVl\FactoryInterface;
use RedisVentures\PredisVl\Query\Filter\FilterInterface;
use RedisVentures\PredisVl\VectorHelper;

class VectorQuery extends AbstractQuery
{
    public function __construct(
        private readonly array              $vector,
        private readonly string             $vectorFieldName,
        private ?array                      $returnFields = null,
        private readonly int                $resultsCount = 10,
        private readonly bool               $returnScore = true,
        private readonly int                $dialect = 2,
        protected ?FilterInterface          $filter = null,
        FactoryInterface                    $factory = null
    ) {
        parent::__construct($filter, $factory);
    }

    /**
     * @inheritDoc
     */
    public function getQueryString(): string
    {
        $query = '';

        if ($this->filter instanceof FilterInterface) {
            $filter = $this->filter->toExpression();
            $query .= "($filter)=>";
        } else {
            $query .= '(*)=>';
        }

        $query .= "[KNN $this->resultsCount @$this->vectorFieldName" . ' $vector' . ' AS vector_score]';

        return $query;
    }

    public function getSearchArguments(): SearchArguments
    {
        $searchArguments = $this->factory->createSearchBuilder();

        $searchArguments = $searchArguments
            ->params(['vector', VectorHelper::toBytes($this->vector)])
            ->sortBy('vector_score')
            ->dialect((string) $this->dialect);

        if (!empty($this->returnFields)) {
            $searchArguments = $searchArguments->addReturn(count($this->returnFields), ...$this->returnFields);
        }

        if ($this->returnScore) {
            $searchArguments = $searchArguments->withScores();
        }

        if (!empty($this->pagination)) {
            $searchArguments = $searchArguments->limit($this->pagination['first'], $this->pagination['limit']);
        }

        return $searchArguments;
    }
}