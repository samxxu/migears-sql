<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use MiGears\Sql\SqlBuilder;
use MiGears\Sql\SqlDelete;
use MiGears\Sql\SqlInsert;
use MiGears\Sql\SqlSelect;
use MiGears\Sql\SqlUpdate;
use Psr\Log\NullLogger;

class SqlBuilderTest extends TestCase
{
    public function testSelectReturnsSqlSelectInstance(): void
    {
        $select = $this->sql->select();
        $this->assertInstanceOf(SqlSelect::class, $select);
    }

    public function testInsertReturnsSqlInsertInstance(): void
    {
        $insert = $this->sql->insert('users');
        $this->assertInstanceOf(SqlInsert::class, $insert);
    }

    public function testUpdateReturnsSqlUpdateInstance(): void
    {
        $update = $this->sql->update('users');
        $this->assertInstanceOf(SqlUpdate::class, $update);
    }

    public function testDeleteReturnsSqlDeleteInstance(): void
    {
        $delete = $this->sql->delete('users');
        $this->assertInstanceOf(SqlDelete::class, $delete);
    }

    public function testGetPdoReturnsPdoInstance(): void
    {
        $this->assertSame($this->pdo, $this->sql->getPdo());
    }

    public function testConstructorRequiresALogger(): void
    {
        // The logger has no default and no NullLogger fallback, so a call site
        // that forgets it fails at assembly time instead of reporting nothing.
        try {
            new SqlBuilder($this->pdo);
            $this->fail('Omitting the logger should not construct a SqlBuilder');
        } catch (\ArgumentCountError $e) {
            $this->assertStringContainsString('SqlBuilder::__construct', $e->getMessage());
        }

        $logger = new NullLogger();

        try {
            new SqlSelect($this->pdo);
            $this->fail('Omitting the logger should not construct a SqlSelect');
        } catch (\ArgumentCountError $e) {
            $this->assertStringContainsString('SqlSelect::__construct', $e->getMessage());
        }

        try {
            new SqlInsert($this->pdo, 'users');
            $this->fail('Omitting the logger should not construct a SqlInsert');
        } catch (\ArgumentCountError $e) {
            $this->assertStringContainsString('SqlInsert::__construct', $e->getMessage());
        }

        try {
            new SqlUpdate($this->pdo, 'users');
            $this->fail('Omitting the logger should not construct a SqlUpdate');
        } catch (\ArgumentCountError $e) {
            $this->assertStringContainsString('SqlUpdate::__construct', $e->getMessage());
        }

        try {
            new SqlDelete($this->pdo, 'users');
            $this->fail('Omitting the logger should not construct a SqlDelete');
        } catch (\ArgumentCountError $e) {
            $this->assertStringContainsString('SqlDelete::__construct', $e->getMessage());
        }

        $this->assertInstanceOf(SqlBuilder::class, new SqlBuilder($this->pdo, $logger));
    }
}
