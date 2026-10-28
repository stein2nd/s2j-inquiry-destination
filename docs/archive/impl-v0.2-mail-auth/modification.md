# 改修記録 — v0.2 Mail 配送・認証

**Slug:** `v0.2-mail-auth`  
**状態:** spec **確定** (2026-10-27)。コード・リリース **未了**。

## 背景

Mail チャネルで DKIM/SPF/DMARC を **`Email Auth`** + `wp_mail` (Transport) に委譲 (D-013– D-015)。Service **v0.4.0** [transport_boundary_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md) と同期。

## 確定した spec (`docs/`)

| ファイル | 内容 |
| --- | --- |
| [mail_deliverability_spec.md](../../mail_deliverability_spec.md) | **新規** — Mail / `Email Auth` |
| [dispatch_execution_spec.md](../../dispatch_execution_spec.md) | D-015、Service ^0.4 |
| [decisions.md](../../decisions.md) | D-013– D-015 |
| [plugin_spec.md](../../plugin_spec.md) / [overview.md](../../overview.md) / [specs.md](../../specs.md) | 索引・境界 |
| [admin_ui_spec.md](../../admin_ui_spec.md) | §メール認証案内 (実装済) |

## コード (未了)

* [x] `Runner::executeMail` — `From:` ヘッダー削除
* [x] `composer.json` **`^0.4.0`**
* [x] `SettingsPage::renderServiceMailAuthNotice()`

## 非目標

`Email Auth` 必須化、本プラグイン内 DKIM、GatherPress 型 UI 統合。

## リリース

* プラグイン **0.2.0** — 上記コード完了後。本 archive の [release_v0.2_spec.md](./release_v0.2_spec.md) 参照。
