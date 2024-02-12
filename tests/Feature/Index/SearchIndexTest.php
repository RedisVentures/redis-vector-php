<?php

namespace Vladvildanov\PredisVl\Feature\Index;

use Vladvildanov\PredisVl\Enum\Condition;
use Vladvildanov\PredisVl\Enum\Logical;
use Vladvildanov\PredisVl\Feature\FeatureTestCase;
use Predis\Client;
use Vladvildanov\PredisVl\Index\SearchIndex;
use Vladvildanov\PredisVl\Query\Filter\FilterInterface;
use Vladvildanov\PredisVl\Query\Filter\NumericFilter;
use Vladvildanov\PredisVl\Query\Filter\TagFilter;
use Vladvildanov\PredisVl\Query\VectorQuery;
use Vladvildanov\PredisVl\VectorHelper;

class SearchIndexTest extends FeatureTestCase
{
    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var array
     */
    private array $hashSchema;

    /**
     * @var array
     */
    private array $jsonSchema;

    protected function setUp(): void
    {
        $this->client = $this->getClient();
        $this->hashSchema = [
            'index' => [
                'name' => 'foobar',
                'prefix' => 'foo:',
            ],
            'fields' => [
                'id' => [
                    'type' => 'text',
                    'alias' => 'foo',
                ],
                'count' => [
                    'type' => 'numeric',
                ],
                'id_embeddings' => [
                    'type' => 'vector',
                    'algorithm' => 'flat',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'distance_metric' => 'cosine',
                ]
            ]
        ];
        $this->jsonSchema = [
            'index' => [
                'name' => 'foobar',
                'prefix' => 'foo:',
                'storage_type' => 'json'
            ],
            'fields' => [
                '$.id' => [
                    'type' => 'text',
                    'alias' => 'foo',
                ],
                '$.count' => [
                    'type' => 'numeric',
                ],
                '$.id_embeddings' => [
                    'type' => 'vector',
                    'algorithm' => 'flat',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'distance_metric' => 'cosine',
                ]
            ]
        ];
    }

    /**
     * @return void
     */
    public function testCreatesHashIndexWithMultipleFields(): void
    {
        $index = new SearchIndex($this->client, $this->hashSchema);
        $this->assertEquals('OK', $index->create());

        $indexInfo = $this->client->ftinfo($this->hashSchema['index']['name']);

        $this->assertEquals('foobar', $indexInfo[1]);
        $this->assertEquals('HASH', $indexInfo[5][1]);
        $this->assertEquals('foo:', $indexInfo[5][3][0]);
        $this->assertEquals('foo', $indexInfo[7][0][3]);
        $this->assertEquals('TEXT', $indexInfo[7][0][5]);
        $this->assertEquals('NUMERIC', $indexInfo[7][1][5]);
        $this->assertEquals('VECTOR', $indexInfo[7][2][5]);

        $this->assertEquals(
            'OK',
            $index->load('1', ['id' => '1', 'count' => 10, 'id_embeddings' => VectorHelper::toBytes([0.000001, 0.000002, 0.000003])])
        );

        $searchResult = $this->client->ftsearch($this->hashSchema['index']['name'], '*');

        $this->assertSame('foo:1', $searchResult[1]);
        $this->assertSame('1', $searchResult[2][1]);
        $this->assertSame('10', $searchResult[2][3]);
        $this->assertSame(VectorHelper::toBytes([0.000001, 0.000002, 0.000003]), $searchResult[2][5]);
    }

    /**
     * @return void
     */
    public function testCreatesJsonIndexWithMultipleFields(): void
    {
        $index = new SearchIndex($this->client, $this->jsonSchema);
        $this->assertEquals('OK', $index->create());

        $indexInfo = $this->client->ftinfo($this->jsonSchema['index']['name']);

        $this->assertEquals('foobar', $indexInfo[1]);
        $this->assertEquals('JSON', $indexInfo[5][1]);
        $this->assertEquals('foo:', $indexInfo[5][3][0]);
        $this->assertEquals('foo', $indexInfo[7][0][3]);
        $this->assertEquals('TEXT', $indexInfo[7][0][5]);
        $this->assertEquals('NUMERIC', $indexInfo[7][1][5]);
        $this->assertEquals('VECTOR', $indexInfo[7][2][5]);

        $this->assertEquals(
            'OK',
            $index->load('1', '{"id":"1","count":10,"id_embeddings":[0.000001, 0.000002, 0.000003]}')
        );

        $searchResult = $this->client->ftsearch($this->jsonSchema['index']['name'], '*');

        $this->assertSame('foo:1', $searchResult[1]);
        $this->assertSame('{"id":"1","count":10,"id_embeddings":[1e-6,2e-6,3e-6]}', $searchResult[2][1]);
    }

    /**
     * @return void
     */
    public function testCreatesHashIndexWithOverride(): void
    {
        $index = new SearchIndex($this->client, $this->hashSchema);
        $this->assertEquals('OK', $index->create());

        $indexInfo = $this->client->ftinfo($this->hashSchema['index']['name']);

        $this->assertEquals('foobar', $indexInfo[1]);
        $this->assertEquals('HASH', $indexInfo[5][1]);
        $this->assertEquals('foo:', $indexInfo[5][3][0]);
        $this->assertEquals('foo', $indexInfo[7][0][3]);
        $this->assertEquals('TEXT', $indexInfo[7][0][5]);
        $this->assertEquals('NUMERIC', $indexInfo[7][1][5]);
        $this->assertEquals('VECTOR', $indexInfo[7][2][5]);

        $this->assertEquals('OK', $index->create(true));

        $this->assertEquals(
            'OK',
            $index->load('1', ['id' => '1', 'count' => 10, 'id_embeddings' => VectorHelper::toBytes([0.000001, 0.000002, 0.000003])])
        );

        $searchResult = $this->client->ftsearch($this->hashSchema['index']['name'], '*');

        $this->assertSame('foo:1', $searchResult[1]);
        $this->assertSame('1', $searchResult[2][1]);
        $this->assertSame('10', $searchResult[2][3]);
        $this->assertSame(VectorHelper::toBytes([0.000001, 0.000002, 0.000003]), $searchResult[2][5]);
    }

    /**
     * @return void
     */
    public function testFetchHashData(): void
    {
        $index = new SearchIndex($this->client, $this->hashSchema);
        $this->assertEquals('OK', $index->create());

        $this->assertEquals(
            'OK',
            $index->load('1', ['id' => '1', 'count' => 10, 'id_embeddings' => VectorHelper::toBytes([0.000001, 0.000002, 0.000003])])
        );

        $this->assertEquals(
            ['id' => '1', 'count' => 10, 'id_embeddings' => VectorHelper::toBytes([0.000001, 0.000002, 0.000003])],
            $index->fetch('1'));
    }

    /**
     * @return void
     */
    public function testFetchJsonData(): void
    {
        $index = new SearchIndex($this->client, $this->jsonSchema);
        $this->assertEquals('OK', $index->create());

        $this->assertEquals(
            'OK',
            $index->load('1', '{"id":"1","count":10,"id_embeddings":[0.000001, 0.000002, 0.000003]}')
        );

        $this->assertEquals(
            '{"id":"1","count":10,"id_embeddings":[1e-6,2e-6,3e-6]}',
            $index->fetch('1'));
    }

    /**
     * @dataProvider vectorQueryScoreProvider
     * @param array $vector
     * @param int $resultsCount
     * @param array $expectedResponse
     * @return void
     */
    public function testVectorQueryHashIndexReturnsCorrectVectorScore(
        array $vector,
        int $resultsCount,
        array $expectedResponse
    ): void {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
            ],
            'fields' => [
                'id' => [
                    'type' => 'text',
                ],
                'category' => [
                    'type' => 'tag',
                ],
                'description' => [
                    'type' => 'text',
                ],
                'description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine'
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $this->assertTrue($index->load(
                $i,
                [
                    'id' => $i, 'category' => ($i % 2 === 0) ? 'foo' : 'bar', 'description' => 'Foobar foobar',
                    'description_embedding' => VectorHelper::toBytes([$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000])
                ])
            );
        }

        $query = new VectorQuery(
            $vector,
            'description_embedding',
            null,
            $resultsCount,
            true,
            2,
            null
        );

        $response = $index->query($query);
        $this->assertSame($expectedResponse['count'], $response['count']);

        foreach ($expectedResponse['results'] as $key => $value) {
            $this->assertSame(
                $expectedResponse['results'][$key]['vector_score'],
                round((float) $response['results'][$key]['vector_score'], 2)
            );
        }
    }

    /**
     * @dataProvider vectorTagFilterProvider
     * @param FilterInterface|null $filter
     * @param array $expectedResponse
     * @return void
     */
    public function testVectorQueryHashWithTagsFilter(
        ?FilterInterface $filter,
        array $expectedResponse
    ): void {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
            ],
            'fields' => [
                'id' => [
                    'type' => 'text',
                ],
                'category' => [
                    'type' => 'tag',
                ],
                'description' => [
                    'type' => 'text',
                ],
                'description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine'
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $this->assertTrue($index->load(
                $i,
                [
                    'id' => $i, 'category' => ($i % 2 === 0) ? 'foo' : 'bar', 'description' => 'Foobar foobar',
                    'description_embedding' => VectorHelper::toBytes([$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000])
                ])
            );
        }

        $this->assertTrue($index->load(
            5,
            [
                'id' => 5, 'category' => 'foo,bar', 'description' => 'Foobar foobar',
                'description_embedding' => VectorHelper::toBytes([0.005, 0.006, 0.007])
            ])
        );

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'description_embedding',
            null,
            10,
            true,
            2,
            $filter
        );

        $response = $index->query($query);
        $this->assertSame($expectedResponse['count'], $response['count']);

        foreach ($expectedResponse['results'] as $key => $value) {
            $this->assertSame(
                $expectedResponse['results'][$key]['category'],
                $response['results'][$key]['category']
            );
        }
    }

    /**
     * @dataProvider vectorNumericFilterProvider
     * @param FilterInterface|null $filter
     * @param array $expectedResponse
     * @return void
     */
    public function testVectorQueryHashWithNumericFilter(
        ?FilterInterface $filter,
        array $expectedResponse
    ): void {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
            ],
            'fields' => [
                'id' => [
                    'type' => 'text',
                ],
                'price' => [
                    'type' => 'numeric',
                ],
                'description' => [
                    'type' => 'text',
                ],
                'description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine'
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $this->assertTrue($index->load(
                $i,
                [
                    'id' => $i, 'price' => $i * 10, 'description' => 'Foobar foobar',
                    'description_embedding' => VectorHelper::toBytes([$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000])
                ])
            );
        }

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'description_embedding',
            null,
            10,
            true,
            2,
            $filter
        );

        $response = $index->query($query);
        $this->assertSame($expectedResponse['count'], $response['count']);

        foreach ($expectedResponse['results'] as $key => $value) {
            $this->assertSame(
                $expectedResponse['results'][$key]['price'],
                (int) $response['results'][$key]['price']
            );
        }
    }

    /**
     * @return void
     */
    public function testVectorQueryHashIndexReturnsCorrectFields(): void
    {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
            ],
            'fields' => [
                'id' => [
                    'type' => 'text',
                ],
                'category' => [
                    'type' => 'tag',
                ],
                'description' => [
                    'type' => 'text',
                ],
                'description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine'
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $this->assertTrue($index->load(
                $i,
                [
                    'id' => $i, 'category' => ($i % 2 === 0) ? 'foo' : 'bar', 'description' => 'Foobar foobar',
                    'description_embedding' => VectorHelper::toBytes([$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000])
                ])
            );
        }

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'description_embedding',
            ['id', 'category'],
            10,
            false,
            2,
            null
        );

        $response = $index->query($query);
        $this->assertSame(4, $response['count']);

        foreach ($response['results'] as $value) {
            $this->assertNotTrue(array_key_exists('description', $value));
            $this->assertNotTrue(array_key_exists('description_embedding', $value));
            $this->assertNotTrue(array_key_exists('vector_score', $value));
        }
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testVectorQueryJsonIndexReturnsCorrectVectorScore(): void
    {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
                'storage_type' => 'json'
            ],
            'fields' => [
                '$.id' => [
                    'type' => 'text',
                ],
                '$.category' => [
                    'type' => 'tag',
                ],
                '$.description' => [
                    'type' => 'text',
                ],
                '$.description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine',
                    'alias' => 'vector_embedding',
                ],
            ],
        ];

        $expectedResponse = [
            'count' => 4,
            'results' => [
                'product:1' => [
                    'vector_score' => 0.16,
                ],
                'product:2' => [
                    'vector_score' => 0.21,
                ],
                'product:3' => [
                    'vector_score' => 0.24,
                ],
                'product:4' => [
                    'vector_score' => 0.27,
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $json = json_encode([
                'id' => (string)$i, 'category' => ($i % 2 === 0) ? 'foo' : 'bar', 'description' => 'Foobar foobar',
                'description_embedding' => [$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000],
            ], JSON_THROW_ON_ERROR);

            $this->assertTrue($index->load(
                $i,
                $json
            ));
        }

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'vector_embedding',
            null,
            10,
            true,
            2,
            null
        );

        $response = $index->query($query);
        $this->assertSame($expectedResponse['count'], $response['count']);

        foreach ($expectedResponse['results'] as $key => $value) {
            $this->assertSame(
                $expectedResponse['results'][$key]['vector_score'],
                round((float) $response['results'][$key]['vector_score'], 2)
            );
        }
    }

    /**
     * @dataProvider vectorTagFilterProvider
     * @param FilterInterface|null $filter
     * @param array $expectedResponse
     * @return void
     * @throws \JsonException
     */
    public function testVectorQueryJsonIndexWithTagFilter(
        ?FilterInterface $filter,
        array $expectedResponse
    ): void {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
                'storage_type' => 'json'
            ],
            'fields' => [
                '$.id' => [
                    'type' => 'text',
                ],
                '$.category' => [
                    'type' => 'tag',
                    'alias' => 'category',
                ],
                '$.description' => [
                    'type' => 'text',
                ],
                '$.description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine',
                    'alias' => 'vector_embedding',
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $json = json_encode([
                'id' => (string)$i, 'category' => ($i % 2 === 0) ? 'foo' : 'bar', 'description' => 'Foobar foobar',
                'description_embedding' => [$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000],
            ], JSON_THROW_ON_ERROR);

            $this->assertTrue($index->load(
                $i,
                $json
            ));
        }

        $this->assertTrue($index->load(
            "5",
            json_encode([
                'id' => "5", 'category' => ['foo', 'bar'], 'description' => 'Foobar foobar',
                'description_embedding' => [0.005, 0.006, 0.007],
            ], JSON_THROW_ON_ERROR))
        );

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'vector_embedding',
            null,
            10,
            false,
            2,
            $filter
        );

        $response = $index->query($query);
        $this->assertSame($expectedResponse['count'], $response['count']);

        foreach ($response['results'] as $key => $value) {
            $expectedCategories = ($key === 'product:5')
                ? $expectedResponse['results'][$key]['category_array']
                : $expectedResponse['results'][$key]['category'];

            $decodedValue = json_decode($value['$'], true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(
                $expectedCategories,
                $decodedValue['category']
            );
        }
    }

    /**
     * @dataProvider vectorNumericFilterProvider
     * @param FilterInterface|null $filter
     * @param array $expectedResponse
     * @return void
     * @throws \JsonException
     */
    public function testVectorQueryJsonIndexWithNumericFilter(
        ?FilterInterface $filter,
        array $expectedResponse
    ): void {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
                'storage_type' => 'json'
            ],
            'fields' => [
                '$.id' => [
                    'type' => 'text',
                ],
                '$.price' => [
                    'type' => 'numeric',
                    'alias' => 'price',
                ],
                '$.description' => [
                    'type' => 'text',
                ],
                '$.description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine',
                    'alias' => 'vector_embedding',
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $json = json_encode([
                'id' => (string)$i, 'price' => $i * 10, 'description' => 'Foobar foobar',
                'description_embedding' => [$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000],
            ], JSON_THROW_ON_ERROR);

            $this->assertTrue($index->load(
                $i,
                $json
            ));
        }

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'vector_embedding',
            null,
            10,
            true,
            2,
            $filter
        );

        $response = $index->query($query);
        $this->assertSame($expectedResponse['count'], $response['count']);

        foreach ($response['results'] as $key => $value) {
            $decodedResponse = json_decode($value['$'], true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(
                $expectedResponse['results'][$key]['price'],
                (int) $decodedResponse['price']
            );
        }
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testVectorQueryJsonIndexReturnsCorrectFields(): void
    {
        $schema = [
            'index' => [
                'name' => 'products',
                'prefix' => 'product:',
                'storage_type' => 'json'
            ],
            'fields' => [
                '$.id' => [
                    'type' => 'text',
                ],
                '$.category' => [
                    'type' => 'tag',
                ],
                '$.description' => [
                    'type' => 'text',
                ],
                '$.description_embedding' => [
                    'type' => 'vector',
                    'dims' => 3,
                    'datatype' => 'float32',
                    'algorithm' => 'flat',
                    'distance_metric' => 'cosine',
                    'alias' => 'vector_embedding',
                ],
            ],
        ];

        $index = new SearchIndex($this->client, $schema);
        $this->assertEquals('OK', $index->create());

        for ($i = 1; $i < 5; $i++) {
            $json = json_encode([
                'id' => (string)$i, 'category' => ($i % 2 === 0) ? 'foo' : 'bar', 'description' => 'Foobar foobar',
                'description_embedding' => [$i / 1000, ($i + 1) / 1000, ($i + 2) / 1000],
            ], JSON_THROW_ON_ERROR);

            $this->assertTrue($index->load(
                $i,
                $json
            ));
        }

        $query = new VectorQuery(
            [0.001, 0.002, 0.03],
            'vector_embedding',
            ['$.id', '$.category'],
            10,
            true,
            2,
            null
        );

        $response = $index->query($query);
        $this->assertSame(4, $response['count']);

        foreach ($response['results'] as $value) {
            $this->assertNotTrue(array_key_exists('$.description', $value));
            $this->assertNotTrue(array_key_exists('$.description_embedding', $value));
            $this->assertNotTrue(array_key_exists('$.vector_score', $value));
        }
    }

    public static function vectorQueryScoreProvider(): array
    {
        return [
            'default' => [
                [0.001, 0.002, 0.03],
                10,
                [
                    'count' => 4,
                    'results' => [
                        'product:1' => [
                            'vector_score' => 0.16,
                        ],
                        'product:2' => [
                            'vector_score' => 0.21,
                        ],
                        'product:3' => [
                            'vector_score' => 0.24,
                        ],
                        'product:4' => [
                            'vector_score' => 0.27,
                        ],
                    ],
                ]
            ],
            'with_results_count' => [
                [0.001, 0.002, 0.03],
                2,
                [
                    'count' => 2,
                    'results' => [
                        'product:1' => [
                            'vector_score' => 0.16,
                        ],
                        'product:2' => [
                            'vector_score' => 0.21,
                        ],
                    ],
                ]
            ],
        ];
    }

    public static function vectorTagFilterProvider(): array
    {
        return [
            'default' => [
                null,
                [
                    'count' => 5,
                    'results' => [
                        'product:1' => [
                            'category' => 'bar',
                        ],
                        'product:2' => [
                            'category' => 'foo',
                        ],
                        'product:3' => [
                            'category' => 'bar',
                        ],
                        'product:4' => [
                            'category' => 'foo',
                        ],
                        'product:5' => [
                            'category' => 'foo,bar',
                            'category_array' => ['foo', 'bar'],
                        ],
                    ],
                ]
            ],
            'with_tag_equal_bar' => [
                new TagFilter('category', Condition::equal, 'bar'),
                [
                    'count' => 3,
                    'results' => [
                        'product:1' => [
                            'category' => 'bar',
                        ],
                        'product:3' => [
                            'category' => 'bar',
                        ],
                        'product:5' => [
                            'category' => 'foo,bar',
                            'category_array' => ['foo', 'bar'],
                        ],
                    ],
                ]
            ],
            'with_tag_equal_foo' => [
                new TagFilter('category', Condition::equal, 'foo'),
                [
                    'count' => 3,
                    'results' => [
                        'product:2' => [
                            'category' => 'foo',
                        ],
                        'product:4' => [
                            'category' => 'foo',
                        ],
                        'product:5' => [
                            'category' => 'foo,bar',
                            'category_array' => ['foo', 'bar'],
                        ],
                    ],
                ]
            ],
            'with_tag_not_equal_bar' => [
                new TagFilter('category', Condition::notEqual, 'bar'),
                [
                    'count' => 2,
                    'results' => [
                        'product:2' => [
                            'category' => 'foo',
                        ],
                        'product:4' => [
                            'category' => 'foo',
                        ],
                    ],
                ]
            ],
            'with_tag_not_equal_foo' => [
                new TagFilter('category', Condition::notEqual, 'foo'),
                [
                    'count' => 2,
                    'results' => [
                        'product:1' => [
                            'category' => 'bar',
                        ],
                        'product:3' => [
                            'category' => 'bar',
                        ],
                    ],
                ]
            ],
            'with_equal_foo_or_bar' => [
                new TagFilter('category', Condition::equal, [
                    'conjunction' => Logical::or,
                    'tags' => ['foo', 'bar'],
                ]),
                [
                    'count' => 5,
                    'results' => [
                        'product:1' => [
                            'category' => 'bar',
                        ],
                        'product:2' => [
                            'category' => 'foo',
                        ],
                        'product:3' => [
                            'category' => 'bar',
                        ],
                        'product:4' => [
                            'category' => 'foo',
                        ],
                        'product:5' => [
                            'category' => 'foo,bar',
                            'category_array' => ['foo', 'bar'],
                        ],
                    ],
                ]
            ],
            'with_not_equal_foo_or_bar' => [
                new TagFilter('category', Condition::notEqual, [
                    'conjunction' => Logical::or,
                    'tags' => ['foo', 'bar'],
                ]),
                [
                    'count' => 0,
                ]
            ],
            'with_equal_foo_and_bar' => [
                new TagFilter('category', Condition::equal, [
                    'conjunction' => Logical::and,
                    'tags' => ['foo', 'bar'],
                ]),
                [
                    'count' => 1,
                    'results' => [
                        'product:5' => [
                            'category' => 'foo,bar',
                            'category_array' => ['foo', 'bar'],
                        ],
                    ]
                ]
            ],
            'with_not_equal_foo_and_bar' => [
                new TagFilter('category', Condition::notEqual, [
                    'conjunction' => Logical::and,
                    'tags' => ['foo', 'bar'],
                ]),
                [
                    'count' => 0,
                ]
            ],
        ];
    }

    public static function vectorNumericFilterProvider(): array
    {
        return [
            'default' => [
                null,
                [
                    'count' => 4,
                    'results' => [
                        'product:1' => [
                            'price' => 10,
                        ],
                        'product:2' => [
                            'price' => 20,
                        ],
                        'product:3' => [
                            'price' => 30,
                        ],
                        'product:4' => [
                            'price' => 40,
                        ],
                    ],
                ]
            ],
            'with_equal' => [
                new NumericFilter('price', Condition::equal, 30),
                [
                    'count' => 1,
                    'results' => [
                        'product:3' => [
                            'price' => 30,
                        ],
                    ],
                ]
            ],
            'with_not_equal' => [
                new NumericFilter('price', Condition::notEqual, 30),
                [
                    'count' => 3,
                    'results' => [
                        'product:1' => [
                            'price' => 10,
                        ],
                        'product:2' => [
                            'price' => 20,
                        ],
                        'product:4' => [
                            'price' => 40,
                        ],
                    ],
                ]
            ],
            'with_greater_than' => [
                new NumericFilter('price', Condition::greaterThan, 20),
                [
                    'count' => 2,
                    'results' => [
                        'product:3' => [
                            'price' => 30,
                        ],
                        'product:4' => [
                            'price' => 40,
                        ],
                    ],
                ]
            ],
            'with_greater_than_or_equal' => [
                new NumericFilter('price', Condition::greaterThanOrEqual, 20),
                [
                    'count' => 3,
                    'results' => [
                        'product:2' => [
                            'price' => 20,
                        ],
                        'product:3' => [
                            'price' => 30,
                        ],
                        'product:4' => [
                            'price' => 40,
                        ],
                    ],
                ]
            ],
            'with_lower_than' => [
                new NumericFilter('price', Condition::lowerThan, 20),
                [
                    'count' => 1,
                    'results' => [
                        'product:1' => [
                            'price' => 10,
                        ],
                    ],
                ]
            ],
            'with_lower_than_or_equal' => [
                new NumericFilter('price', Condition::lowerThanOrEqual, 20),
                [
                    'count' => 2,
                    'results' => [
                        'product:1' => [
                            'price' => 10,
                        ],
                        'product:2' => [
                            'price' => 20,
                        ],
                    ],
                ]
            ],
            'with_between' => [
                new NumericFilter('price', Condition::between, [20, 30]),
                [
                    'count' => 2,
                    'results' => [
                        'product:2' => [
                            'price' => 20,
                        ],
                        'product:3' => [
                            'price' => 30,
                        ],
                    ],
                ]
            ],
        ];
    }
}