# migears/sql Module Specification

Version: 2.0.0
Date: 2026-09-29

## 1. Positioning

`migears/sql` is a lightweight SQL query builder at the bottom of miGears' three-layer data architecture (Service → DAO → SQL → PDO). It borrows a `PDO` instance from the caller, turns fluent method calls into a SQL string plus bound parameters, and returns plain arrays. It is not an ORM: it owns no connection and no configuration, performs no mapping, and knows nothing about Domain objects.

## 2. Boundaries

### 2.1 In scope

- Building and executing SQL over a borrowed `PDO`: `select()` / `insert()` / `update()` / `delete()`, `where()` / `filter()` / `bind()` / `orderBy()` / `limit()` / `groupBy()`, `single()` / `singleOrFail()` / `count()` / `paginate()`.
- Returning rows as plain arrays, and naming failures as `SqlException` / `RecordNotFoundException`.

### 2.2 Out of scope (explicitly not done)

- Owning or opening a connection — a `PDO` instance is passed in; this package never connects and holds no configuration.
- Domain objects, mapping and hydration — `migears/dao` turns rows into Domains; this layer knows nothing about them.
- Schema, migrations, transactions, connection pooling, or an ORM.
- Filtering on qualified column names — `filter()` takes unqualified names only; use `where()` for JOIN queries.

## 3. Public contract

| Entry point | Behaviour |
|---|---|
| `SqlBuilder::__construct(PDO $pdo, LoggerInterface $logger = new NullLogger())` | Entry point, usable right after `new`; the logger is optional and defaults to `NullLogger`. |
| `SqlBuilder::select(string\|array\|null $fields = null): SqlSelect` | `null` → `*`, array → `implode(', ', ...)`, string → verbatim. |
| `SqlBuilder::insert\|update\|delete(string $table)` | New `SqlInsert` / `SqlUpdate` / `SqlDelete` builder for `$table`. |
| `SqlBuilder::getPdo(): PDO` | Returns the borrowed connection unchanged. |
| `SqlSelect::from(string $table): self` | Table clause, passed through verbatim (JOIN, comma-separated and alias expressions allowed). |
| `SqlSelect::orderBy(string) / groupBy(string) / limit(int) / offset(int): self` | Each stores one raw clause or integer; `toSql()` emits them in this fixed order. |
| `SqlSelect::toSql(): string` | Builds `SELECT ... [GROUP BY] [ORDER BY] [LIMIT] [OFFSET]`. |
| `SqlSelect::execute(): array` | All matching rows as `array<int, array<string, mixed>>`; `[]` when none. |
| `SqlSelect::single(): ?array` | Forces `LIMIT 1`, returns the row or `null`, and restores the previous limit/offset afterwards. |
| `SqlSelect::singleOrFail(): array` | `single()`, throwing `RecordNotFoundException` when the row is `null`. |
| `SqlSelect::count(): int` | `COUNT(*)` ignoring limit/offset/orderBy; wraps in a subquery when `groupBy()` is set. |
| `SqlSelect::paginate(int $page, int $pageSize): array` | Returns `['records' => [...], 'total' => n]`; restores limit/offset afterwards. |
| `SqlInsert::values(array $data): self`, `SqlInsert::execute(): self` | `execute()` returns self so `rowCount()` / `lastInsertId()` can be chained. |
| `SqlInsert::rowCount(): int` / `lastInsertId(): string\|false` | The id is captured right after `execute()`; `false` before a successful insert. |
| `SqlUpdate::set(array\|string $data): self` | Array → `` `col` = :set_col `` pairs; string → raw SET expression; the last call wins, the two forms never mix. |
| `SqlUpdate::execute() / SqlDelete::execute(): int` | Affected-row count; both refuse to run without a condition. |
| `SqlUpdate::toSql() / SqlDelete::toSql(): string` | DML string; table name and array keys are backtick-quoted. |
| `HasWhereClause::where(string $condition, array $params = []): static` | Sets the raw condition and its named parameters, replacing the previous condition and its parameters. |
| `HasWhereClause::bind(array $params): static` | Merges named parameters for the current condition. |
| `HasWhereClause::filter(array $params): static` | Merges filter-style conditions whose keys may carry an operator suffix. |

`filter()` operator suffixes:

| Suffix | Operator | Example | Generated SQL |
|---|---|---|---|
| (none) | = | `['status' => 1]` | `` `status` = :f_status `` |
| `>` | > | `['age>' => 18]` | `` `age` > :f_age `` |
| `<` | < | `['age<' => 30]` | `` `age` < :f_age `` |
| `>=` | >= | `['age>=' => 18]` | `` `age` >= :f_age `` |
| `<=` | <= | `['age<=' => 30]` | `` `age` <= :f_age `` |
| `!` / `<>` / `><` | != | `['status!' => 0]` | `` `status` != :f_status `` |

Named-placeholder rules:

- `filter()` placeholders are named `f_{field}`, gaining `_2`, `_3`, ... when two conditions target the same field, so they never collide with `where()` / `bind()` parameters.
- `where()` / `bind()` use exactly the `:name` keys the caller wrote.
- `insert()` uses `:{column}`; `update()`'s array form uses `:set_{column}`, so SET never collides with WHERE.

## 4. Invariants and error behaviour

- `select()->toSql()` / `select()->count()` without `from()`, `insert()` without `values()`, `update()` without `set()` → `SqlException`.
- `update()->execute()` / `delete()->execute()` with no condition at all → `SqlException`; a whole-table write needs an explicit `where('1 = 1')`.
- A `filter()` key that does not match `^(\w+)([!<>=]{0,2})$` → `SqlException` (`Invalid filter expression`); qualified names such as `u.id` are rejected this way.
- Table names (and array keys) for insert/update/delete must match `^\w+$`, else `SqlException`; `select()->from()` is the one slot left raw.
- `PDO::prepare()` returning `false`, or `execute()` returning `false` under `ERRMODE_SILENT`, → `SqlException` — never a swallowed empty result.
- `singleOrFail()` with no row → `RecordNotFoundException` (extends `SqlException`, extends `RuntimeException`); `paginate()` with `page < 1` or `pageSize < 1` → `InvalidArgumentException`.
- `where()` replaces the condition with its parameters, `bind()` / `filter()` merge, and `single()` / `paginate()` temporarily override limit/offset, always restoring them in a `finally`.
- `count()` ignores limit/offset/orderBy; a `groupBy()` turns it into a counted subquery.
- The builder holds no global state: the `PDO` and the logger are constructor-injected, and the logger only receives `debug()` calls.

## 5. Dependencies

### 5.1 Required

- Runtime (composer.json): `php: ^8.1`, `ext-pdo: *`, `psr/log: ^3.0`; development only: `phpunit/phpunit: ^10`, `phpstan/phpstan: ^2.2`.

### 5.2 Forbidden by design

- The upper data layers — `migears/dao`, `migears/domain`, `migears/cache`, `migears/manager` (this is the bottom layer; the DAO calls it, never the reverse, and it stays unaware of Domain objects and caching), and any ORM, schema/migration tooling, connection pool or framework container (the connection and logger are injected, and the package holds no configuration).

## 6. Test plan

Integration tests run against an in-memory SQLite `users` table seeded with five rows (`tests/TestCase.php`), with `failOnWarning` / `failOnNotice` / `failOnDeprecation` / `failOnRisky` enabled.

- `SqlBuilderTest` — each factory returns the right builder type, `getPdo()` returns the same instance, construction without a logger works.
- `SqlSelectTest` — all-rows / string / array field selection; `where()` with inline and separate `bind()`; all six `filter()` operators plus combined conditions; `orderBy`, `limit`, `limit` + `offset`, `groupBy`; `single()` (and that it does not disturb the caller's limit), `singleOrFail()` (found and `RecordNotFoundException`), `count()` (all / filtered / grouped), `paginate()` plus its bounds checks; `toSql()` shape; missing `from()`; invalid filter keys; empty result; same-field filter placeholder uniqueness; `where()` + `filter()` same-field isolation; a second `where()` replacing parameters; silent-mode prepare/execute failures; `from()` raw JOIN splice; `filter()` rejecting a qualified name.
- `SqlInsertTest` — insert with `lastInsertId()` and `rowCount()`, `null` values, `toSql()`, missing `values()`, self chaining, backtick/space column rejection, backtick table rejection, `lastInsertId()` before and after later inserts, silent-mode failure.
- `SqlUpdateTest` — single and multiple columns, `where()` bind, `filter()`, raw SET string, no match, `toSql()`, missing `set()`, `set_`/WHERE isolation, identifier rejection, no-condition refusal (rows left unchanged), update-all with an explicit condition, `set()` form replacement.
- `SqlDeleteTest` — by id, `where()`, `filter()`, no match, no-condition refusal (table left intact), delete-all with an explicit condition, `toSql()` with and without WHERE, table rejection.
- `ExceptionTest` — `SqlException` is a `RuntimeException` (code and previous carried), `RecordNotFoundException` extends it and formats the message for table/id and for no arguments.

Cases that must stay covered: the six `filter()` operators and the `f_` collision rule; `where()`/`filter()` placeholder isolation; the no-condition guard for update/delete; identifier validation on DML; silent-mode `false` → `SqlException`; `RecordNotFoundException` from `singleOrFail()`; limit/offset restoration after `single()`/`paginate()`.

## 7. Future candidates

Contract changes currently queued in the module's own issue record (`issues/`), not in the shipped behaviour:

- `P3-4` — table names are validated with `^\w+$` while column names are not restricted the same way (identifier-validation asymmetry).
- `P3-5` — `offset()` without `limit()` emits a bare `OFFSET` clause, which is a syntax error in SQLite and MySQL.
- `P3-6` — `where('', ['x' => 1])` still binds `:x` although the empty condition generates no SQL.
- `G1` (accepted) — a missing logger is silent by construction: every constructor defaults the argument to a live `NullLogger`.

---

# migears/sql 模块规格说明

Version: 2.0.0
Date: 2026-09-29

## 1. 定位

`migears/sql` 是位于 miGears 三层数据架构（Service → DAO → SQL → PDO）最底层的轻量 SQL 查询构建器。它借用调用方传入的 `PDO` 实例，把链式方法调用变成 SQL 字符串与绑定参数，并返回纯数组。它不是 ORM：既不持有连接也不持有配置，不做映射，对 Domain 对象一无所知。

## 2. 边界

### 2.1 范围内

- 借助调用方传入的 `PDO` 构建并执行 SQL：`select()` / `insert()` / `update()` / `delete()`，`where()` / `filter()` / `bind()` / `orderBy()` / `limit()` / `groupBy()`，`single()` / `singleOrFail()` / `count()` / `paginate()`。
- 以纯数组返回行，并以 `SqlException` / `RecordNotFoundException` 命名失败。

### 2.2 范围外（刻意不做）

- 拥有或打开连接 —— `PDO` 由调用方传入；本包从不自行连接，也不持有配置。
- Domain 对象、映射与 hydrate —— 由 `migears/dao` 把行变成 Domain；本层对之一无所知。
- schema、迁移、事务、连接池，以及 ORM。
- 以带限定符的列名过滤 —— `filter()` 只接受未限定的列名；JOIN 查询请用 `where()`。

## 3. 公开契约

| 入口 | 行为 |
|---|---|
| `SqlBuilder::__construct(PDO $pdo, LoggerInterface $logger = new NullLogger())` | 入口，`new` 后即可用；logger 可选，默认为 `NullLogger`。 |
| `SqlBuilder::select(string\|array\|null $fields = null): SqlSelect` | `null` → `*`，数组 → `implode(', ', ...)`，字符串 → 原样。 |
| `SqlBuilder::insert\|update\|delete(string $table)` | 为 `$table` 新建 `SqlInsert` / `SqlUpdate` / `SqlDelete` 构建器。 |
| `SqlBuilder::getPdo(): PDO` | 原样返回借用的连接。 |
| `SqlSelect::from(string $table): self` | 表子句，原样透传（允许 JOIN、逗号分隔与别名表达式）。 |
| `SqlSelect::orderBy(string) / groupBy(string) / limit(int) / offset(int): self` | 各存一个原始子句或整数；`toSql()` 按此固定顺序拼接。 |
| `SqlSelect::toSql(): string` | 生成 `SELECT ... [GROUP BY] [ORDER BY] [LIMIT] [OFFSET]`。 |
| `SqlSelect::execute(): array` | 返回全部匹配行 `array<int, array<string, mixed>>`；无结果时 `[]`。 |
| `SqlSelect::single(): ?array` | 强制 `LIMIT 1`，返回该行或 `null`；之后恢复原有的 limit/offset。 |
| `SqlSelect::singleOrFail(): array` | 即 `single()`，为 `null` 时抛 `RecordNotFoundException`。 |
| `SqlSelect::count(): int` | `COUNT(*)`，忽略 limit/offset/orderBy；设置了 `groupBy()` 时包成子查询。 |
| `SqlSelect::paginate(int $page, int $pageSize): array` | 返回 `['records' => [...], 'total' => n]`；之后恢复 limit/offset。 |
| `SqlInsert::values(array $data): self`、`SqlInsert::execute(): self` | `execute()` 返回 self，可链式调用 `rowCount()` / `lastInsertId()`。 |
| `SqlInsert::rowCount(): int` / `lastInsertId(): string\|false` | id 在 `execute()` 之后立即捕获；成功插入前为 `false`。 |
| `SqlUpdate::set(array\|string $data): self` | 数组 → `` `col` = :set_col `` 键值对；字符串 → 原始 SET 表达式；以最后一次调用为准，两形式不混用。 |
| `SqlUpdate::execute() / SqlDelete::execute(): int` | 返回受影响行数；两者在无条件时拒绝执行。 |
| `SqlUpdate::toSql() / SqlDelete::toSql(): string` | DML 字符串；表名与数组键均加反引号。 |
| `HasWhereClause::where(string $condition, array $params = []): static` | 设置原始条件及其具名参数，替换上一个条件及其参数。 |
| `HasWhereClause::bind(array $params): static` | 为当前条件合并具名参数。 |
| `HasWhereClause::filter(array $params): static` | 合并 filter 风格条件，其键可带操作符后缀。 |

`filter()` 操作符后缀：

| 后缀 | 操作符 | 示例 | 生成 |
|---|---|---|---|
| （无） | = | `['status' => 1]` | `` `status` = :f_status `` |
| `>` | > | `['age>' => 18]` | `` `age` > :f_age `` |
| `<` | < | `['age<' => 30]` | `` `age` < :f_age `` |
| `>=` | >= | `['age>=' => 18]` | `` `age` >= :f_age `` |
| `<=` | <= | `['age<=' => 30]` | `` `age` <= :f_age `` |
| `!` / `<>` / `><` | != | `['status!' => 0]` | `` `status` != :f_status `` |

具名占位符规则：

- `filter()` 占位符命名为 `f_{字段名}`，同一字段出现两个条件时追加 `_2`、`_3`……，因此永远不会与 `where()` / `bind()` 的参数冲突。
- `where()` / `bind()` 使用的正是调用方写下的 `:name` 键名。
- `insert()` 使用 `:{列名}`；`update()` 的数组形式使用 `:set_{列名}`，因此 SET 永不与 WHERE 冲突。

## 4. 不变量与错误行为

- `select()->toSql()` / `select()->count()` 未调用 `from()`，`insert()` 未调用 `values()`，`update()` 未调用 `set()` → 抛 `SqlException`。
- `update()->execute()` / `delete()->execute()` 完全没有条件 → 抛 `SqlException`；确实要写全表需显式 `where('1 = 1')`。
- `filter()` 的键不匹配 `^(\w+)([!<>=]{0,2})$` → 抛 `SqlException`（`Invalid filter expression`）；`u.id` 这类限定名即由此被拒绝。
- insert/update/delete 的表名（以及数组键）必须匹配 `^\w+$`，否则抛 `SqlException`；`select()->from()` 是唯一保留原样的位置。
- `PDO::prepare()` 返回 `false`，或 `ERRMODE_SILENT` 下 `execute()` 返回 `false` → 抛 `SqlException`，绝不被吞成空结果。
- `singleOrFail()` 找不到行 → `RecordNotFoundException`（继承 `SqlException`，再继承 `RuntimeException`）；`paginate()` 的 `page < 1` 或 `pageSize < 1` → `InvalidArgumentException`。
- `where()` 连同参数一起替换条件，`bind()` / `filter()` 合并，而 `single()` / `paginate()` 会临时覆盖 limit/offset，并在 `finally` 中始终恢复。
- `count()` 忽略 limit/offset/orderBy；设置 `groupBy()` 时它变成计数子查询。
- 构建器不持有全局状态：`PDO` 与 logger 由构造函数注入，logger 只接收 `debug()` 调用。

## 5. 依赖

### 5.1 必需

- 运行时（composer.json）：`php: ^8.1`、`ext-pdo: *`、`psr/log: ^3.0`；仅开发期：`phpunit/phpunit: ^10`、`phpstan/phpstan: ^2.2`。

### 5.2 设计上禁止

- 上层数据层 —— `migears/dao`、`migears/domain`、`migears/cache`、`migears/manager`（这是最底层：DAO 调用它，绝不反过来，且它必须对 Domain 对象与缓存一无所知），以及任何 ORM、schema/迁移工具、连接池或框架容器（连接与 logger 都由外部传入，本包不持有配置）。

## 6. 测试计划

集成测试跑在内存 SQLite 的 `users` 表上，预置五行数据（`tests/TestCase.php`），并开启 `failOnWarning` / `failOnNotice` / `failOnDeprecation` / `failOnRisky`。

- `SqlBuilderTest` —— 各工厂返回对应的构建器类型，`getPdo()` 返回同一实例，无 logger 也能构造。
- `SqlSelectTest` —— 全表 / 字符串 / 数组字段选择；`where()` 配合内联与分开的 `bind()`；全部六种 `filter()` 操作符及组合条件；`orderBy`、`limit`、`limit` + `offset`、`groupBy`；`single()`（且不干扰调用方的 limit）、`singleOrFail()`（命中与 `RecordNotFoundException`）、`count()`（全表 / 带条件 / 带分组）、`paginate()` 及其边界校验；`toSql()` 形态；缺 `from()`；非法 filter 键；空结果；同字段 filter 占位符唯一；`where()` + `filter()` 同字段隔离；第二次 `where()` 替换参数；SILENT 模式 prepare/execute 失败；`from()` 原样拼接 JOIN；`filter()` 拒绝限定列名。
- `SqlInsertTest` —— 插入并取 `lastInsertId()` 与 `rowCount()`、`null` 值、`toSql()`、缺 `values()`、self 链式、反引号/空格列名被拒、反引号表名被拒、后续插入前后 `lastInsertId()` 稳定、SILENT 模式失败。
- `SqlUpdateTest` —— 单列与多列、`where()` 绑定、`filter()`、原始 SET 字符串、无匹配、`toSql()`、缺 `set()`、`set_`/WHERE 隔离、标识符被拒、无条件拒绝（行保持不变）、带显式条件更新全表、`set()` 形式替换。
- `SqlDeleteTest` —— 按 id、`where()`、`filter()`、无匹配、无条件拒绝（表保持完整）、带显式条件删全表、`toSql()` 有/无 WHERE、表名被拒。
- `ExceptionTest` —— `SqlException` 是 `RuntimeException`（携带 code 与 previous），`RecordNotFoundException` 继承它，并分别格式化带 table/id 与无参数的文案。

必须持续覆盖的用例：六种 `filter()` 操作符与 `f_` 防冲突规则；`where()`/`filter()` 占位符隔离；update/delete 的无条件护栏；DML 的标识符校验；SILENT 模式 `false` → `SqlException`；`singleOrFail()` 抛 `RecordNotFoundException`；`single()`/`paginate()` 之后 limit/offset 的恢复。

## 7. 后续候选

当前排队在本模块自身问题记录（`issues/`）中、尚未进入已发布行为的契约变更：

- `P3-4` —— 表名用 `^\w+$` 校验，而列名的限制与之不同（标识符校验不对称）。
- `P3-5` —— 不带 `limit()` 的 `offset()` 会输出裸 `OFFSET` 子句，在 SQLite 与 MySQL 中是语法错误。
- `P3-6` —— `where('', ['x' => 1])` 仍绑定了 `:x`，而那个空条件不产生任何 SQL。
- `G1`（accepted）—— 缺 logger 在构造上就是静默的：每个构造函数都把参数默认成一个活的 `NullLogger`。
