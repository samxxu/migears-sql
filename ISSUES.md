# migears-sql — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (6th round, 2026-10-01).

| | |
|---|---|
| Status | **Best state** |
| Size | src 545 lines (net) · 98 tests · 8 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 0 · P3 2 · other 1 |
| Settled | 6 of 9 |
| Waiting on the owner | _nothing_ |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | `P3-4`, `P3-7`, `G1` |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **verified** | `SqlSelect::execute()` and `count()` call `$stmt->execute()` bare, … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | `phpunit.xml.dist` leaves the strict flags off here too, so the … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | Table identifiers are handled inconsistently: … |
| [`P3-3`](issues/P3-3.md) | P3 | **verified** | `SqlUpdate::set()` mixes two sides without clearing: `set('a = a + 1')` … |
| [`P3-4`](issues/P3-4.md) | P3 | **rejected** | Table name validation uses ^\w+$ (strict) but column name validation is … |
| [`P3-5`](issues/P3-5.md) | P3 | **verified** | `offset(n)` without `limit()` emits `… OFFSET n`, which is a syntax … |
| [`P3-6`](issues/P3-6.md) | P3 | **verified** | `where('', ['x' => 1])` binds `:x` although the empty condition … |
| [`P3-7`](issues/P3-7.md) | P3 | **fixed** | count() documents itself as returning 'the total number of matching … |
| [`G1`](issues/G1.md) | - | **fixed** | A missing logger is silent by construction: the constructor of every … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **3** of 9 |
| By status | `rejected` 1 · `fixed` 2 |
| Waiting on | reviewer 3 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P3** | [`P3-4`](issues/P3-4.md) | `rejected` | reviewer | Table name validation uses ^\w+$ (strict) but column name validation is … |
| **P3** | [`P3-7`](issues/P3-7.md) | `fixed` | reviewer | count() documents itself as returning 'the total number of matching … |
| **-** | [`G1`](issues/G1.md) | `fixed` | reviewer | A missing logger is silent by construction: the constructor of every … |

## Verdict

Both degenerate inputs now fail where the caller can see it; what remains is a documentation claim about count() that its groupBy() path does not keep.

## Fixed since the last round

P3-5 and P3-6 verified by mutation: offset() without limit() is refused at build time instead of emitting invalid SQL, and an empty WHERE condition that carries bound parameters now throws instead of failing later on a bind-count mismatch. Reverting either guard turns the module’s own tests red.

## Test gaps

count() combined with groupBy() has no test at all, which is why the finding below escaped; the G1 logger-required change has no landing checklist or guard yet.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-sql — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（6th round，2026-10-01）。

| | |
|---|---|
| 状态 | **状态最好** |
| 体量 | src 545 行（净）· 98 个用例 · 8 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 0 · P3 2 · 其他 1 |
| 已了结 | 6 / 9 |
| 等模块主 | _无_ |
| 等协调人 | _无_ |
| 等评审方 | `P3-4`, `P3-7`, `G1` |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **verified** | SqlSelect::execute() 与 count() 裸调 $stmt->execute()，而 DML 各类都做了检查。SILENT … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | phpunit.xml.dist 也未开严格开关，因此 ISSUES.md 里「PHP 警告即测试失败」的声明对本模块不成立。 |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | 表标识符处理不一致：SqlInsert/SqlUpdate/SqlDelete 把 $table … |
| [`P3-3`](issues/P3-3.md) | P3 | **verified** | SqlUpdate::set() 两侧互不清空：set("a = a + 1") 只写原始侧，set(["b" => 2]) … |
| [`P3-4`](issues/P3-4.md) | P3 | **rejected** | 表名校验使用 ^\w+$（严格），但列名校验限制较少，允许表名不接受的字符——同一契约的两层之间略有不对称。 |
| [`P3-5`](issues/P3-5.md) | P3 | **verified** | 不带 `limit()` 的 `offset(n)` 会输出 `… OFFSET n`，在 SQLite 与 MySQL … |
| [`P3-6`](issues/P3-6.md) | P3 | **verified** | `where('', ['x' => 1])` 绑定了 `:x`，而那个空条件不产生任何 SQL，于是 `execute()` … |
| [`P3-7`](issues/P3-7.md) | P3 | **fixed** | count() 的文档称自己返回「匹配记录总数」，并声明忽略 limit、offset 与 orderBy——但没有提 groupBy。加了 … |
| [`G1`](issues/G1.md) | - | **fixed** | 缺 logger 在构造上就是静默的：每个 SQL 类的构造函数都把参数默认成一个活的 `NullLogger` … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **3** / 9 |
| 按状态 | `rejected` 1 · `fixed` 2 |
| 等在谁 | 评审方 3 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P3** | [`P3-4`](issues/P3-4.md) | `rejected` | 评审方 | 表名校验使用 ^\w+$（严格），但列名校验限制较少，允许表名不接受的字符——同一契约的两层之间略有不对称。 |
| **P3** | [`P3-7`](issues/P3-7.md) | `fixed` | 评审方 | count() 的文档称自己返回「匹配记录总数」，并声明忽略 limit、offset 与 orderBy——但没有提 groupBy。加了 … |
| **-** | [`G1`](issues/G1.md) | `fixed` | 评审方 | 缺 logger 在构造上就是静默的：每个 SQL 类的构造函数都把参数默认成一个活的 `NullLogger` … |

## 结论

两处退化输入现在都在调用方看得见的地方失败；剩下的是 count() 的文档承诺在 groupBy() 路径下不成立。

## 本轮已修复确认

P3-5 and P3-6 verified by mutation: offset() without limit() is refused at build time instead of emitting invalid SQL, and an empty WHERE condition that carries bound parameters now throws instead of failing later on a bind-count mismatch. Reverting either guard turns the module’s own tests red.

## 测试盲区

count() 与 groupBy() 的组合完全没有用例，下面那条 finding 正是因此逃逸；G1 的 logger 必填改动尚无落地清单或守卫。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
