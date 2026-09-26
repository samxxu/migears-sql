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
 *   ['status' => 1]        => `status` = :f_status
 *   ['age>' => 18]         => `age` > :f_age
 *   ['age<' => 30]         => `age` < :f_age
 *   ['name!' => 'foo']     => `name` != :f_name
 *   ['score>=' => 60]      => `score` >= :f_score
 *
 * Filter placeholders are prefixed with f_ (and suffixed _2, _3, ... when two
 * conditions target the same field), so they never collide with where()/bind()
 * parameters. filter() accepts unqualified column names only.
 */
trait HasWhereClause
{
    /** @var string|null Raw WHERE condition (without WHERE keyword) */
    private ?string $whereClause = null;

    /** @var array<string, mixed> Named bound parameters (referenced as :name in where) */
    private array $bindParams = [];

    /** @var array<string, mixed> filter-style parameters (keys may have operator suffix) */
    private array $filterParams = [];

    /** @var array<string, string> Maps each filter key to its unique placeholder name */
    private array $filterPlaceholders = [];

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function getPdo(): PDO;

    /**
     * Sets the raw WHERE condition, replacing any condition set before.
     *
     * The parameters are replaced together with the condition: a second where()
     * call describes a different condition, so keeping the earlier parameters
     * would leave stale placeholders behind and break the bind count.
     * Use bind() to add parameters for the current condition.
     *
     * @param string $condition Condition string (without WHERE keyword), e.g. "id = :id AND status = :status"
     * @param array<string, mixed> $params Bind parameters for this condition (equivalent to calling bind() immediately after)
     */
    public function where(string $condition, array $params = []): static
    {
        $this->whereClause = $condition;
        $this->bindParams = $params;
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
     *
     * Also populates $filterPlaceholders so bindAllParams() can bind values using
     * the same unique names. Placeholders are named f_{field} (f_{field}_2, f_{field}_3, ...
     * on collision) to avoid conflicts with bind()/where() parameters and with other
     * filter conditions on the same field (e.g. age> and age<).
     */
    protected function buildWhere(): string
    {
        $segments = [];
        $used = [];

        if ($this->whereClause !== null && $this->whereClause !== '') {
            $segments[] = $this->whereClause;
            foreach (array_keys($this->bindParams) as $name) {
                $used[$name] = true;
            }
        }

        $this->filterPlaceholders = [];
        foreach ($this->filterParams as $key => $_value) {
            // Validate the key before extracting the field name: a malformed key
            // must raise SqlException, not an undefined-array-key warning.
            $operator = $this->parseFilterOperator($key);
            $field = $this->extractFilterField($key);
            $base = 'f_' . $field;
            $name = $base;
            $i = 1;
            while (isset($used[$name])) {
                ++$i;
                $name = $base . '_' . $i;
            }
            $used[$name] = true;
            $this->filterPlaceholders[$key] = $name;

            $segments[] = "`{$field}` {$operator} :{$name}";
        }

        if ($segments === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $segments);
    }

    /**
     * Extracts the comparison operator from a filter key (e.g. "age>" => ">").
     */
    private function parseFilterOperator(string $key): string
    {
        if (!preg_match('/^([\w]+)([!<>=]{0,2})$/D', $key, $matches)) {
            throw new SqlException("Invalid filter expression: {$key}");
        }

        return match ($matches[2]) {
            '!', '<>', '><' => '!=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            '', '=' => '=',
            default => throw new SqlException("Unsupported filter operator: {$matches[2]}"),
        };
    }

    /**
     * Binds all parameters (bind + filter) onto the PDOStatement.
     *
     * Call buildWhere() first — it populates $filterPlaceholders with the
     * unique placeholder names used in the generated SQL.
     */
    protected function bindAllParams(PDOStatement $stmt): void
    {
        foreach ($this->bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        foreach ($this->filterParams as $key => $value) {
            $placeholder = $this->filterPlaceholders[$key] ?? $this->extractFilterField($key);
            $stmt->bindValue($placeholder, $value);
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
