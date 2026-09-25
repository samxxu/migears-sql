<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use MiGears\Sql\Exception\SqlException;

class SqlUpdateTest extends TestCase
{
    public function testUpdateSingleColumn(): void
    {
        $affected = $this->sql->update('users')
            ->set(['name' => 'Alice Updated'])
            ->filter(['id' => 1])
            ->execute();

        $this->assertEquals(1, $affected);

        $row = $this->sql->select()->from('users')->filter(['id' => 1])->single();
        $this->assertEquals('Alice Updated', $row['name']);
    }

    public function testUpdateMultipleColumns(): void
    {
        $affected = $this->sql->update('users')
            ->set(['name' => 'Bob Updated', 'age' => 31])
            ->filter(['id' => 2])
            ->execute();

        $this->assertEquals(1, $affected);

        $row = $this->sql->select()->from('users')->filter(['id' => 2])->single();
        $this->assertEquals('Bob Updated', $row['name']);
        $this->assertEquals(31, $row['age']);
    }

    public function testUpdateWithWhereBind(): void
    {
        // Bob(30, status=1), Diana(28, status=1) both satisfy age >= 28 and status=1
        $affected = $this->sql->update('users')
            ->set(['status' => 0])
            ->where('age >= :age AND status = :status', ['age' => 28, 'status' => 1])
            ->execute();

        $this->assertEquals(2, $affected);

        $count = $this->sql->select()->from('users')->filter(['status' => 0])->count();
        $this->assertEquals(4, $count); // original 2 + newly updated 2
    }

    public function testUpdateWithFilter(): void
    {
        $affected = $this->sql->update('users')
            ->set(['status' => 0])
            ->filter(['status' => 1, 'age>' => 25])
            ->execute();

        // Bob(30), Diana(28) match the condition
        $this->assertEquals(2, $affected);
    }

    public function testUpdateWithRawSetString(): void
    {
        $affected = $this->sql->update('users')
            ->set('age = age + 1')
            ->filter(['id' => 1])
            ->execute();

        $this->assertEquals(1, $affected);

        $row = $this->sql->select()->from('users')->filter(['id' => 1])->single();
        $this->assertEquals(26, $row['age']);
    }

    public function testUpdateNoMatchingRows(): void
    {
        $affected = $this->sql->update('users')
            ->set(['name' => 'Nobody'])
            ->filter(['id' => 999])
            ->execute();

        $this->assertEquals(0, $affected);
    }

    public function testToSqlGeneratesCorrectSql(): void
    {
        $sql = $this->sql->update('users')
            ->set(['name' => 'Test', 'age' => 20])
            ->filter(['id' => 1])
            ->toSql();

        $this->assertStringContainsString('UPDATE `users` SET', $sql);
        $this->assertStringContainsString('`name` = :set_name', $sql);
        $this->assertStringContainsString('`age` = :set_age', $sql);
        $this->assertStringContainsString('WHERE', $sql);
    }

    public function testUpdateWithoutSetThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->update('users')->filter(['id' => 1])->toSql();
    }

    public function testSetAndFilterSameFieldNameNoConflict(): void
    {
        // Verify SET parameters have set_ prefix and don't conflict with WHERE parameters
        $affected = $this->sql->update('users')
            ->set(['status' => 0])
            ->filter(['status' => 1])
            ->execute();

        $this->assertEquals(3, $affected);
    }

    public function testSetWithBacktickInColumnNameThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->update('users')
            ->set(["name` = 'HACKED' -- " => 'ignored'])
            ->filter(['id' => 1])
            ->toSql();
    }

    public function testSetWithSpaceInColumnNameThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->update('users')
            ->set(['a b' => 1])
            ->filter(['id' => 1])
            ->toSql();
    }
}
