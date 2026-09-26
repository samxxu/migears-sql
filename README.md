# migears/sql

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Lightweight SQL query builder for PHP 8.1+, with zero mandatory dependencies (except the PDO extension).

> **Background**: miGears is the open-source successor of **TinyGears**, a
> self-developed PHP framework. It was renamed and open-sourced recently because
> the name *TinyGears* is already taken in the open-source community.

## Features

- **Minimalist API**: `$sql->select()->from('users')->filter(['status' => 1])->execute()`
- **Zero global dependencies**: accepts a PDO instance in the constructor, ready to use after `new`
- **Pure array returns**: no object mapping, simple and straightforward
- **PSR-3 logging**: optional `LoggerInterface` injection, defaults to `NullLogger`
- **Domain exceptions**: `SqlException` / `RecordNotFoundException`
- **Single file < 300 lines**: every core file is short and easy to understand at a glance
- **High test coverage**: integration tests with SQLite in-memory database

## Installation

```bash
composer require migears/sql
```

Requires: PHP 8.1+, PDO extension.

## Quick Start

```php
use MiGears\Sql\SqlBuilder;

$pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = new SqlBuilder($pdo);
```

### SELECT

```php
// Fetch all
$rows = $sql->select()->from('users')->execute();

// Specify columns + conditions
$rows = $sql->select(['id', 'name'])
    ->from('users')
    ->where('status = :status', ['status' => 1])
    ->orderBy('id DESC')
    ->limit(10)
    ->execute();

// filter style (key names with operator suffixes)
$rows = $sql->select()
    ->from('users')
    ->filter(['status' => 1, 'age>' => 18])
    ->execute();

// Single row
$user = $sql->select()->from('users')->filter(['id' => 1])->single();

// Single row, throws exception if not found
$user = $sql->select()->from('users')->filter(['id' => 1])->singleOrFail();

// Count
$count = $sql->select()->from('users')->filter(['status' => 1])->count();

// Group by
$rows = $sql->select(['status', 'COUNT(*) as cnt'])
    ->from('users')
    ->groupBy('status')
    ->execute();

// Separate bind() calls (equivalent to passing params to where())
$rows = $sql->select()
    ->from('users')
    ->where('status = :status AND age > :age')
    ->bind(['status' => 1])
    ->bind(['age' => 18])
    ->execute();

// Pagination
$result = $sql->select()->from('users')->paginate(1, 20);
// ['records' => [...], 'total' => 100]
```

#### filter Operator Suffixes

| Suffix | Operator | Example | Generated SQL |
|--------|----------|---------|---------------|
| (none) | = | `['status' => 1]` | `` `status` = :f_status `` |
| `>` | > | `['age>' => 18]` | `` `age` > :f_age `` |
| `<` | < | `['age<' => 30]` | `` `age` < :f_age `` |
| `>=` | >= | `['age>=' => 18]` | `` `age` >= :f_age `` |
| `<=` | <= | `['age<=' => 30]` | `` `age` <= :f_age `` |
| `!` / `<>` / `><` | != | `['status!' => 0]` | `` `status` != :f_status `` |

Filter placeholders are named `f_{field}`, gaining a `_2`, `_3`, ... suffix when two conditions target the same field, so they never collide with `where()` / `bind()` parameters. `filter()` accepts unqualified column names only — `['u.id' => 1]` is rejected; use `where()` for qualified names in JOIN queries.

### Multi-Table / JOIN Queries

`from()` accepts any valid SQL table clause, including JOIN syntax and comma-separated tables. Use `where()` for join conditions.

```php
// INNER JOIN via from()
$rows = $sql->select(['u.name', 'p.title'])
    ->from('users u INNER JOIN posts p ON u.id = p.user_id')
    ->where('u.status = :status', ['status' => 1])
    ->execute();

// LEFT JOIN
$rows = $sql->select(['u.name', 'p.title'])
    ->from('users u LEFT JOIN posts p ON u.id = p.user_id')
    ->execute();

// Comma-separated (implicit join)
$rows = $sql->select(['u.name', 'p.title'])
    ->from('users u, posts p')
    ->where('u.id = p.user_id', [])
    ->execute();
```

**Design rationale**: No dedicated `join()` methods — `from()` is flexible enough for any SQL syntax, keeping the API minimal.

### INSERT

```php
$id = $sql->insert('users')
    ->values(['name' => 'Alice', 'email' => 'alice@example.com'])
    ->execute()
    ->lastInsertId();
```

### UPDATE

```php
$affected = $sql->update('users')
    ->set(['name' => 'Bob', 'age' => 30])
    ->filter(['id' => 1])
    ->execute();

// Raw SET expression
$sql->update('users')
    ->set('counter = counter + 1')
    ->filter(['id' => 1])
    ->execute();
```

### DELETE

```php
$affected = $sql->delete('users')
    ->filter(['id' => 1])
    ->execute();
```

## Architecture

The SQL module is the bottom layer of miGears' three-layer data architecture:

```
Service Layer (business logic)
    ↓ calls
DAO Layer (receives/returns Domain objects) → migears/dao
    ↓ internally calls
SQL Layer (SQL + params → arrays) → migears/sql (this package)
    ↓
PDO / MySQL
```

The SQL layer knows nothing about Domain objects. It only executes SQL and returns arrays.

## Logging

Inject any PSR-3 Logger implementation:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('sql');
$logger->pushHandler(new StreamHandler('php://stdout'));

$sql = new SqlBuilder($pdo, $logger);
```

## Exceptions

```php
use MiGears\Sql\Exception\SqlException;
use MiGears\Sql\Exception\RecordNotFoundException;

try {
    $user = $sql->select()->from('users')->filter(['id' => 999])->singleOrFail();
} catch (RecordNotFoundException $e) {
    // Record not found
} catch (SqlException $e) {
    // SQL related error
}
```

## License

MIT

---

# migears/sql

![Version](https://img.shields.io/badge/version-2.0.0-blue)

轻量 SQL 查询构建器，PHP 8.1+，零强制依赖（除了 PDO 扩展）。

## 特性

- **极简 API**：`$sql->select()->from('users')->filter(['status' => 1])->execute()`
- **零全局依赖**：构造函数接收 PDO 实例，new 了就能用
- **纯数组返回**：不做对象映射，简单直接
- **PSR-3 日志**：可选注入 LoggerInterface，默认 NullLogger
- **领域异常**：SqlException / RecordNotFoundException
- **单文件 < 300 行**：每个核心文件都很短，一眼看懂
- **高测试覆盖率**：SQLite 内存数据库集成测试

## 安装

```bash
composer require migears/sql
```

要求：PHP 8.1+，PDO 扩展。

## 快速开始

```php
use MiGears\Sql\SqlBuilder;

$pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = new SqlBuilder($pdo);
```

### SELECT

```php
// 查询所有
$rows = $sql->select()->from('users')->execute();

// 指定字段 + 条件
$rows = $sql->select(['id', 'name'])
    ->from('users')
    ->where('status = :status', ['status' => 1])
    ->orderBy('id DESC')
    ->limit(10)
    ->execute();

// filter 方式（键名带操作符后缀）
$rows = $sql->select()
    ->from('users')
    ->filter(['status' => 1, 'age>' => 18])
    ->execute();

// 单行
$user = $sql->select()->from('users')->filter(['id' => 1])->single();

// 单行，找不到抛异常
$user = $sql->select()->from('users')->filter(['id' => 1])->singleOrFail();

// 统计
$count = $sql->select()->from('users')->filter(['status' => 1])->count();

// 分组
$rows = $sql->select(['status', 'COUNT(*) as cnt'])
    ->from('users')
    ->groupBy('status')
    ->execute();

// 分开调用 bind()（与把参数传给 where() 等效）
$rows = $sql->select()
    ->from('users')
    ->where('status = :status AND age > :age')
    ->bind(['status' => 1])
    ->bind(['age' => 18])
    ->execute();

// 分页
$result = $sql->select()->from('users')->paginate(1, 20);
// ['records' => [...], 'total' => 100]
```

#### filter 操作符后缀

| 后缀 | 操作符 | 示例 | 生成 |
|------|--------|------|------|
| （无） | = | `['status' => 1]` | `` `status` = :f_status `` |
| `>` | > | `['age>' => 18]` | `` `age` > :f_age `` |
| `<` | < | `['age<' => 30]` | `` `age` < :f_age `` |
| `>=` | >= | `['age>=' => 18]` | `` `age` >= :f_age `` |
| `<=` | <= | `['age<=' => 30]` | `` `age` <= :f_age `` |
| `!` / `<>` / `><` | != | `['status!' => 0]` | `` `status` != :f_status `` |

filter 占位符命名为 `f_{字段名}`，同一字段出现两个条件时追加 `_2`、`_3` 后缀，因此永远不会与 `where()` / `bind()` 的参数冲突。`filter()` 只接受非限定列名，`['u.id' => 1]` 会被拒绝；JOIN 查询中的限定列名请用 `where()`。

### 多表 / JOIN 查询

`from()` 接受任意合法 SQL 表子句，包括 JOIN 语法和逗号分隔的多表。用 `where()` 指定关联条件。

```php
// INNER JOIN
$rows = $sql->select(['u.name', 'p.title'])
    ->from('users u INNER JOIN posts p ON u.id = p.user_id')
    ->where('u.status = :status', ['status' => 1])
    ->execute();

// LEFT JOIN
$rows = $sql->select(['u.name', 'p.title'])
    ->from('users u LEFT JOIN posts p ON u.id = p.user_id')
    ->execute();

// 逗号分隔（隐式连接）
$rows = $sql->select(['u.name', 'p.title'])
    ->from('users u, posts p')
    ->where('u.id = p.user_id', [])
    ->execute();
```

**设计理由**：不提供专门的 `join()` 方法 — `from()` 足以表达任意 SQL 语法，保持 API 极简。

### INSERT

```php
$id = $sql->insert('users')
    ->values(['name' => 'Alice', 'email' => 'alice@example.com'])
    ->execute()
    ->lastInsertId();
```

### UPDATE

```php
$affected = $sql->update('users')
    ->set(['name' => 'Bob', 'age' => 30])
    ->filter(['id' => 1])
    ->execute();

// 原始 SET 表达式
$sql->update('users')
    ->set('counter = counter + 1')
    ->filter(['id' => 1])
    ->execute();
```

### DELETE

```php
$affected = $sql->delete('users')
    ->filter(['id' => 1])
    ->execute();
```

## 架构

SQL 模块是 miGears 三层数据架构的最底层：

```
Service 层（业务逻辑）
    ↓ 调用
DAO 层（接收/返回 Domain 对象）→ migears/dao
    ↓ 内部调用
SQL 层（SQL + 参数 → 数组）→ migears/sql（本包）
    ↓
PDO / MySQL
```

SQL 层不知道 Domain 的存在，只执行 SQL 返回数组。

## 日志

注入任意 PSR-3 Logger 实现：

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('sql');
$logger->pushHandler(new StreamHandler('php://stdout'));

$sql = new SqlBuilder($pdo, $logger);
```

## 异常

```php
use MiGears\Sql\Exception\SqlException;
use MiGears\Sql\Exception\RecordNotFoundException;

try {
    $user = $sql->select()->from('users')->filter(['id' => 999])->singleOrFail();
} catch (RecordNotFoundException $e) {
    // 记录不存在
} catch (SqlException $e) {
    // SQL 相关错误
}
```

## 许可证

MIT
