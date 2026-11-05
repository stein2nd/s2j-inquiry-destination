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
| 1 | `composer install` | `s2j/inquiry-destination-service` **^0.4.0** が vendor に入る (v0.2) | [plugin_spec.md](./plugin_spec.md) |
| 1b | プラグイン Unit | `composer test` — **26** 件 (`FormOptionsTest` 不正 `inquiry_type`、`RunnerMailHeadersTest` D-015、他) | `tests/Unit/`、[status.md §非 HTTPS · 自動](./status.md#status-local-automated) |
| 2 | 設定画面の保存 (タブごと) | 各 POST が **当該 option のみ** 更新 (D-007) — **Pass** 2026-11-01 (#2i で saas_product 切替も確認) | [admin_ui_spec.md](./admin_ui_spec.md) |
| 2i | タブ1 `saas_product` と種別行 | Asana で種別追加→保存、Backlog に変更→保存後もタブ2に **同一種別行** が残る (types option 不変) — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ2D-007 |
| 2b | タブ1の dispatch / SaaS | `channel=mail` 保存時: dispatch のみ更新、接続 POST は `s2j_inquiry_destination_saas` に反映されない (D-010) — **Pass** 2026-11-01 | [saas_connection_spec.md](./saas_connection_spec.md) |
| 2e | タブ1UI (手動) | `channel=saas` 選択で SaaS 製品ラジオが有効化。接続欄・**接続を検証** 表示。**検証** POST は option 不変 — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ1 |
| 2f | タブ1接続検証エラー後 UI | 検証 POST で警告時も **POST の channel / saas_product** がフォームに残る (保存済み Mail に戻らない) — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) § POST |
| 2c | タブ2List Table (Bulk / Views / 検索) | Views ・検索・一括削除 (confirm / 削除通知)、列ソート (#2c-sort) — **Pass** 2026-11-01。タブ3List Table は別途 | [decisions.md](./decisions.md) D-008 |
| 2c-sort | タブ2列ソート | **タイプ (スラッグ)** / **表示名** 列見出しクリックで昇降反映 — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §一覧表 |
| 2g | タブ2SaaS Backlog 行レイアウト | 1489 / 785 / 558 / 557px の4段 — 意図通り (Local 目視) — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ2 |
| 2h | タブ2SaaS Asana 狭幅 | ≤782px: 表示名・プロジェクト ID 入力が表示される (Z 字、`--asana`) — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ2Asana |
| 2d | タブ3狭幅レイアウト | ≤782px: `data-colname` 縦積み・長ラベル省略なし・`column-fields` はみ出しなし — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ3 |
| 2j | 管理 UI wait フィードバック (D-017) | タブ GET 切替: nav-tab 内スピナー + 読み込みステータス行。POST: 保存 / 接続検証 / Bulk 適用で submit 横スピナー — **Pass** 2026-11-01 | [admin_ui_spec.md](./admin_ui_spec.md) §読み込み・送信中 |
| 3 | 対応フォーム送信 (Mailpit / MailHog 等) | To = 種別 `mail_to` (複数時同一1通)、Reply-To = 訪問者。**From** は Runner `$headers` に載せない (D-015)。`From`/Reply-To は Unit も ([RunnerMailHeadersTest](../tests/Unit/Dispatch/RunnerMailHeadersTest.php)) — **Pass** 2026-11-03 (Local Mailpit、下記 §記録) | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 4 | 空宛先種別 | systemerror、相関 ID、完了しない | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 5 | 未対応フォーム | SMF デフォルト管理者宛 (`skip` false) | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| 6 | `error_log` 1行 | PII (個人を識別できる情報) なし、`correlation_id` あり — **Pass** 2026-11-03 (Local `php/error.log`、#6セッション) | [logging_spec.md](./logging_spec.md) |
| 7 | 自動返信のみ失敗 | 完了画面に Notice + 相関 ID。ログ `autoreply_failed` | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 8 | 未マッピング SMF コントロール | 管理者メール本文末尾に `{name}: {value}` | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| 9 | 複数 `mail_to` | `wp_mail` 1回、To に全アドレス — **Pass** 2026-11-03 (Local Mailpit、#9セッション) | [admin_ui_spec.md](./admin_ui_spec.md) |
| 10 | 存在しない `inquiry_type` をタブ3保存 | **手動 UI: N/A** — `<select>` のため不可。**Unit:** `FormOptionsTest` が POST 改ざん相当を拒否 — Pass | [admin_ui_spec.md](./admin_ui_spec.md) §タブ3 |
| 11 | SaaS (Backlog / Asana) | `channel=saas` 保存 + 種別 SaaS 宛先 + マッピングフォーム。HTTP/201 + `external_ticket_id`。Mail チャネルでは不可 | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |

## GitHub Actions (main)

| ワークフロー | トリガー | 結果 | Run (例) |
| --- | --- | --- | --- |
| PHPUnit | push `512a3aa` | Pass | [40136890264](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40136890264) |
| Lint Docs | push `512a3aa` | Pass | [40136890383](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40136890383) |
| Build distribution zip | `workflow_dispatch` (main) | Pass · artifact `s2j-inquiry-destination-dist-zip` | [40137154220](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40137154220) |

## v0.2Mail / `Email Auth` {#test-v02}

正本: [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7.1。

| ID | 種別 | 内容 | 結果 | 日付 |
| --- | --- | --- | --- | --- |
| v0.2-1 | 手動 | `Email Auth` 無、Mail チャネル、マッピングフォーム送信 | **Pass** 2026-11-03 | Local Mailpit (#3と同一セッション) |
| v0.2-2 | 手動 | `Email Auth` 有、Mail チャネル、マッピングフォーム送信 | **Pass** 2026-11-03 | Local Mailpit · Email Auth **有効・未設定** (#6セッション) |
| v0.2-3 | 手動 | パスワードリセット (D-005回帰) | **Pass** 2026-11-03 | Local Mailpit · `wp-login.php` 忘れたほう (リンク未使用) |
| v0.2-4 | 手動 | `channel=saas` — タブ1案内非表示 | **Pass** 2026-11-03 | タブ1SaaS 選択・**未保存**で案内非表示 (#6セッション) |

## 記録

| 日付 | 環境 | 結果 | メモ |
| --- | --- | --- | --- |
| 2026-10-25 | GitHub Actions (`main`) | Pass | 上表 — SaaS 実装コミット `512a3aa` 後。dist はタグ push ではなく手動 dispatch |
| 2026-10-27 | docs | Pass | v0.2の spec を `docs/` にマージ ([archive/impl-v0.2-mail-auth/](./archive/impl-v0.2-mail-auth/)) |
| 2026-11-01 | Local `http://localhost:10081` | Pass | D-007: タブ2 `recruit`/`sales` 表示名のみ変更→保存。タブ3種別ドロップダウンに反映。タブ1 (Mail / Email Auth 案内) 不変。各タブの無変更保存も成功 (nonce / 通知修正後) |
| 2026-11-01 | Local `http://localhost:10081` | Pass | タブ3狭幅 (#2d): `forms-tab.css` で `data-colname` 上・コントロール下の縦積み。「フィールド名 (SMF コントロール名)」全文表示、入力はみ出しなし |
| 2026-11-01 | Local `http://localhost:10081` | Pass | D-010 (#2b): Mail → SaaS (Asana) で API ベース URL 入力→ Mail に戻して保存→タブ往復→再 Asana で URL 未反映 (option 不更新の UI 確認) |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2f: SaaS で製品未選択 / Backlog+URL 空の各 **接続の検証** → 警告表示時も channel ・製品が POST どおり |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2e: `serviceTabScript` で SaaS 時に製品・接続欄・検証ボタン有効。**接続の検証** (Backlog404等) 後も dispatch / saas option 不変。未設定時の秘密ガイド・ POST 再表示 (#2f) 含む一連確認 |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2g: タブ2SaaS Backlog4段行レイアウト (1489/785/558/557px) 目視確認 |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2c-sort: タブ2「タイプ (スラッグ)」「表示名」列ソート |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2h: タブ1SaaS → Asana 保存後、タブ2≤782px で slug / 表示名 / プロジェクト ID 表示 |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2i: Asana →種別2件追加保存→ Backlog 切替保存→タブ2に2件とも表示 (types / dispatch 分離) |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2c: タブ2Views (すべて / プロジェクト未設定等) ・検索・ Bulk 削除 (confirm ・削除通知) ・列ソート (スラッグ / 表示名) |
| 2026-11-01 | Local `http://localhost:10081` | Pass | #2j: D-017— タブ切替・ POST 保存/検証/Bulk で `.spinner` + 読み込みステータス (二重操作の防止) |
| 2026-11-03 | Local (Mailpit / Flywheel SMTP) | Pass | **#3 / v0.2-1:** タブ2 `recruit`・`sales` 保存 (各1宛先) → タブ3SMF フォーム ID **157** を `recruit` にマッピング (`fullname` / `email` / `tel-*` / `message`) → フロント送信 (任意の電話番号は空) → SMF **完了画面**。Mailpit **2通:** (A) 管理者通知 — To = `recruit` の `mail_to`、Subject `[recruit]` + 種別表示名、Reply-To = 訪問者 `email`、本文 = マッピング4項目 (空の tel は行なしまたは空)。Headers に Runner 由来の `From:` 直書きなし (`wordpress@localhost.local` はコア Transport)。(B) SMF **自動返信** (`自動返信通知`) — 本プラグイン Runner 外。**証跡:** ローカルスクショのみ (実メールアドレス含むため **GitHub 等に非掲載**) |
| 2026-11-03 | Local (Mailpit / Flywheel SMTP) | Pass | **#6 / v0.2-2 / v0.2-4:** タブ1Mail 保存済み + **Email Auth 有効 (未設定)** で案内表示 → タブ1 **SaaS 未保存**選択で案内非表示 → マッピングフォーム **157** 送信 → 完了画面 + Mailpit 管理者1通 (`[recruit]` 件名)。`php/error.log` JSON: `correlation_id` `sid-c6fa…`・`inquiry_type` `recruit`・`form_id` **157**・`success` true ・`channel` **mail** (未保存 SaaS UI は dispatch 不変)。PII ・具体 To なし |
| 2026-11-03 | Local (Mailpit / Flywheel SMTP) | Pass | **v0.2-3:** ログアウト → `wp-login.php` **パスワードをお忘れですか** → ユーザー名送信 → 確認画面 → Mailpit にコア **パスワードリセット** 1通 (件名 `[サイト名] パスワードのリセット`)。**リセット URL は未クリック** (既存パスワード維持)。Runner 非経路 · Email Auth 有効・未設定 |
| 2026-11-03 | Local (Mailpit / Flywheel SMTP) | Pass | **#9:** タブ2 `recruit` の `mail_to` **2行** 保存 → マッピングフォーム送信 → 完了画面。Mailpit **管理者1通** — **To1行に2アドレス** (カンマ区切り)。Subject `[recruit]` + 表示名。SMF 自動返信は別1通 (Runner 外) |

### 手動証跡 (PII)

Mail / 送信系の目視 Pass は、**実在メールアドレス・氏名を含むスクショをリポジトリにコミットしない**。上表の §記録行が正本。再現手順は [status.md § Mail Local](./status.md#status-mail-local) の共通前提。

## 関連

* [status.md](./status.md)
* [decisions.md](./decisions.md)
* Service 側: [testing_spec (Service)](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/testing_spec.md)
