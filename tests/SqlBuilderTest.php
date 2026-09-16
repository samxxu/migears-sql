<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use MiGears\Sql\SqlBuilder;
use MiGears\Sql\SqlDelete;
use MiGears\Sql\SqlInsert;
use MiGears\Sql\SqlSelect;
use MiGears\Sql\SqlUpdate;

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

    public function testCanBeConstructedWithoutLogger(): void
    {
        $sql = new SqlBuilder($this->pdo);
        $this->assertInstanceOf(SqlBuilder::class, $sql);
    }
}
