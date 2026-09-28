# migears-sql — Known Issues / 已知问题

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> From the miGears Full-Module Code Review Report (4th round, 2026-09-27).

| | |
|---|---|
| Status / 状态 | **P0 cleared / P0 已清零** |
| Size / 体量 | src 865 lines (522 net) · 86 tests · 8 src files |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## At a glance / 状态一览

| | |
|---|---|
| Items / 条目 | P0 0 · P1 0 · P2 1 · P3 3 · other 0 |
| Answered / 已回复 | 0 of 4 |
| Waiting / 等待回复 | `P2-1`, `P3-1`, `P3-2`, `P3-3` |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **open** | `SqlSelect::execute()` and `count()` call `$stmt->execute()` bare, … |
| [`P3-1`](issues/P3-1.md) | P3 | **open** | `phpunit.xml.dist` leaves the strict flags off here too, so the … |
| [`P3-2`](issues/P3-2.md) | P3 | **open** | Table identifiers are handled inconsistently: … |
| [`P3-3`](issues/P3-3.md) | P3 | **open** | `SqlUpdate::set()` mixes two sides without clearing: `set('a = a + 1')` … |

## Verdict / 结论

DML is now defensive, but the SELECT side was left behind: `SqlSelect::execute()` and `count()` still ignore the return value, so in non-exception mode a failed SELECT silently yields an empty result where a failed INSERT throws.

DML 的防御做全了，但 SELECT 一侧被落下：SqlSelect::execute() 与 count() 仍忽略返回值，因此在非异常模式下 SELECT 失败会静默返回空结果，而 INSERT 失败会抛异常。

## Fixed since the last round / 本轮已修复确认

上一轮 5 项基本落地：prepare() 返回值在五个类中都检查了；DML 的 execute() 检查并抛 SqlException；delete()/update() 无条件下抛错（旧的「全表删除」测试已替换为「无条件即抛」）；lastInsertId 在 execute 后立即取值并文档化连接级限制；占位符文档与实现统一为 f_ 前缀；parseFilterOperator 补 /D；trait 的 abstract getLogger 与三处实现可见性一致；where() 二次调用整体替换参数。 

## Test gaps / 测试盲区

No SILENT-mode SELECT-execute case (only prepare failure is covered); no case for a qualified column in filter (documented but not pinned); no case for `set()` mixing raw and array; no case for a backtick in a table name.

无「SILENT 模式下 SELECT execute 失败」用例（只覆盖了 prepare 失败）；无 filter 限定列名被拒的固化用例（已文档化但未守）；无 set() 混用 raw 与数组的用例；无表名含反引号的用例。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
