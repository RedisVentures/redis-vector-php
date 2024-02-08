<?php

namespace Vladvildanov\PredisVl\Unit\Query;

use Mockery;
use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\SearchArguments;
use Vladvildanov\PredisVl\FactoryInterface;
use Vladvildanov\PredisVl\Query\Filter\FilterInterface;
use Vladvildanov\PredisVl\Query\VectorQuery;
use Vladvildanov\PredisVl\VectorHelper;

class VectorQueryTest extends TestCase
{
    /**
     * @var FilterInterface
     */
    private FilterInterface $mockFilter;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $mockFactory;

    protected function setUp(): void
    {
        $this->mockFilter = Mockery::mock(FilterInterface::class);
        $this->mockFactory = Mockery::mock(FactoryInterface::class);
    }

    /**
     * @dataProvider queryStringProvider
     * @param string $fieldName
     * @param int $resultsCount
     * @param string $expectedQuery
     * @return void
     */
    public function testGetQueryStringWithoutFilter(string $fieldName, int $resultsCount, string $expectedQuery): void
    {
        $query = new VectorQuery([0.01, 0.02, 0.03], $fieldName, null, $resultsCount);

        $this->assertSame($expectedQuery, $query->getQueryString());
    }

    /**
     * @return void
     */
    public function testGetQueryStringWithFilter(): void
    {
        $this->mockFilter
            ->shouldReceive('toExpression')
            ->once()
            ->withNoArgs()
            ->andReturn("@foo:{bar}");

        $query = new VectorQuery(
            [0.01, 0.02, 0.03],
            'foo',
            null,
            10,
            true,
            2,
            $this->mockFilter
        );

        $this->assertSame('(@foo:{bar})=>[KNN 10 @foo $vector AS vector_score]', $query->getQueryString());
    }

    /**
     * @dataProvider searchArgumentsProvider
     * @param array $vector
     * @param string $dialect
     * @param array|null $returnFields
     * @param bool $returnScore
     * @param array $expectedSearchArguments
     * @return void
     */
    public function testGetSearchArgumentsWithoutPagination(
        array $vector,
        string $dialect,
        ?array $returnFields,
        bool $returnScore,
        array $expectedSearchArguments
    ): void {
        $this->mockFactory
            ->shouldReceive('createSearchBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn(new SearchArguments());

        $query = new VectorQuery(
            $vector,
            'foo',
            $returnFields,
            10,
            $returnScore,
            $dialect,
            null,
            $this->mockFactory
        );

        $this->assertEquals($expectedSearchArguments, $query->getSearchArguments()->toArray());
    }

    /**
     * @return void
     */
    public function testGetSearchArgumentsWithPagination(): void
    {
        $this->mockFactory
            ->shouldReceive('createSearchBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn(new SearchArguments());

        $query = new VectorQuery(
            [0.01, 0.02, 0.03],
            'foo',
            null,
            10,
            true,
            2,
            null,
            $this->mockFactory
        );

        $query->setPagination(10, 20);

        $this->assertEquals([
            'PARAMS', 2, 'vector', VectorHelper::toBytes([0.01, 0.02, 0.03]), 'SORTBY', 'vector_score', 'ASC',
            'DIALECT', '2', 'WITHSCORES', 'LIMIT', 10, 20
        ], $query->getSearchArguments()->toArray());
    }

    public static function queryStringProvider(): array
    {
        return [
            'default' => ['foo', 10, '(*)=>[KNN 10 @foo $vector AS vector_score]'],
            'with_result_count' => ['foo', 3, '(*)=>[KNN 3 @foo $vector AS vector_score]'],
        ];
    }

    public static function searchArgumentsProvider(): array
    {
        return [
            'default' => [
                [0.01, 0.02, 0.03],
                '2',
                null,
                true,
                [
                    'PARAMS', 2, 'vector', VectorHelper::toBytes([0.01, 0.02, 0.03]), 'SORTBY', 'vector_score', 'ASC',
                    'DIALECT', '2', 'WITHSCORES'
                ]
            ],
            'with_dialect' => [
                [0.01, 0.02, 0.03],
                '1',
                null,
                true,
                [
                    'PARAMS', 2, 'vector', VectorHelper::toBytes([0.01, 0.02, 0.03]), 'SORTBY', 'vector_score', 'ASC',
                    'DIALECT', '1', 'WITHSCORES'
                ]
            ],
            'with_return_fields' => [
                [0.01, 0.02, 0.03],
                '2',
                ['bar', 'baz'],
                true,
                [
                    'PARAMS', 2, 'vector', VectorHelper::toBytes([0.01, 0.02, 0.03]), 'SORTBY', 'vector_score', 'ASC',
                    'DIALECT', '2', 'RETURN', 2, 'bar', 'baz', 'WITHSCORES'
                ]
            ],
            'without_scores' => [
                [0.01, 0.02, 0.03],
                '2',
                null,
                false,
                [
                    'PARAMS', 2, 'vector', VectorHelper::toBytes([0.01, 0.02, 0.03]), 'SORTBY', 'vector_score', 'ASC',
                    'DIALECT', '2'
                ]
            ],
        ];
    }
}