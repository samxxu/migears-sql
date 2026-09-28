# migears-sql — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (5th round, 2026-09-28).

| | |
|---|---|
| Status | **Best state** |
| Size | src 508 lines (net) · 94 tests · 6 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 1 · P3 3 · other 1 |
| Settled | 0 of 5 |
| Waiting on the owner | _nothing_ |
| Waiting on the reviewer | `P2-1`, `P3-1`, `P3-2`, `P3-3` |
| Waiting on the coordinator | `G1` |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **fixed** | `SqlSelect::execute()` and `count()` call `$stmt->execute()` bare, … |
| [`P3-1`](issues/P3-1.md) | P3 | **fixed** | `phpunit.xml.dist` leaves the strict flags off here too, so the … |
| [`P3-2`](issues/P3-2.md) | P3 | **fixed** | Table identifiers are handled inconsistently: … |
| [`P3-3`](issues/P3-3.md) | P3 | **fixed** | `SqlUpdate::set()` mixes two sides without clearing: `set('a = a + 1')` … |
| [`G1`](issues/G1.md) | - | **question** | A missing logger is silent by construction: the constructor of every … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **5** of 5 |
| By status | `question` 1 · `fixed` 4 |
| Waiting on | reviewer 4 · coordinator 1 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `fixed` | reviewer | `SqlSelect::execute()` and `count()` call `$stmt->execute()` bare, … |
| **P3** | [`P3-1`](issues/P3-1.md) | `fixed` | reviewer | `phpunit.xml.dist` leaves the strict flags off here too, so the … |
| **P3** | [`P3-2`](issues/P3-2.md) | `fixed` | reviewer | Table identifiers are handled inconsistently: … |
| **P3** | [`P3-3`](issues/P3-3.md) | `fixed` | reviewer | `SqlUpdate::set()` mixes two sides without clearing: `set('a = a + 1')` … |
| **-** | [`G1`](issues/G1.md) | `question` | coordinator | A missing logger is silent by construction: the constructor of every … |

## Verdict

A lean SQL query builder with strict identifier validation and proper parameter binding; the one remaining P3 item is identifier-validation asymmetry between table and column names.

## Fixed since the last round

All prior items confirmed fixed except P3-2: P2-1 SELECT execute() return value now checked; P3-1 G2 strict flags complete; P3-3 set() now overwrites instead of accumulating in both string and array forms.

## Test gaps

No test for deeply nested WHERE conditions; no test for ORDER BY with multiple columns and directions; no test for JOIN with ON conditions containing raw expressions.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-sql — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（5th round，2026-09-28）。

| | |
|---|---|
| 状态 | **状态最好** |
| 体量 | src 508 行（净）· 94 个用例 · 6 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 1 · P3 3 · 其他 1 |
| 已了结 | 0 / 5 |
| 等负责人 | _无_ |
| 等评审方 | `P2-1`, `P3-1`, `P3-2`, `P3-3` |
| 等协调人 | `G1` |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **fixed** | SqlSelect::execute() 与 count() 裸调 $stmt->execute()，而 DML 各类都做了检查。SILENT … |
| [`P3-1`](issues/P3-1.md) | P3 | **fixed** | phpunit.xml.dist 也未开严格开关，因此 ISSUES.md 里「PHP 警告即测试失败」的声明对本模块不成立。 |
| [`P3-2`](issues/P3-2.md) | P3 | **fixed** | 表标识符处理不一致：SqlInsert/SqlUpdate/SqlDelete 把 $table … |
| [`P3-3`](issues/P3-3.md) | P3 | **fixed** | SqlUpdate::set() 两侧互不清空：set("a = a + 1") 只写原始侧，set(["b" => 2]) … |
| [`G1`](issues/G1.md) | - | **question** | 缺 logger 在构造上就是静默的：每个 SQL 类的构造函数都把参数默认成一个活的 `NullLogger` … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **5** / 5 |
| 按状态 | `question` 1 · `fixed` 4 |
| 等在谁 | 评审方 4 · 协调人 1 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `fixed` | 评审方 | SqlSelect::execute() 与 count() 裸调 $stmt->execute()，而 DML 各类都做了检查。SILENT … |
| **P3** | [`P3-1`](issues/P3-1.md) | `fixed` | 评审方 | phpunit.xml.dist 也未开严格开关，因此 ISSUES.md 里「PHP 警告即测试失败」的声明对本模块不成立。 |
| **P3** | [`P3-2`](issues/P3-2.md) | `fixed` | 评审方 | 表标识符处理不一致：SqlInsert/SqlUpdate/SqlDelete 把 $table … |
| **P3** | [`P3-3`](issues/P3-3.md) | `fixed` | 评审方 | SqlUpdate::set() 两侧互不清空：set("a = a + 1") 只写原始侧，set(["b" => 2]) … |
| **-** | [`G1`](issues/G1.md) | `question` | 协调人 | 缺 logger 在构造上就是静默的：每个 SQL 类的构造函数都把参数默认成一个活的 `NullLogger` … |

## 结论

一个精简的 SQL 查询构建器，标识符校验严格、参数绑定正确；唯一剩余的 P3 项是表名与列名之间标识符校验不对称。

## 本轮已修复确认

All prior items confirmed fixed except P3-2: P2-1 SELECT execute() return value now checked; P3-1 G2 strict flags complete; P3-3 set() now overwrites instead of accumulating in both string and array forms.

## 测试盲区

无深层嵌套 WHERE 条件测试；无多列多方向 ORDER BY 测试；无含原始表达式 ON 条件的 JOIN 测试。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
