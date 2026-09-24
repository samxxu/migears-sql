<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use MiGears\Sql\Exception\RecordNotFoundException;
use MiGears\Sql\Exception\SqlException;

class SqlSelectTest extends TestCase
{
    public function testBasicSelectAll(): void
    {
        $rows = $this->sql->select()->from('users')->execute();
        $this->assertCount(5, $rows);
        $this->assertEquals('Alice', $rows[0]['name']);
    }

    public function testSelectWithSpecificFields(): void
    {
        $rows = $this->sql->select(['id', 'name'])->from('users')->execute();
        $this->assertCount(5, $rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('name', $rows[0]);
        $this->assertArrayNotHasKey('email', $rows[0]);
    }

    public function testSelectWithStringFields(): void
    {
        $rows = $this->sql->select('id, name')->from('users')->execute();
        $this->assertCount(5, $rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('name', $rows[0]);
    }

    public function testWhereWithBindParams(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->where('status = :status', ['status' => 1])
            ->execute();

        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertEquals(1, $row['status']);
        }
    }

    public function testWhereWithSeparateBind(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->where('status = :status AND age > :age')
            ->bind(['status' => 1, 'age' => 25])
            ->execute();

        $this->assertCount(2, $rows);
    }

    public function testFilterEquals(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['status' => 0])
            ->execute();

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertEquals(0, $row['status']);
        }
    }

    public function testFilterGreaterThan(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['age>' => 28])
            ->execute();

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertGreaterThan(28, $row['age']);
        }
    }

    public function testFilterLessThan(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['age<' => 25])
            ->execute();

        $this->assertCount(1, $rows);
        $this->assertEquals('Eve', $rows[0]['name']);
    }

    public function testFilterNotEquals(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['status!' => 1])
            ->execute();

        $this->assertCount(2, $rows);
    }

    public function testFilterGreaterThanOrEqual(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['age>=' => 30])
            ->execute();

        $this->assertCount(2, $rows); // Bob(30), Charlie(35)
    }

    public function testFilterLessThanOrEqual(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['age<=' => 25])
            ->execute();

        $this->assertCount(2, $rows); // Alice(25), Eve(22)
    }

    public function testMultipleFilterConditions(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['status' => 1, 'age>' => 25])
            ->execute();

        $this->assertCount(2, $rows); // Bob(30), Diana(28)
    }

    public function testWhereAndFilterCombined(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->where('name LIKE :pattern', ['pattern' => '%a%'])
            ->filter(['status' => 1])
            ->execute();

        // Alice, Diana have 'a' in name and status=1
        $this->assertGreaterThanOrEqual(2, count($rows));
    }

    public function testOrderBy(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->orderBy('age DESC')
            ->execute();

        $this->assertEquals('Charlie', $rows[0]['name']); // 35
        $this->assertEquals('Eve', $rows[4]['name']);     // 22
    }

    public function testLimit(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->limit(2)
            ->execute();

        $this->assertCount(2, $rows);
    }

    public function testLimitWithOffset(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->orderBy('id ASC')
            ->limit(2)
            ->offset(2)
            ->execute();

        $this->assertCount(2, $rows);
        $this->assertEquals('Charlie', $rows[0]['name']);
        $this->assertEquals('Diana', $rows[1]['name']);
    }

    public function testGroupBy(): void
    {
        $rows = $this->sql->select('status, COUNT(*) as cnt')
            ->from('users')
            ->groupBy('status')
            ->orderBy('status ASC')
            ->execute();

        $this->assertCount(2, $rows);
        $this->assertEquals(0, $rows[0]['status']);
        $this->assertEquals(2, $rows[0]['cnt']);
        $this->assertEquals(1, $rows[1]['status']);
        $this->assertEquals(3, $rows[1]['cnt']);
    }

    public function testSingleReturnsOneRow(): void
    {
        $row = $this->sql->select()
            ->from('users')
            ->filter(['name' => 'Alice'])
            ->single();

        $this->assertNotNull($row);
        $this->assertEquals('Alice', $row['name']);
        $this->assertEquals('alice@example.com', $row['email']);
    }

    public function testSingleReturnsNullWhenNotFound(): void
    {
        $row = $this->sql->select()
            ->from('users')
            ->filter(['name' => 'Nobody'])
            ->single();

        $this->assertNull($row);
    }

    public function testSingleDoesNotAffectOriginalLimit(): void
    {
        $select = $this->sql->select()->from('users')->limit(3);
        $select->single();
        $rows = $select->execute();
        $this->assertCount(3, $rows);
    }

    public function testSingleOrFailReturnsRow(): void
    {
        $row = $this->sql->select()
            ->from('users')
            ->filter(['name' => 'Alice'])
            ->singleOrFail();

        $this->assertEquals('Alice', $row['name']);
    }

    public function testSingleOrFailThrowsWhenNotFound(): void
    {
        $this->expectException(RecordNotFoundException::class);

        $this->sql->select()
            ->from('users')
            ->filter(['name' => 'Nobody'])
            ->singleOrFail();
    }

    public function testCountAll(): void
    {
        $count = $this->sql->select()->from('users')->count();
        $this->assertEquals(5, $count);
    }

    public function testCountWithFilter(): void
    {
        $count = $this->sql->select()
            ->from('users')
            ->filter(['status' => 1])
            ->count();

        $this->assertEquals(3, $count);
    }

    public function testCountWithGroupBy(): void
    {
        $count = $this->sql->select()
            ->from('users')
            ->groupBy('status')
            ->count();

        $this->assertEquals(2, $count);
    }

    public function testPaginate(): void
    {
        $result = $this->sql->select()
            ->from('users')
            ->orderBy('id ASC')
            ->paginate(2, 2);

        $this->assertEquals(5, $result['total']);
        $this->assertCount(2, $result['records']);
        $this->assertEquals('Charlie', $result['records'][0]['name']);
    }

    public function testPaginateWithZeroPageThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sql->select()->from('users')->paginate(0, 2);
    }

    public function testPaginateWithNegativePageSizeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sql->select()->from('users')->paginate(1, 0);
    }

    public function testToSqlGeneratesCorrectSql(): void
    {
        $sql = $this->sql->select(['id', 'name'])
            ->from('users')
            ->filter(['status' => 1])
            ->orderBy('id DESC')
            ->limit(10)
            ->toSql();

        $this->assertStringContainsString('SELECT id, name FROM users', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('ORDER BY id DESC', $sql);
        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    public function testFromNotSetThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->select()->toSql();
    }

    public function testCountWithoutFromThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->select()->count();
    }

    public function testInvalidFilterExpressionThrowsException(): void
    {
        $this->expectException(SqlException::class);
        $this->sql->select()
            ->from('users')
            ->filter(['invalid field!' => 'value'])
            ->execute();
    }

    public function testEmptyResultReturnsEmptyArray(): void
    {
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['status' => 999])
            ->execute();

        $this->assertSame([], $rows);
    }

    public function testMultipleFiltersOnSameFieldUseUniquePlaceholders(): void
    {
        // age > 20 AND age < 40 — both bind to the same field name
        // but must not overwrite each other's placeholder value.
        $rows = $this->sql->select()
            ->from('users')
            ->filter(['age>' => 20])
            ->filter(['age<' => 40])
            ->orderBy('id ASC')
            ->execute();

        // All 5 users have age between 20 and 40.
        $this->assertCount(5, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Bob', $rows[1]['name']);
        $this->assertSame('Eve', $rows[4]['name']);
    }

    public function testWhereAndFilterWithSameFieldNameDoNotCollide(): void
    {
        // where() uses :age and filter() also has an age condition.
        // The filter placeholder must not collide with the where param.
        $rows = $this->sql->select()
            ->from('users')
            ->where('age <> :age', ['age' => 25])
            ->filter(['age>' => 20])
            ->orderBy('id ASC')
            ->execute();

        // 4 users: Bob(30), Charlie(35), Diana(28), Eve(22) — Alice(25) excluded
        $this->assertCount(4, $rows);
        $this->assertSame('Bob', $rows[0]['name']);
        $this->assertSame('Charlie', $rows[1]['name']);
    }

    public function testPaginatePreservesOriginalLimitAndOffset(): void
    {
        $select = $this->sql->select()
            ->from('users')
            ->orderBy('id ASC')
            ->limit(10)
            ->offset(0);

        $result = $select->paginate(2, 2);
        $this->assertSame(5, $result['total']);
        $this->assertCount(2, $result['records']);
        $this->assertSame('Charlie', $result['records'][0]['name']);

        // After paginate(), the original limit/offset must be restored
        // so subsequent calls to execute() still return all matching rows.
        $all = $select->execute();
        $this->assertCount(5, $all);
    }
}
