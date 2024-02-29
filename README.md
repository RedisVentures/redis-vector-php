## Introduction ##

The Redis Vector Library (RedisVL) is a PHP client for AI applications leveraging Redis.

Designed for:
- Vector similarity search
- Recommendation engine

A perfect tool for Redis-based applications, incorporating capabilities like vector-based semantic search,
full-text search, and geo-spatial search.

## Getting started ##

### Installation ###
```shell
composer install redis-ventures/redisvl
```

### Setting up Redis ####

Choose from multiple Redis deployment options:
1. [Redis Cloud](https://redis.com/try-free/): Managed cloud database (free tier available)
2. [Redis Stack](https://redis.io/docs/install/install-stack/docker/): Docker image for development
```shell
docker run -d --name redis-stack -p 6379:6379 -p 8001:8001 redis/redis-stack:latest
```
3. [Redis Enterprise](https://redis.com/redis-enterprise/advantages/): Commercial, self-hosted database

## What's included? ##

### Redis index management ###

1. Design your schema that models your dataset with one of the available Redis data structures (HASH, JSON)
and indexable fields (e.g. text, tags, numerics, geo, and vectors).

Load schema as a dictionary:
```php
$schema = [
    'index' => [
        'name' => 'products',
        'prefix' => 'product:',
        'storage_type' => 'hash',
    ],
    'fields' => [
        'id' => [
            'type' => 'numeric',
        ],
        'categories' => [
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
```
2. Create a SearchIndex object with an input schema and client connection to be able to interact with your Redis index
```php
use Predis\Client;
use RedisVentures\RedisVl\Index\SearchIndex;

$client = new Client();
$index = new SearchIndex($client, $schema);

// Creates index in the Redis
$index->create();
```
3. Load/fetch your data from index. If you have a hash index data should be loaded as key-value pairs
, for json type data loads as json string.
```php
$data = ['id' => '1', 'count' => 10, 'id_embeddings' => VectorHelper::toBytes([0.000001, 0.000002, 0.000003])];

// Loads given dataset associated with given key.
$index->load('key', $data);

// Fetch dataset corresponding to given key
$index->fetch('key');
```

### Realtime search ###

Define queries and perform advanced search over your indices, including combination of vectors and variety of filters.

**VectorQuery** - flexible vector-similarity semantic search with customizable filters
```php
use RedisVentures\RedisVl\Query\VectorQuery;

$query = new VectorQuery(
    [0.001, 0.002, 0.03],
    'description_embedding',
    null,
    3
);

// Run vector search against vector field specified in schema.
$results = $index->query($query);
```

Incorporate complex metadata filters on your queries:
```php
use RedisVentures\RedisVl\Query\Filter\TagFilter;
use RedisVentures\RedisVl\Enum\Condition;

$filter = new TagFilter(
    'categories',
    Condition::equal,
    'foo'
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

// Results will be filtered by tag field values.
$results = $index->query($query);
```

### Filter types ###

#### Numeric ####

Numeric filters could be applied to numeric fields. 
Supports variety of conditions applicable for scalar types (==, !=, <, >, <=, >=).
More information [here](https://redis.io/docs/interact/search-and-query/query/range/).
```php
use RedisVentures\RedisVl\Query\Filter\NumericFilter;
use RedisVentures\RedisVl\Enum\Condition;

$equal = new NumericFilter('numeric', Condition::equal, 10);
$notEqual = new NumericFilter('numeric', Condition::notEqual, 10);
$greaterThan = new NumericFilter('numeric', Condition::greaterThan, 10);
$greaterThanOrEqual = new NumericFilter('numeric', Condition::greaterThanOrEqual, 10);
$lowerThan = new NumericFilter('numeric', Condition::lowerThan, 10);
$lowerThanOrEqual = new NumericFilter('numeric', Condition::lowerThanOrEqual, 10);
```

#### Tag ####

Tag filters could be applied to tag fields. Single or multiple values can be provided, single values supports only 
equality conditions (==, !==), for multiple tags additional conjunction (AND, OR) could be specified.
More information [here](https://redis.io/docs/interact/search-and-query/advanced-concepts/tags/)
```php
use RedisVentures\RedisVl\Query\Filter\TagFilter;
use RedisVentures\RedisVl\Enum\Condition;
use RedisVentures\RedisVl\Enum\Logical;

$singleTag = new TagFilter('tag', Condition::equal, 'value')
$multipleTags = new TagFilter('tag', Condition::notEqual, [
    'conjunction' => Logical::or,
    'tags' => ['value1', 'value2']
])
```

#### Text ####

Text filters could be applied to text fields. Values can be provided as a single word or multiple words with
specified condition. Empty value corresponds to all values (*). 
More information [here](https://redis.io/docs/interact/search-and-query/query/full-text/)
```php
use RedisVentures\RedisVl\Query\Filter\TextFilter;
use RedisVentures\RedisVl\Enum\Condition;

$single = new TextFilter('text', Condition::equal, 'foo');

// Matching foo AND bar
$multipleAnd = new TextFilter('text', Condition::equal, 'foo bar');

// Matching foo OR bar
$multipleOr = new TextFilter('text', Condition::equal, 'foo|bar');

// Perform fuzzy search
$fuzzy = new TextFilter('text', Condition::equal, '%foobaz%');
```

#### Geo ####

Geo filters could be applied to geo fields. Supports only equality conditions, 
value should be specified as specific-shape array. 
More information [here](https://redis.io/docs/interact/search-and-query/query/geo-spatial/)
```php
use RedisVentures\RedisVl\Query\Filter\GeoFilter;
use RedisVentures\RedisVl\Enum\Condition;
use RedisVentures\RedisVl\Enum\Unit;

$geo = new GeoFilter('geo', Condition::equal, [
    'lon' => 10.111,
    'lat' => 11.111,
    'radius' => 100,
    'unit' => Unit::kilometers
]);
```

#### Aggregate ####

To apply multiple filters to a single query use AggregateFilter. 
If there's the same logical operator that should be applied for each filter you can pass values in constructor,  
if you need a specific combination use `and()` and `or()` methods to create combined filter.
```php
use RedisVentures\RedisVl\Query\Filter\AggregateFilter;
use RedisVentures\RedisVl\Query\Filter\TextFilter;
use RedisVentures\RedisVl\Query\Filter\NumericFilter;
use RedisVentures\RedisVl\Enum\Condition;
use RedisVentures\RedisVl\Enum\Logical;

$aggregate = new AggregateFilter([
    new TextFilter('text', Condition::equal, 'value'),
    new NumericFilter('numeric', Condition::greaterThan, 10)
], Logical::or);

$combinedAggregate = new AggregateFilter();
$combinedAggregate
    ->and(
        new TextFilter('text', Condition::equal, 'value'),
        new NumericFilter('numeric', Condition::greaterThan, 10)
    )->or(
        new NumericFilter('numeric', Condition::lowerThan, 100)
    );
```

## Vectorizers ##

To be able to effectively create vector representations for your indexed data or queries, you have to use 
[LLM's](https://en.wikipedia.org/wiki/Large_language_model). There's a variety of vectorizers that provide integration
with popular embedding models.

The only required option is your API key specified as environment variable or configuration option.

### OpenAI ###
```php
use RedisVentures\RedisVl\Vectorizer\Factory;

putenv('OPENAI_API_TOKEN=your_token');

$factory = new Factory();
$vectorizer = $factory->createVectorizer('openai');

// Creates vector representation of given text.
$embedding = $vectorizer->embed('your_text')

// Creates a single vector representation from multiple chunks.
$mergedEmbedding = $vectorizer->batchEmbed(['first_chunk', 'second_chunk']);
```

### VectorHelper ###

When you perform vector queries against Redis or load hash data into index that contains vector field data, 
your vector should be represented as a blob string. VectorHelper allows you to create
blob representation from your vector represented as array of floats.
```php
use RedisVentures\RedisVl\VectorHelper;

$blobVector = VectorHelper::toBytes([0.001, 0.002, 0.003]);
```