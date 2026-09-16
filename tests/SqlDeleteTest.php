<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

class SqlDeleteTest extends TestCase
{
    public function testDeleteById(): void
    {
        $affected = $this->sql->delete('users')
            ->filter(['id' => 1])
            ->execute();

        $this->assertEquals(1, $affected);

        $count = $this->sql->select()->from('users')->count();
        $this->assertEquals(4, $count);
    }

    public function testDeleteWithWhere(): void
    {
        $affected = $this->sql->delete('users')
            ->where('status = :status', ['status' => 0])
            ->execute();

        $this->assertEquals(2, $affected);

        $count = $this->sql->select()->from('users')->count();
        $this->assertEquals(3, $count);
    }

    public function testDeleteWithFilter(): void
    {
        $affected = $this->sql->delete('users')
            ->filter(['status' => 0, 'age>' => 30])
            ->execute();

        // Charlie(35, status=0)
        $this->assertEquals(1, $affected);
    }

    public function testDeleteNoMatchingRows(): void
    {
        $affected = $this->sql->delete('users')
            ->filter(['id' => 999])
            ->execute();

        $this->assertEquals(0, $affected);
    }

    public function testDeleteAllRows(): void
    {
        $affected = $this->sql->delete('users')->execute();

        $this->assertEquals(5, $affected);
        $this->assertEquals(0, $this->sql->select()->from('users')->count());
    }

    public function testToSqlGeneratesCorrectSql(): void
    {
        $sql = $this->sql->delete('users')
            ->filter(['id' => 1])
            ->toSql();

        $this->assertStringContainsString('DELETE FROM `users`', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('`id` = :id', $sql);
    }

    public function testToSqlWithoutWhere(): void
    {
        $sql = $this->sql->delete('users')->toSql();
        $this->assertEquals('DELETE FROM `users`', $sql);
    }
}
