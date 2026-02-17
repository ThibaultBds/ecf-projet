<?php

namespace App\Core;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;

class MongoDB
{
    private static $instance = null;
    private $manager;
    private $database = 'ecoride';

    private function __construct()
    {
        $uri = getenv('MONGO_URL') ?: 'mongodb://mongo:27017';
        $this->manager = new Manager($uri);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getManager(): Manager
    {
        return $this->manager;
    }

    public function findOne(string $collection, array $filter): ?array
    {
        $query = new Query($filter, ['limit' => 1]);
        $cursor = $this->manager->executeQuery("{$this->database}.{$collection}", $query);
        $results = $cursor->toArray();

        if (empty($results)) {
            return null;
        }

        return (array) $results[0];
    }

    public function find(string $collection, array $filter = [], array $options = []): array
    {
        $query = new Query($filter, $options);
        $cursor = $this->manager->executeQuery("{$this->database}.{$collection}", $query);

        $results = [];
        foreach ($cursor as $doc) {
            $results[] = (array) $doc;
        }
        return $results;
    }

    public function insertOne(string $collection, array $document): void
    {
        $bulk = new BulkWrite();
        $bulk->insert($document);
        $this->manager->executeBulkWrite("{$this->database}.{$collection}", $bulk);
    }

    public function upsert(string $collection, array $filter, array $data): void
    {
        $bulk = new BulkWrite();
        $bulk->update($filter, ['$set' => $data], ['upsert' => true]);
        $this->manager->executeBulkWrite("{$this->database}.{$collection}", $bulk);
    }

    public function delete(string $collection, array $filter): void
    {
        $bulk = new BulkWrite();
        $bulk->delete($filter);
        $this->manager->executeBulkWrite("{$this->database}.{$collection}", $bulk);
    }
}
