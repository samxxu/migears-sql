<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use PDO;
use MiGears\Sql\SqlBuilder;
use MiGears\Sql\Exception\SqlException;

class SqlInsertTest extends TestCase
{
    public function testInsertSingleRow(): void
    {
        $insert = $this->sql->insert('users')
            ->values([
                'name' => 'Frank',
                'email' => 'frank@example.com',
                'age' => 40,
                'status' => 1,
            ])
            ->execute();

        $id = $insert->lastInsertId();
        $this->assertEquals(6, (int) $id);
        $this->assertEquals(1, $insert->rowCount());

        // Verify data
        $row = $this->sql->select()->from('users')->filter(['id' => 6])->single();
        $this->assertNotNull($row);
        $this->assertEquals('Frank', $row['name']);
        $this->assertEquals('frank@example.com', $row['email']);
    }

    public function testInsertWithNullValue(): void
    {
        $this->sql->insert('users')
            ->values([
                'name' => 'Grace',
                'email' => 'grace@example.com',
                'age' => null,
                'status' => 1,
            ])
            ->execute();

        $row = $this->sql->select()->from('users')->filter(['name' => 'Grace'])->single();
        $this->assertNotNull($row);
        $this->assertNull($row['age']);
    }

    public function testToSqlGeneratesCorrectSql(): void
    {
        $sql = $this->sql->insert('users')
            ->values(['name' => 'Test', 'email' => 'test@example.com'])
            ->toSql();

        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        $this->assertStringContainsString('`name`', $sql);
        $this->assertStringContainsString('`email`', $sql);
        $this->assertStringContainsString(':name', $sql);
        $this->assertStringContainsString(':email', $sql);
    }

    public function testInsertWithoutValuesThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->insert('users')->toSql();
    }

    public function testInsertExecuteWithoutValuesThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->insert('users')->execute();
    }

    public function testInsertReturnsSelfForChaining(): void
    {
        $insert = $this->sql->insert('users');
        $result = $insert->values(['name' => 'Test', 'email' => 'test@test.com']);
        $this->assertSame($insert, $result);
    }

    public function testInsertWithBacktickInColumnNameThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->insert('users')
            ->values(["name`, email`) SELECT 'x' AS name, 'x@x.com' AS email FROM users -- " => 'evil'])
            ->toSql();
    }

    public function testInsertWithSpaceInColumnNameThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->insert('users')
            ->values(['a b' => 1])
            ->toSql();
    }

    public function testLastInsertIdIsFalseBeforeExecute(): void
    {
        $this->assertFalse($this->sql->insert('users')->lastInsertId());
    }

    public function testLastInsertIdIsNotChangedByALaterInsert(): void
    {
        $first = $this->sql->insert('users')
            ->values(['name' => 'Zoe', 'email' => 'zoe@example.com', 'age' => 20, 'status' => 1])
            ->execute();
        $this->assertEquals(6, (int) $first->lastInsertId());

        // A later insert on the same connection must not change the first result.
        $this->sql->insert('users')
            ->values(['name' => 'Yan', 'email' => 'yan@example.com', 'age' => 21, 'status' => 1])
            ->execute();

        $this->assertEquals(6, (int) $first->lastInsertId());
    }

    public function testSilentModeFailedExecuteThrowsSqlException(): void
    {
        // In ERRMODE_SILENT a failed execute() returns false instead of throwing;
        // the builder must raise SqlException rather than swallow the error.
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY)');
        $pdo->exec('INSERT INTO t (id) VALUES (1)');
        $sql = new SqlBuilder($pdo);

        $this->expectException(SqlException::class);
        $sql->insert('t')->values(['id' => 1])->execute();
    }
}
