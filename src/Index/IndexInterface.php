<?php

namespace RedisVentures\RedisVl\Index;

use RedisVentures\RedisVl\Query\QueryInterface;

interface IndexInterface
{
    /**
     * Returns schema configuration for index.
     *
     * @return array
     */
    public function getSchema(): array;

    /**
     * Creates index entity according to given schema.
     *
     * @param bool $isOverwrite
     * @return bool
     */
    public function create(bool $isOverwrite): bool;

    /**
     * Loads data into current index.
     *
     * @param string $key
     * @param mixed $values
     * @return bool
     */
    public function load(string $key, mixed $values): bool;

    /**
     * Fetch one of the previously loaded objects.
     *
     * @param string $id
     * @return mixed
     */
    public function fetch(string $id): mixed;

    /**
     * Query for a data from current index.
     *
     * @param QueryInterface $query
     * @return mixed
     */
    public function query(QueryInterface $query);
}