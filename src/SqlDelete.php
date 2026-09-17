<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
        $this->bindAllParams($stmt);
        $stmt->execute();

        return $stmt->rowCount();
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
