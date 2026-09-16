<?php

declare(strict_types=1);

namespace MiGears\Sql\Tests;

use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;
use MiGears\Sql\SqlBuilder;

abstract class TestCase extends BaseTestCase
{
    protected PDO $pdo;
    protected SqlBuilder $sql;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->createUsersTable();
        $this->seedUsers();

        $this->sql = new SqlBuilder($this->pdo);
    }

    protected function createUsersTable(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                age INTEGER DEFAULT 0,
                status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
    }

    protected function seedUsers(): void
    {
        $users = [
            ['name' => 'Alice',   'email' => 'alice@example.com',   'age' => 25, 'status' => 1],
            ['name' => 'Bob',     'email' => 'bob@example.com',     'age' => 30, 'status' => 1],
            ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 35, 'status' => 0],
            ['name' => 'Diana',   'email' => 'diana@example.com',   'age' => 28, 'status' => 1],
            ['name' => 'Eve',     'email' => 'eve@example.com',     'age' => 22, 'status' => 0],
        ];

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, age, status) VALUES (:name, :email, :age, :status)'
        );
        foreach ($users as $user) {
            $stmt->execute($user);
        }
    }
}
