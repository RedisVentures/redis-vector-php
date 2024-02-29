<?php

namespace RedisVentures\RedisVl\Vectorizer;

use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class OpenAIVectorizer implements VectorizerInterface
{
    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $model;

    /**
     * @var string
     */
    private string $apiUrl;

    /**
     * API configuration accepts token and optional request parameters specified in documentation.
     *
     * Example: $apiConfiguration = ['token' => $token, 'requestParams' => [...]]
     *
     * @link https://platform.openai.com/docs/api-reference/embeddings/create
     * @param string|null $model
     * @param array $apiConfiguration
     * @param Client|null $client
     */
    public function __construct(
        string $model = null,
        private array $apiConfiguration = [],
        Client $client = null
    ) {
        $this->client = $client ?? new Client();
        $this->model = $model ?? 'text-embedding-ada-002';
        $this->apiUrl = getenv('OPENAI_API_URL') ?: 'https://api.openai.com/v1/embeddings';
    }

    /**
     * @inheritDoc
     */
    public function embed(string $text): array
    {
        $jsonResponse = $this->sendRequest(
            [
                'model' => $this->model,
                'input' => $text,
            ]
        )->getBody()->getContents();

        return json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function batchEmbed(array $texts): array
    {
        $jsonResponse = $this->sendRequest(
            [
                'model' => $this->model,
                'input' => $texts,
            ]
        )->getBody()->getContents();

        return json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(): array
    {
        return $this->apiConfiguration;
    }

    /**
     * Returns API token associated with current vectorizer.
     *
     * @return string
     * @throws Exception
     */
    private function getApiToken(): string
    {
        if (array_key_exists('token', $this->apiConfiguration)) {
            return $this->apiConfiguration['token'];
        }

        if (false !== $token = getenv('OPENAI_API_TOKEN')) {
            return $token;
        }

        throw new Exception(
            'API token should be provided in API configuration or as an environment variable.'
        );
    }

    /**
     * Sends an actual request to API endpoint.
     *
     * @param array $requestBody
     * @return ResponseInterface
     * @throws Exception|\GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest(array $requestBody): ResponseInterface
    {
        $requestParams = (array_key_exists('requestParams', $this->apiConfiguration))
            ? $this->apiConfiguration['requestParams']
            : [];
        $requestBody = array_merge($requestBody, $requestParams);

        return $this->client->post($this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $requestBody
        ]);
    }
}