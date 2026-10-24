# S2J Inquiry Destination - テスト戦略と結果

環境の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) です。本ファイルは **プラグイン固有** の Pass 記録です。チェック項目の正本一覧は [status.md §100% 完了条件チェックリスト](./status.md#100-完了条件チェックリスト) (非 HTTPS / HTTPS の順) です。

## 段階 (3段階)

| 段階 | 目的 | 本リポジトリ (想定) |
| --- | --- | --- |
| **1** | Local 上で SMF + 本プラグインが有効 | smoke スクリプト (未作成) |
| **2** | Studio / Blueprint で再現 | 任意 |
| **3** | PHPUnit + CI | `tests/Unit` (Runner モック不要)、Integration は SMF 依存。CI は PHP **8.3**、[`composer test`](https://github.com/stein2nd/s2j-inquiry-destination/blob/main/.github/workflows/phpunit.yml) のみ (`npm run phpunit:wp-env` 等は定義しない) |

**依存:** 段階1–2では **Snow Monkey Forms** 有効化が必要です。検証 semver は [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) §前提と [README.md](../README.md) が一致すること。

## Local 可 (初版)

| # | 確認項目 | 期待 | 正本 |
| --- | --- | --- | --- |
| 1 | `composer install` | `s2j/inquiry-destination-service` **^0.3.0** が vendor に入る | [plugin_spec.md](./plugin_spec.md) |
| 1b | プラグイン Unit | `composer test` — **21** 件 (`DispatchOptions`, `SaasOptions` + `resolveForConnectionTest`, `SaasProductCatalog` + `choicePhrase`, `SaasConnectionProbe`, 他) | `tests/Unit/`、[status.md §Options](./status.md#status-options) |
| 2 | 設定画面保存 (タブごと) | 各 POST が **当該 option のみ** 更新 (D-007) | [admin_ui_spec.md](./admin_ui_spec.md) |
| 2b | タブ1の dispatch / SaaS | `channel=mail` 保存時: dispatch のみ更新、接続 POST は `s2j_inquiry_destination_saas` に反映されない (D-010) | [saas_connection_spec.md](./saas_connection_spec.md) |
| 2e | タブ1 UI (手動) | `channel=saas` 選択で SaaS 製品ラジオが有効化。接続欄・**接続を検証** 表示。**検証** POST は option 不変 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ1 |
| 2c | List Table Bulk / Views / 検索 | タブ2・3が [admin_ui_spec.md](./admin_ui_spec.md) D-008に一致 | [decisions.md](./decisions.md) |
| 2d | タブ3フィールド名列 | ブラウザ幅を狭めても `column-fields` 内の `input.regular-text` がセルからはみ出さない | [admin_ui_spec.md](./admin_ui_spec.md) §タブ3 |
| 3 | 対応フォーム送信 (MailHog 等) | To = 種別 `mail_to` (複数時同一1通)、Reply-To = 訪問者 | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 4 | 空宛先種別 | systemerror、相関 ID、完了しない | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 5 | 未対応フォーム | SMF デフォルト管理者宛 (`skip` false) | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| 6 | `error_log` 1行 | PII (個人を識別できる情報) なし、`correlation_id` あり | [logging_spec.md](./logging_spec.md) |
| 7 | 自動返信のみ失敗 | 完了画面に Notice + 相関 ID。ログ `autoreply_failed` | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 8 | 未写像 SMF コントロール | 管理者メール本文末尾に `{name}: {value}` | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| 9 | 複数 `mail_to` | `wp_mail` 1回、To に全アドレス | [admin_ui_spec.md](./admin_ui_spec.md) |
| 10 | 存在しない `inquiry_type` をタブ3保存 | 保存エラー | [admin_ui_spec.md](./admin_ui_spec.md) |
| 11 | SaaS (Backlog / Asana) | `channel=saas` 時、HTTP201 + `external_ticket_id` まで完了。body ログなし | [dispatch_execution_spec.md](./dispatch_execution_spec.md)、Service [testing_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/testing_spec.md) |

## GitHub Actions (main)

| ワークフロー | トリガー | 結果 | Run (例) |
| --- | --- | --- | --- |
| PHPUnit | push `512a3aa` | Pass | [40136890264](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40136890264) |
| Lint Docs | push `512a3aa` | Pass | [40136890383](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40136890383) |
| Build distribution zip | `workflow_dispatch` (main) | Pass · artifact `s2j-inquiry-destination-dist-zip` | [40137154220](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40137154220) |

## 記録

| 日付 | 環境 | 結果 | メモ |
| --- | --- | --- | --- |
| 2026-10-25 | GitHub Actions (`main`) | Pass | 上表 — SaaS 実装コミット `512a3aa` 後。dist はタグ push ではなく手動 dispatch |

## 関連

* [status.md](./status.md)
* [decisions.md](./decisions.md)
* Service 側: [testing_spec (Service)](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/testing_spec.md)
