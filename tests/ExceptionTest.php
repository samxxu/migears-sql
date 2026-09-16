<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Sql\Exception\RecordNotFoundException;
use MiGears\Sql\Exception\SqlException;

class ExceptionTest extends TestCase
{
    public function testSqlExceptionIsRuntimeException(): void
    {
        $e = new SqlException('test');
        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertEquals('test', $e->getMessage());
    }

    public function testRecordNotFoundExceptionExtendsSqlException(): void
    {
        $e = new RecordNotFoundException();
        $this->assertInstanceOf(SqlException::class, $e);
    }

    public function testRecordNotFoundExceptionWithTableAndId(): void
    {
        $e = new RecordNotFoundException('users', 42);
        $this->assertStringContainsString('users', $e->getMessage());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    public function testRecordNotFoundExceptionWithTableOnly(): void
    {
        $e = new RecordNotFoundException('users');
        $this->assertStringContainsString('users', $e->getMessage());
    }

    public function testRecordNotFoundExceptionWithNoArgs(): void
    {
        $e = new RecordNotFoundException();
        $this->assertEquals('Record not found', $e->getMessage());
    }

    public function testSqlExceptionWithCodeAndPrevious(): void
    {
        $prev = new \Exception('previous');
        $e = new SqlException('test', 500, $prev);
        $this->assertEquals(500, $e->getCode());
        $this->assertSame($prev, $e->getPrevious());
    }
}
