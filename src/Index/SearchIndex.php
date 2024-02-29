<?php

namespace RedisVentures\RedisVl\Index;

use Exception;
use Predis\Client;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Response\ServerException;
use RedisVentures\RedisVl\Enum\SearchField;
use RedisVentures\RedisVl\Enum\StorageType;
use RedisVentures\RedisVl\Factory;
use RedisVentures\RedisVl\FactoryInterface;
use RedisVentures\RedisVl\Query\QueryInterface;

class SearchIndex implements IndexInterface
{
    /**
     * @var array
     */
    protected array $schema;

    /**
     * @var FactoryInterface
     */
    protected FactoryInterface $factory;

    public function __construct(protected Client $client, array $schema, FactoryInterface $factory = null)
    {
        $this->validateSchema($schema);
        $this->factory = $factory ?? new Factory();
    }

    /**
     * @inheritDoc
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @inheritDoc
     */
    public function create(bool $isOverwrite = false): bool
    {
        if ($isOverwrite) {
            try {
                $this->client->ftdropindex($this->schema['index']['name']);
            } catch (ServerException $exception) {
                // Do nothing on exception, there's no way to check if index already exists.
            }
        }

        $createArguments = $this->factory->createIndexBuilder();

        if (array_key_exists('storage_type', $this->schema['index'])) {
            $createArguments = $createArguments->on(
                StorageType::from(strtoupper($this->schema['index']['storage_type']))->value
            );
        } else {
            $createArguments = $createArguments->on();
        }

        if (array_key_exists('prefix', $this->schema['index'])) {
            $createArguments = $createArguments->prefix([$this->schema['index']['prefix']]);
        }

        $schema = [];

        foreach ($this->schema['fields'] as $fieldName => $fieldData) {
            $fieldEnum = SearchField::fromName($fieldData['type']);

            if (array_key_exists('alias', $fieldData)) {
                $alias = $fieldData['alias'];
            } else {
                $alias = '';
            }

            if ($fieldEnum === SearchField::vector) {
                $schema[] = $this->createVectorField($fieldName, $alias, $fieldData);
            } else {
                $fieldClass = $fieldEnum->fieldMapping();
                $schema[] = new $fieldClass($fieldName, $alias);
            }
        }

        $response = $this->client->ftcreate($this->schema['index']['name'], $schema, $createArguments);

        return $response == 'OK';
    }

    /**
     * Loads data into current index.
     * Accepts array for hashes and string for JSON type.
     */
    public function load(string $key, mixed $values): bool
    {
        $key = (array_key_exists('prefix', $this->schema['index']))
            ? $this->schema['index']['prefix'] . $key
            : $key;

        if (is_string($values)) {
            $response = $this->client->jsonset($key, '$', $values);
        } elseif (is_array($values)) {
            $response = $this->client->hmset($key, $values);
        }

        return $response == 'OK';
    }

    /**
     * @inheritDoc
     */
    public function fetch(string $id): mixed
    {
        $key = (array_key_exists('prefix', $this->schema['index']))
            ? $this->schema['index']['prefix'] . $id
            : $id;

        if (
            array_key_exists('storage_type', $this->schema['index'])
            && StorageType::from(strtoupper($this->schema['index']['storage_type'])) === StorageType::json
        ) {
            return $this->client->jsonget($key);
        }

        return $this->client->hgetall($key);
    }

    /**
     * @inheritDoc
     */
    public function query(QueryInterface $query)
    {
        $response = $this->client->ftsearch(
            $this->schema['index']['name'],
            $query->getQueryString(),
            $query->getSearchArguments()
        );

        $processedResponse = ['count' => $response[0]];
        $withScores = in_array('WITHSCORES', $query->getSearchArguments()->toArray(), true);

        if (count($response) > 1) {
            for ($i = 1, $iMax = count($response); $i < $iMax; $i++) {
                $processedResponse['results'][$response[$i]] = [];

                // Different return type depends on WITHSCORE condition
                if ($withScores) {
                    $processedResponse['results'][$response[$i]]['score'] = $response[$i + 1];
                    $step = 2;
                } else {
                    $step = 1;
                }

                for ($j = 0, $jMax = count($response[$i + $step]); $j < $jMax; $j++) {
                    $processedResponse['results'][$response[$i]][$response[$i + $step][$j]] = $response[$i + $step][$j + 1];
                    ++$j;
                }

                $i += $step;
            }
        }

        return $processedResponse;
    }

    /**
     * Validates schema array.
     *
     * @param array $schema
     * @return void
     * @throws Exception
     */
    protected function validateSchema(array $schema): void
    {
        if (!array_key_exists('index', $schema)) {
            throw new Exception("Schema should contains 'index' entry.");
        }

        if (!array_key_exists('name', $schema['index'])) {
            throw new Exception("Index name is required.");
        }

        if (
            array_key_exists('storage_type', $schema['index']) &&
            null === StorageType::tryFrom(strtoupper($schema['index']['storage_type']))
        ) {
            throw new Exception('Invalid storage type value.');
        }

        if (!array_key_exists('fields', $schema)) {
            throw new Exception('Schema should contains at least one field.');
        }

        foreach ($schema['fields'] as $fieldData) {
            if (!array_key_exists('type', $fieldData)) {
                throw new Exception('Field type should be specified for each field.');
            }

            if (!in_array($fieldData['type'], SearchField::names(), true)) {
                throw new Exception('Invalid field type.');
            }
        }

        $this->schema = $schema;
    }

    /**
     * Creates a Vector field from given configuration.
     *
     * @param string $fieldName
     * @param string $alias
     * @param array $fieldData
     * @return VectorField
     * @throws Exception
     */
    protected function createVectorField(string $fieldName, string $alias, array $fieldData): VectorField
    {
        $mandatoryKeys = ['datatype', 'dims', 'distance_metric', 'algorithm'];
        $intersections = array_intersect($mandatoryKeys, array_keys($fieldData));

        if (count($intersections) !== count($mandatoryKeys)) {
            throw new Exception("datatype, dims, distance_metric and algorithm are mandatory parameters for vector field.");
        }

        return new VectorField(
            $fieldName,
            strtoupper($fieldData['algorithm']),
            [
                'TYPE', strtoupper($fieldData['datatype']),
                'DIM', strtoupper($fieldData['dims']),
                'DISTANCE_METRIC', strtoupper($fieldData['distance_metric'])
            ],
            $alias
        );
    }
}