<?php

namespace RedisVentures\PredisVl\Unit\Vectorizer;

use Exception;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RedisVentures\PredisVl\Vectorizer\OpenAIVectorizer;

class OpenAIVectorizerTest extends TestCase
{
    /**
     * @var Client
     */
    private Client $mockClient;

    /**
     * @var ResponseInterface
     */
    private ResponseInterface $mockResponse;

    /**
     * @var StreamInterface
     */
    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        $this->mockClient = $this->getMockBuilder(Client::class)->getMock();
        $this->mockResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
    }

    /**
     * @return void
     */
    public function testGetModel(): void
    {
        $vectorizer = new OpenAIVectorizer('test');

        $this->assertSame('test', $vectorizer->getModel());
    }

    /**
     * @return void
     */
    public function testGetConfiguration(): void
    {
        $vectorizer = new OpenAIVectorizer(null, ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $vectorizer->getConfiguration());
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testEmbedIncludeRequestParametersFromApiConfiguration(): void
    {
        $expectedBody = [
            'model' => 'test model',
            'input' => 'text',
            'encoding_format' => 'float',
        ];

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/embeddings',
                [
                    'headers' => [
                        'Authorization' => 'Bearer foobar',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $expectedBody,
                ]
            )
            ->willReturn($this->mockResponse);


        $this->mockResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"embeddings":[0.1111111,0.2222222]}');

        $vectorizer = new OpenAIVectorizer(
            'test model',
            ['token' => 'foobar', 'requestParams' => ['encoding_format' => 'float']],
            $this->mockClient
        );

        $this->assertSame(['embeddings' => [0.1111111, 0.2222222]], $vectorizer->embed('text'));
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testEmbedWithNoRequestParams(): void
    {
        $expectedBody = [
            'model' => 'test model',
            'input' => 'text',
        ];

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/embeddings',
                [
                    'headers' => [
                        'Authorization' => 'Bearer foobar',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $expectedBody,
                ]
            )
            ->willReturn($this->mockResponse);


        $this->mockResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"embeddings":[0.1111111,0.2222222]}');

        $vectorizer = new OpenAIVectorizer(
            'test model',
            ['token' => 'foobar'],
            $this->mockClient
        );

        $this->assertSame(['embeddings' => [0.1111111, 0.2222222]], $vectorizer->embed('text'));
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testEmbedGetApiTokenFromEnvVariable(): void
    {
        $expectedBody = [
            'model' => 'test model',
            'input' => 'text',
        ];

        putenv('OPENAI_API_TOKEN=foobar');

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/embeddings',
                [
                    'headers' => [
                        'Authorization' => 'Bearer foobar',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $expectedBody,
                ]
            )
            ->willReturn($this->mockResponse);


        $this->mockResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"embeddings":[0.1111111,0.2222222]}');

        $vectorizer = new OpenAIVectorizer(
            'test model',
            [],
            $this->mockClient
        );

        $this->assertSame(['embeddings' => [0.1111111, 0.2222222]], $vectorizer->embed('text'));
        putenv('OPENAI_API_TOKEN');
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testEmbedThrowsExceptionOnMissingApiToken(): void
    {
        $vectorizer = new OpenAIVectorizer(
            'test model',
            [],
            $this->mockClient
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API token should be provided in API configuration or as an environment variable.');

        $vectorizer->embed('text');
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testBatchEmbed(): void
    {
        $expectedBody = [
            'model' => 'test model',
            'input' => ['foo', 'bar'],
        ];

        $this->mockClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/embeddings',
                [
                    'headers' => [
                        'Authorization' => 'Bearer foobar',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $expectedBody,
                ]
            )
            ->willReturn($this->mockResponse);


        $this->mockResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('{"embeddings":[0.1111111,0.2222222]}');

        $vectorizer = new OpenAIVectorizer(
            'test model',
            ['token' => 'foobar'],
            $this->mockClient
        );

        $this->assertSame(['embeddings' => [0.1111111, 0.2222222]], $vectorizer->batchEmbed(['foo', 'bar']));
    }
}