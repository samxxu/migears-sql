<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

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
}
