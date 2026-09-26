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
class SqlUpdate
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
            $this->assertValidColumn((string) $key);
            $segments[] = "`{$key}` = :set_{$key}";
        }

        $setClause = implode(', ', $segments);
        $sql = "UPDATE `{$this->table}` SET {$setClause}";
        $sql .= $this->buildWhere();

        return $sql;
    }

    /**
     * Executes the UPDATE, returns the number of affected rows.
     *
     * Refuses to run without a condition, so an accidental update()->execute()
     * cannot rewrite a whole table. Add an explicit condition such as
     * where('1 = 1') to update every row on purpose.
     */
    public function execute(): int
    {
        if ($this->buildWhere() === '') {
            throw new SqlException(
                "Refusing to update `{$this->table}` without a condition; "
                . "add where()/filter(), or use where('1 = 1') to update every row deliberately"
            );
        }

        $sql = $this->toSql();
        $this->logUpdateQuery($sql);

        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new SqlException("Failed to prepare statement: {$sql}");
        }

        // Bind SET parameters (set_ prefix avoids conflicts with WHERE parameters)
        foreach ($this->setValues as $key => $value) {
            $stmt->bindValue("set_{$key}", $value);
        }

        $this->bindAllParams($stmt);
        if (!$stmt->execute()) {
            throw new SqlException("Failed to execute statement: {$sql}");
        }

        return $stmt->rowCount();
    }

    private function logUpdateQuery(string $sql): void
    {
        $params = array_merge($this->setValues, $this->bindParams, $this->filterParams);
        $this->logger->debug('SQL: {sql}', ['sql' => $sql, 'params' => $params]);
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Rejects column names that could break out of the backtick-quoted
     * identifier (e.g. "name` = 'HACKED' -- ").
     */
    private function assertValidColumn(string $column): void
    {
        if (!preg_match('/^\w+$/', $column)) {
            throw new SqlException("Invalid column name: {$column}");
        }
    }
}
