<?php

namespace Vladvildanov\PredisVl\Unit\Index;

use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Vladvildanov\PredisVl\FactoryInterface;
use Vladvildanov\PredisVl\Index\SearchIndex;

class SearchIndexTest extends TestCase
{
    /**
     * @var Client
     */
    private Client $mockClient;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $mockFactory;

    /**
     * @var CreateArguments
     */
    private CreateArguments $mockIndexBuilder;

    /**
     * @var array
     */
    private array $schema;

    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock(Client::class);
        $this->mockFactory = Mockery::mock(FactoryInterface::class);
        $this->mockIndexBuilder = Mockery::mock(CreateArguments::class);

        $this->schema = [
            'index' => [
                'name' => 'foobar',
                'storage_type' => 'hash',
            ],
            'fields' => [
                'foo' => [
                    'type' => 'text',
                ]
            ]
        ];
    }

    /**
     * @dataProvider validateSchemaProvider
     * @param array $schema
     * @param string $exceptionMessage
     * @return void
     */
    public function testValidateSchemaThrowsException(array $schema, string $exceptionMessage): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        new SearchIndex($this->mockClient, $schema);
    }

    /**
     * @return void
     */
    public function testCreateWithNoPrefixWithNoOverride(): void
    {
        $expectedSchema = [
            new TextField('foo'),
        ];

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $this->schema, $this->mockFactory);

        $this->assertTrue($index->create());
    }

    /**
     * @return void
     */
    public function testCreateWithOverride(): void
    {
        $expectedSchema = [
            new TextField('foo'),
        ];

        $this->mockClient
            ->shouldReceive('ftdropindex')
            ->once()
            ->with('foobar');

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $this->schema, $this->mockFactory);

        $this->assertTrue($index->create(true));
    }

    /**
     * @return void
     */
    public function testCreateWithOverrideDoesNotFailsOnCommandError(): void
    {
        $expectedSchema = [
            new TextField('foo'),
        ];

        $this->mockClient
            ->shouldReceive('ftdropindex')
            ->once()
            ->with('foobar')
            ->andThrow(ServerException::class);

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $this->schema, $this->mockFactory);

        $this->assertTrue($index->create(true));
    }

    /**
     * @return void
     */
    public function testCreateReturnsFalseOnCommandError(): void
    {
        $expectedSchema = [
            new TextField('foo'),
        ];

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new ServerException('Error'));

        $index = new SearchIndex($this->mockClient, $this->schema, $this->mockFactory);

        $this->assertFalse($index->create());
    }

    /**
     * @return void
     */
    public function testCreateWithNoPrefixWithNoOverrideWithNoStorageType(): void
    {
        $expectedSchema = [
            new TextField('foo'),
        ];

        $schema = $this->schema;
        unset($schema['index']['storage_type']);

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $schema, $this->mockFactory);

        $this->assertTrue($index->create());
    }

    /**
     * @dataProvider prefixProvider
     * @param $prefix
     * @return void
     */
    public function testCreateWithPrefixWithNoOverride($prefix): void
    {
        $expectedSchema = [
            new TextField('foo'),
        ];
        $schema = $this->schema;
        $schema['index']['prefix'] = $prefix;

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('prefix')
            ->once()
            ->with([$prefix])
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $schema, $this->mockFactory);

        $this->assertTrue($index->create());
    }

    /**
     * @return void
     */
    public function testCreateWithAlias(): void
    {
        $expectedSchema = [
            new TextField('foo', 'bar'),
        ];

        $schema = $this->schema;
        $schema['fields']['foo']['alias'] = 'bar';

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $schema, $this->mockFactory);

        $this->assertTrue($index->create());
    }

    /**
     * @return void
     */
    public function testCreateWithMultipleFields(): void
    {
        $expectedSchema = [
            new TextField('foo'),
            new VectorField('bar', 'FLAT',
                [
                    'TYPE', 'FLOAT32',
                    'DIM', 3,
                    'DISTANCE_METRIC', 'COSINE'
                ]
            ),
        ];

        $schema = $this->schema;
        $schema['fields']['bar'] = [
            'type' => 'vector',
            'dims' => 3,
            'distance_metric' => 'cosine',
            'algorithm' => 'flat',
            'datatype' => 'float32'
        ];

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $expectedSchema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $schema, $this->mockFactory);

        $this->assertTrue($index->create());
    }

    /**
     * @return void
     */
    public function testCreateThrowsExceptionOnMissingMandatoryVectorFields(): void
    {
        $schema = $this->schema;
        $schema['fields']['bar'] = [
            'type' => 'vector',
            'distance_metric' => 'cosine',
            'algorithm' => 'flat',
            'datatype' => 'float32'
        ];

        $this->mockFactory
            ->shouldReceive('createIndexBuilder')
            ->once()
            ->withNoArgs()
            ->andReturn($this->mockIndexBuilder);

        $this->mockIndexBuilder
            ->shouldReceive('on')
            ->once()
            ->with('HASH')
            ->andReturn($this->mockIndexBuilder);

        $this->mockClient
            ->shouldReceive('ftcreate')
            ->once()
            ->with('foobar', $schema, $this->mockIndexBuilder)
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $schema, $this->mockFactory);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("datatype, dims, distance_metric and algorithm are mandatory parameters for vector field.");

        $this->assertTrue($index->create());
    }

    /**
     * @return void
     */
    public function testLoadHash(): void
    {
        $this->mockClient
            ->shouldReceive('hmset')
            ->once()
            ->with('key', ['key' => 'value'])
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $this->schema);

        $this->assertTrue($index->load('key',['key' => 'value']));
    }

    /**
     * @return void
     */
    public function testLoadJson(): void
    {
        $this->mockClient
            ->shouldReceive('jsonset')
            ->once()
            ->with('key', '$', '{"key":"value"}')
            ->andReturn(new Status('OK'));

        $index = new SearchIndex($this->mockClient, $this->schema);

        $this->assertTrue($index->load('key','{"key":"value"}'));
    }

    /**
     * @return void
     */
    public function testLoadReturnsFalseOnCommandError(): void
    {
        $this->mockClient
            ->shouldReceive('jsonset')
            ->once()
            ->with('key', '$', '{"key":"value"}')
            ->andReturn(new ServerException('Error'));

        $index = new SearchIndex($this->mockClient, $this->schema);

        $this->assertFalse($index->load('key','{"key":"value"}'));
    }

    /**
     * @return void
     */
    public function testFetchWithPrefix(): void
    {
        $schema = $this->schema;
        $schema['index']['prefix'] = 'prefix:';

        $this->mockClient
            ->shouldReceive('hgetall')
            ->once()
            ->with('prefix:key')
            ->andReturn(['key' => 'value']);

        $index = new SearchIndex($this->mockClient, $schema);

        $this->assertSame(['key' => 'value'], $index->fetch('key'));
    }

    /**
     * @return void
     */
    public function testFetchHash(): void
    {
        $schema = $this->schema;
        unset($schema['index']['storage_type']);

        $this->mockClient
            ->shouldReceive('hgetall')
            ->once()
            ->with('key')
            ->andReturn(['key' => 'value']);

        $index = new SearchIndex($this->mockClient, $schema);

        $this->assertSame(['key' => 'value'], $index->fetch('key'));
    }

    /**
     * @return void
     */
    public function testFetchJson(): void
    {
        $schema = $this->schema;
        $schema['index']['storage_type'] = 'json';

        $this->mockClient
            ->shouldReceive('jsonget')
            ->once()
            ->with('key')
            ->andReturn('{"key":"value"}');

        $index = new SearchIndex($this->mockClient, $schema);

        $this->assertSame('{"key":"value"}', $index->fetch('key'));
    }

    /**
     * @return void
     */
    public function testGetSchema(): void
    {
        $index = new SearchIndex($this->mockClient, [
            'index' => ['name' => 'bar'],
            'fields' => [
                'foo' => ['type' => 'tag']
            ]
        ]);

        $this->assertSame([
            'index' => ['name' => 'bar'],
            'fields' => [
                'foo' => ['type' => 'tag']
            ]
        ], $index->getSchema());
    }

    public static function validateSchemaProvider(): array
    {
        return [
            'no_index' => [
                ['fields' => ['foo' => 'bar']],
                "Schema should contains 'index' entry.",
            ],
            'no_index_name' => [
                ['index' => ['foo' => 'bar']],
                "Index name is required.",
            ],
            'incorrect_storage_type' => [
                ['index' => ['name' => 'bar', 'storage_type' => 'foo']],
                "Invalid storage type value.",
            ],
            'no_fields' => [
                ['index' => ['name' => 'bar']],
                "Schema should contains at least one field.",
            ],
            'no_field_type' => [
                ['index' => ['name' => 'bar'], 'fields' => ['foo' => []]],
                "Field type should be specified for each field.",
            ],
            'incorrect_field_type' => [
                ['index' => ['name' => 'bar'], 'fields' => ['foo' => ['type' => 'bar']]],
                "Invalid field type.",
            ],
        ];
    }

    public static function prefixProvider(): array
    {
        return [[
            'prefix:',
            ['prefix1:', 'prefix2:']
        ]];
    }
}