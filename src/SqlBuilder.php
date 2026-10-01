<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * SQL query builder entry point (factory class).
 *
 * Accepts a PDO instance, zero global dependencies, ready to use after new.
 * The logger is required: a call site that forgets it fails here at assembly
 * time instead of running on silently.
 *
 *   $sql = new SqlBuilder($pdo, $logger);
 *   $rows = $sql->select()->from('users')->where('id = :id', ['id' => 1])->execute();
 */
class SqlBuilder
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Creates a SELECT query builder.
     *
     * @param string|string[]|null $fields Field names (string, array, or null for *)
     */
    public function select(string|array|null $fields = null): SqlSelect
    {
        return new SqlSelect($this->pdo, $this->logger, $fields);
    }

    /**
     * Creates an INSERT builder.
     */
    public function insert(string $table): SqlInsert
    {
        return new SqlInsert($this->pdo, $table, $this->logger);
    }

    /**
     * Creates an UPDATE builder.
     */
    public function update(string $table): SqlUpdate
    {
        return new SqlUpdate($this->pdo, $table, $this->logger);
    }

    /**
     * Creates a DELETE builder.
     */
    public function delete(string $table): SqlDelete
    {
        return new SqlDelete($this->pdo, $table, $this->logger);
    }

    /**
     * Gets the underlying PDO instance.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
