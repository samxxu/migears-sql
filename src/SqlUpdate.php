<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use MiGears\Sql\Exception\SqlException;

/**
 * UPDATE query builder.
 *
 *   $sql->update('users')
 *       ->set(['name' => 'Bob', 'age' => 30])
 *       ->where('id = :id', ['id' => 1])
 *       ->execute();
 */
final class SqlUpdate
{
    use HasWhereClause;

    /** @var array<string, mixed> SET data as key-value pairs */
    private array $setValues = [];

    /** @var string|null Raw SET expression (string form) */
    private ?string $setRaw = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Sets the fields to update.
     *
     * - Pass an array: key-value pairs, generates `col` = :col form
     * - Pass a string: raw SET expression (e.g. "counter = counter + 1")
     *
     * @param array<string, mixed>|string $data
     */
    public function set(array|string $data): self
    {
        if (is_string($data)) {
            $this->setRaw = $data;
        } else {
            $this->setValues = $data;
        }
        return $this;
    }

    /**
     * Generates the UPDATE SQL string.
     */
    public function toSql(): string
    {
        if ($this->setValues === [] && $this->setRaw === null) {
            throw new SqlException('Update data must be provided via set()');
        }

        $segments = [];

        if ($this->setRaw !== null) {
            $segments[] = $this->setRaw;
        }

        foreach (array_keys($this->setValues) as $key) {
            $segments[] = "`{$key}` = :set_{$key}";
        }

        $setClause = implode(', ', $segments);
        $sql = "UPDATE `{$this->table}` SET {$setClause}";
        $sql .= $this->buildWhere();

        return $sql;
    }

    /**
     * Executes the UPDATE, returns the number of affected rows.
     */
    public function execute(): int
    {
        $sql = $this->toSql();
        $this->logUpdateQuery($sql);

        $stmt = $this->pdo->prepare($sql);

        // Bind SET parameters (set_ prefix avoids conflicts with WHERE parameters)
        foreach ($this->setValues as $key => $value) {
            $stmt->bindValue("set_{$key}", $value);
        }

        $this->bindAllParams($stmt);
        $stmt->execute();

        return $stmt->rowCount();
    }

    private function logUpdateQuery(string $sql): void
    {
        $params = array_merge($this->setValues, $this->bindParams, $this->filterParams);
        $this->logger->debug('SQL: {sql}', ['sql' => $sql, 'params' => $params]);
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    private function getPdo(): PDO
    {
        return $this->pdo;
    }
}
