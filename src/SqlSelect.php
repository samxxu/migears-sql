<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use MiGears\Sql\Exception\SqlException;

/**
 * SELECT query builder.
 *
 *   $rows = $sql->select(['id', 'name'])
 *       ->from('users')
 *       ->where('status = :status', ['status' => 1])
 *       ->orderBy('id DESC')
 *       ->limit(10)
 *       ->execute();
 */
final class SqlSelect
{
    use HasWhereClause;

    private string $fields;
    private ?string $table = null;
    private ?string $orderBy = null;
    private ?string $groupBy = null;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger = new NullLogger(),
        string|array|null $fields = null,
    ) {
        $this->fields = match (true) {
            $fields === null => '*',
            is_array($fields) => implode(', ', $fields),
            default => $fields,
        };
    }

    /**
     * Sets the table name (supports single or multiple tables).
     */
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function orderBy(string $order): self
    {
        $this->orderBy = $order;
        return $this;
    }

    public function groupBy(string $group): self
    {
        $this->groupBy = $group;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Generates the complete SELECT SQL string.
     */
    public function toSql(): string
    {
        if ($this->table === null) {
            throw new SqlException('Table name must be specified via from()');
        }

        $sql = "SELECT {$this->fields} FROM {$this->table}";
        $sql .= $this->buildWhere();

        if ($this->groupBy !== null) {
            $sql .= " GROUP BY {$this->groupBy}";
        }
        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Executes the query, returns all rows (array of arrays).
     *
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        $sql = $this->toSql();
        $this->logQuery($sql);

        $stmt = $this->pdo->prepare($sql);
        $this->bindAllParams($stmt);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Queries a single row, returns null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function single(): ?array
    {
        $prevLimit = $this->limit;
        $prevOffset = $this->offset;
        $this->limit = 1;
        $this->offset = null;

        try {
            $rows = $this->execute();
            return $rows[0] ?? null;
        } finally {
            $this->limit = $prevLimit;
            $this->offset = $prevOffset;
        }
    }

    /**
     * Queries a single row, throws RecordNotFoundException if not found.
     *
     * @return array<string, mixed>
     */
    public function singleOrFail(): array
    {
        $row = $this->single();
        if ($row === null) {
            throw new Exception\RecordNotFoundException();
        }
        return $row;
    }

    /**
     * Counts the total number of matching records (ignores limit/offset/orderBy).
     */
    public function count(): int
    {
        if ($this->table === null) {
            throw new SqlException('Table name must be specified via from()');
        }

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $sql .= $this->buildWhere();

        if ($this->groupBy !== null) {
            $sql = "SELECT COUNT(*) FROM ({$sql} GROUP BY {$this->groupBy}) c";
        }

        $this->logQuery($sql);
        $stmt = $this->pdo->prepare($sql);
        $this->bindAllParams($stmt);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated query, returns [records, total].
     *
     * @return array{records: array<int, array<string, mixed>>, total: int}
     */
    public function paginate(int $page, int $pageSize): array
    {
        $total = $this->count();
        $this->limit = $pageSize;
        $this->offset = ($page - 1) * $pageSize;
        $records = $this->execute();

        return ['records' => $records, 'total' => $total];
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
