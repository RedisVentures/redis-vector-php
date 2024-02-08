<?php

namespace Vladvildanov\PredisVl\Feature\Index;

use Vladvildanov\PredisVl\Feature\FeatureTestCase;
use Predis\Client;
use Vladvildanov\PredisVl\Index\SearchIndex;
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
}