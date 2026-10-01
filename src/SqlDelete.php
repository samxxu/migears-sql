<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Generates the DELETE SQL string.
     *
     * The table name is wrapped in backticks, so it must be a bare identifier;
     * anything else could break out of the quoting and is rejected here.
     */
    public function toSql(): string
    {
        if (!preg_match('/^\w+$/', $this->table)) {
            throw new SqlException("Invalid table name: {$this->table}");
        }

        $sql = "DELETE FROM `{$this->table}`";
        $sql .= $this->buildWhere();

        return $sql;
    }

    /**
     * Executes the DELETE, returns the number of affected rows.
     *
     * Refuses to run without a condition, so an accidental delete()->execute()
     * cannot empty a table. Add an explicit condition such as where('1 = 1')
     * to delete every row on purpose.
     */
    public function execute(): int
    {
        if ($this->buildWhere() === '') {
            throw new SqlException(
                "Refusing to delete from `{$this->table}` without a condition; "
                . "add where()/filter(), or use where('1 = 1') to delete every row deliberately"
            );
        }

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
