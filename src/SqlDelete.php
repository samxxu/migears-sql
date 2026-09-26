<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use MiGears\Sql\Exception\SqlException;

/**
 * DELETE query builder.
 *
 *   $sql->delete('users')
 *       ->where('id = :id', ['id' => 1])
 *       ->execute();
 */
class SqlDelete
{
    use HasWhereClause;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Generates the DELETE SQL string.
     */
    public function toSql(): string
    {
        $sql = "DELETE FROM `{$this->table}`";
        $sql .= $this->buildWhere();

        return $sql;
    }

    /**
     * Executes the DELETE, returns the number of affected rows.
     */
    public function execute(): int
    {
        $sql = $this->toSql();
        $this->logQuery($sql);

        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new SqlException("Failed to prepare statement: {$sql}");
        }
        $this->bindAllParams($stmt);
        if (!$stmt->execute()) {
            throw new SqlException("Failed to execute statement: {$sql}");
        }

        return $stmt->rowCount();
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getPdo(): PDO
    {
        return $this->pdo;
    }
}
