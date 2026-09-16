<?php

declare(strict_types=1);

namespace MiGears\Sql;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use MiGears\Sql\Exception\SqlException;

/**
 * WHERE clause builder trait, shared by select/update/delete.
 *
 * Supports two parameter passing styles:
 *   - where() + bind(): handwritten conditions + named parameter binding
 *   - filter():  array keys with operator suffix, auto-generates conditions
 *
 * filter operator suffix examples:
 *   ['status' => 1]        => `status` = :status
 *   ['age>' => 18]         => `age` > :age
 *   ['age<' => 30]         => `age` < :age
 *   ['name!' => 'foo']     => `name` != :name
 *   ['score>=' => 60]      => `score` >= :score
 */
trait HasWhereClause
{
    /** @var string|null Raw WHERE condition (without WHERE keyword) */
    private ?string $whereClause = null;

    /** @var array<string, mixed> Named bound parameters (referenced as :name in where) */
    private array $bindParams = [];

    /** @var array<string, mixed> filter-style parameters (keys may have operator suffix) */
    private array $filterParams = [];

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getPdo(): PDO;

    /**
     * Sets the raw WHERE condition.
     *
     * @param string $condition Condition string (without WHERE keyword), e.g. "id = :id AND status = :status"
     * @param array<string, mixed> $params Optional bind parameters (equivalent to calling bind() immediately after)
     */
    public function where(string $condition, array $params = []): static
    {
        $this->whereClause = $condition;
        if ($params !== []) {
            $this->bindParams = array_merge($this->bindParams, $params);
        }
        return $this;
    }

    /**
     * Binds named parameters (corresponding to :name placeholders in where()).
     *
     * @param array<string, mixed> $params
     */
    public function bind(array $params): static
    {
        $this->bindParams = array_merge($this->bindParams, $params);
        return $this;
    }

    /**
     * Filters using array conditions, keys may have operator suffixes.
     *
     * @param array<string, mixed> $params e.g. ['status' => 1, 'age>' => 18]
     */
    public function filter(array $params): static
    {
        $this->filterParams = array_merge($this->filterParams, $params);
        return $this;
    }

    /**
     * Builds the WHERE SQL fragment (including WHERE keyword), returns empty string if no conditions.
     */
    protected function buildWhere(): string
    {
        $segments = [];

        if ($this->whereClause !== null && $this->whereClause !== '') {
            $segments[] = $this->whereClause;
        }

        foreach ($this->filterParams as $key => $_value) {
            $segments[] = $this->parseFilterSegment($key);
        }

        if ($segments === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $segments);
    }

    /**
     * Parses a filter key, returns a fragment in "`field` op :placeholder" form.
     * Placeholder names use the field name (with operator suffix stripped); if duplicate with existing params, a suffix is added to avoid conflicts.
     */
    private function parseFilterSegment(string $key): string
    {
        if (!preg_match('/^([\w]+)([!<>=]{0,2})$/', $key, $matches)) {
            throw new SqlException("Invalid filter expression: {$key}");
        }

        $field = $matches[1];
        $operator = match ($matches[2]) {
            '!', '<>', '><' => '!=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            '', '=' => '=',
            default => throw new SqlException("Unsupported filter operator: {$matches[2]}"),
        };

        return "`{$field}` {$operator} :{$field}";
    }

    /**
     * Binds all parameters (bind + filter) onto the PDOStatement.
     */
    protected function bindAllParams(PDOStatement $stmt): void
    {
        foreach ($this->bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        foreach ($this->filterParams as $key => $value) {
            $field = $this->extractFilterField($key);
            $stmt->bindValue($field, $value);
        }
    }

    /**
     * Extracts the pure field name from a filter key.
     */
    private function extractFilterField(string $key): string
    {
        preg_match('/^([\w]+)/', $key, $matches);
        return $matches[1];
    }

    /**
     * Logs debug information (SQL + parameters).
     */
    protected function logQuery(string $sql): void
    {
        $params = array_merge($this->bindParams, $this->filterParams);
        $this->getLogger()->debug('SQL: {sql}', ['sql' => $sql, 'params' => $params]);
    }
}
