<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use MiGears\Sql\Exception\SqlException;

/**
 * INSERT query builder.
 *
 *   $id = $sql->insert('users')
 *       ->values(['name' => 'Alice', 'age' => 25])
 *       ->execute()
 *       ->lastInsertId();
 */
final class SqlInsert
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Sets the insert data (key-value array).
     *
     * @param array<string, mixed> $data
     */
    public function values(array $data): self
    {
        $this->values = $data;
        return $this;
    }

    /**
     * Generates the INSERT SQL string.
     */
    public function toSql(): string
    {
        if ($this->values === []) {
            throw new SqlException('Insert values must be provided via values()');
        }

        $columns = array_keys($this->values);
        $cols = '`' . implode('`, `', $columns) . '`';
        $placeholders = ':' . implode(', :', $columns);

        return "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$placeholders})";
    }

    /**
     * Executes the INSERT, returns self for chained lastInsertId() / rowCount() calls.
     */
    public function execute(): self
    {
        $sql = $this->toSql();
        $this->logger->debug('SQL: {sql}', ['sql' => $sql, 'params' => $this->values]);

        $stmt = $this->pdo->prepare($sql);
        foreach ($this->values as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $this->affectedRows = $stmt->rowCount();
        return $this;
    }

    private int $affectedRows = 0;

    /**
     * Returns the number of affected rows.
     */
    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    /**
     * Returns the last inserted ID.
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
}
