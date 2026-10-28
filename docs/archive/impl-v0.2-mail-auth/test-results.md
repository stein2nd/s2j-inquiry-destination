# S2J Inquiry Destination - テスト結果 (v0.2)

**スナップショット:** 2026-10-27。**正本:** [docs/test-results.md](../../test-results.md) (#test-v02)。

## v0.2Mail / `Email Auth`

正本 spec: [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7、[release_v0.2_spec.md](./release_v0.2_spec.md) §5。

| ID | 種別 | 内容 | 結果 | 日付 |
| --- | --- | --- | --- | --- |
| v0.2-1 | 手動 | `Email Auth` 無、Mail チャネル、写像フォーム送信 | 未 | — |
| v0.2-2 | 手動 | `Email Auth` 有、Mail チャネル、写像フォーム送信 | 未 | — |
| v0.2-3 | 手動 | `wp-login.php` パスワードリセット (D-005回帰) | 未 | — |
| v0.2-4 | 手動 | `channel=saas` — タブ1案内非表示 | 未 | — |
| v0.2-5 | Unit | `composer test` green (Runner 変更後) | 未 | — |
| v0.2-6 | CI | `lint:docs` / PHPUnit workflow | 未 | — |

## Service v0.4 (参照)

プラグイン v0.2の Composer 前提。**Service 側 Pass** は Service `docs/test-results.md` が正本。

| 項目 | 状態 |
| --- | --- |
| `docs/core/transport_boundary_spec.md` 公開 | 済 (v0.4.0) |
| Packagist **0.4.0** | プラグイン lock 更新時に確認 |
