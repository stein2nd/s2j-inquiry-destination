# S2J Inquiry Destination - 実装状況 (v0.2)

**スナップショット:** 2026-10-27 spec マージ前。**正本:** [docs/status.md](../../status.md) (#status-v02)。

最終更新: 2026-10-27

## 参照 (起草)

* [release_v0.2_spec.md](./release_v0.2_spec.md)
* [specs.md](./specs.md) — ミラー一覧
* Service: [transport_boundary_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md)

## v0.2サマリー

| 項目 | 状態 |
| --- | --- |
| 仕様ミラー (`docs_mod/`) | 起草済 |
| D-013– D-015 | [decisions.md](./decisions.md) |
| `Email Auth` 案内 UI | **実装済** |
| Runner D-015 | **未** |
| Composer ^0.4.0 | **未** |
| 手動 smoke | **未記録** |
| `docs/` マージ | **未** |

## 100% チェックリスト

### 仕様 {#status-v02-spec}

* [x] [mail_deliverability_spec.md](./mail_deliverability_spec.md)
* [x] [dispatch_execution_spec.md](./dispatch_execution_spec.md)
* [x] [decisions.md](./decisions.md) (D-013– D-015含む)
* [x] [plugin_spec.md](./plugin_spec.md)、[overview.md](./overview.md)、[specs.md](./specs.md)
* [ ] レビュー合意
* [ ] `docs/` マージ ([README](./README.md) map)

### Runner {#status-v02-runner}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md)、[mail_deliverability_spec.md](./mail_deliverability_spec.md) §5

* [ ] `includes/Dispatch/Runner.php` — `$headers` に `From:` なし
* [ ] Unit / CI green

### Service {#status-v02-service}

* [x] Service **v0.4.0** `docs/` (transport_boundary)
* [ ] プラグイン `composer.json` → `^0.4.0`、`composer.lock`
* [ ] `ServiceGateTest` / dist zip

### UI {#status-v02-ui}

正本: [admin_ui_spec.md](../../admin_ui_spec.md) §メール認証案内、[mail_deliverability_spec.md](../../mail_deliverability_spec.md) §6

* [x] `renderServiceMailAuthNotice()` / `isEmailAuthActive()`
* [x] `serviceTabScript()` — Mail のみ表示
* [ ] (任意) `CompanionStatus` 型

### 手動 {#status-v02-manual}

正本: [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7.1、[test-results.md](./test-results.md)

* [ ] v0.2-1— `Email Auth` 無、Mail 送信
* [ ] v0.2-2— `Email Auth` 有、Mail 送信
* [ ] v0.2-3— パスワードリセット
* [ ] v0.2-4— SaaS 時、案内非表示

### リリース {#status-v02-promote}

* [ ] `npm run lint:docs`
* [ ] CHANGELOG **0.2.0**
* [ ] タグ `v0.2.0`
