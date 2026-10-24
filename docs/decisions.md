# S2J Inquiry Destination - 決定事項 (初版)

採用・合意前の **実装固定** を ADR 風に記録します。詳細手順は、各分割 spec が正本です。ガバナンス: [governance/documentation_governance.md](./governance/documentation_governance.md)。

| ID | 決定 | 正本 |
| --- | --- | --- |
| D-001 | Dispatch Runner は、`administrator_mailer/is_sended` で **最大1回**。`skip` では呼ばない | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| D-002 | 管理者宛の成功フラグは、同一リクエストの `MailerHook` 内 **RequestContext** (小さなオブジェクト) | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| D-003 | `mail_to` UI は、textarea **改行区切り** → 保存時 `sanitize_email` 配列 | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-004 | 種別表は、**可変** (行追加・削除)。登録名は、Service0.2+ のスラッグ規則。削除時は、フォーム写像参照中は拒否 | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-005 | 自動返信のみ失敗: 完了維持 + ログ `autoreply_failed` + 完了画面に短い i18n Notice と **相関 ID** | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| D-006 | SMF 検証下限バージョンは、`snow_monkey_forms_spec.md` §前提が正本。README に同じ semver を1行 | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| D-007 | 管理画面は、**3タブ** (`service` / `types` / `forms`)。**タブごと** 別 HTML フォーム・別 nonce ・**当該 option のみ** POST 更新 | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-008 | タブ2・タブ3の一覧は、**`WP_List_Table`**。Bulk / Views / 検索 / 列ソートは、**メディアライブラリ** 一覧に倣う | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-009 | サイト全体 dispatch チャネルは、タブ1の `s2j_inquiry_destination_dispatch.channel` (`mail` / `saas`) | [saas_connection_spec.md](./saas_connection_spec.md) |
| D-010 | **`channel=mail` の間**、タブ1の SaaS 接続は **保存 POST を無視**。UI は初期 `disabled` + **チャネル切替 JS** で mail/saas を同期 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ1 |
| D-011 | `saas_product` (`backlog` / `asana`) → Service **`destination.kind` = `saas_{saas_product}`** | [data_dictionary.md](./data_dictionary.md) |
| D-012 | SaaS 対応後、Runner は material 型で **`wp_mail` または `wp_remote_request`** を1箇所で分岐。Service4関数の呼び出し口は Runner のみ | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |

## D-001— Runner の呼び出し位置

* **採用:** `snow_monkey_forms/administrator_mailer/is_sended` コールバック内で `Dispatch\Runner` を **1回だけ** 実行する。
* **採用しない:** `skip` フィルター内での Runner 実行 (二重送信の恐れ)。
* Service の4関数は `Runner.php` のみが呼ぶ ([architecture.md](./architecture.md))。

## D-002— リクエスト内コンテキスト

* `MailerHook` が `RequestContext` を保持する (同一 HTTP リクエスト限定)。
* 管理者宛 Runner 成功後に `administrator_mail_succeeded = true` と `correlation_id` を格納。
* `auto_reply_mailer/is_sended` はこのフラグを参照する。

## D-003 / D-004 / D-007— 管理 UI

* タブ1 (service) ・タブ2 (types) ・タブ3 (forms) は、**別 HTML フォーム・別 nonce** (D-007)。保存時は、**当該 option キーのみ** 更新し、他 option は触らない。
* タブ3保存時、`inquiry_type` は `s2j_inquiry_destination_types` に存在する登録名のみ許可 (不存在は保存エラー)。
* タブ2の **Add row** は、保存ボタン左隣。行追加はクライアント側、確定は types 保存 POST。

## D-008— 一覧表 UX

* 種別表: Bulk **削除** (写像参照中は拒否)、Views **宛先未設定**、検索・列ソート (登録名 / 表示名)。
* フォーム表: Bulk **種別変更** / **写像解除**、Views **写像済み / 未写像**、検索・列ソート (タイトル / ID 等)。
* 表内入力の見た目: コア `.regular-text` の固定幅でセルからはみ出さないよう、`assets/admin/types-tab.css` / `forms-tab.css` をタブ表示時に読み込む ([admin_ui_spec.md](./admin_ui_spec.md) §タブ別管理 CSS)。

## D-009— Dispatch チャネル

* `mail` / `saas` をタブ1で保存。Runner は D-012に従い material 型で分岐 — [status.md](./status.md)。
* SaaS 接続・検証: [saas_connection_spec.md](./saas_connection_spec.md)。

## D-010— `channel=mail` 時の SaaS 接続

* `SaasOptions::sanitizeFromPost` は `channel=mail` なら POST 無視。
* UI: 保存済み mail 時は SaaS 製品ラジオ等を初期 `disabled`。画面上で `saas` に切替えた場合は **保存前** から `serviceTabScript()` が製品・接続欄を有効化 ([admin_ui_spec.md](./admin_ui_spec.md))。
* **接続検証** POST も保存しない (option 更新なし)。

## D-011— SaaS 製品 ID と Service `kind`

* タブ1 `saas_product` は **`backlog` | `asana` のみ** (sanitize_key)。
* Runner / 検証 / `map_dispatch_result` 第1引数は **`saas_backlog` / `saas_asana`** (Service [data_dictionary — destination.kind](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md#destinationkind))。

## D-012— Runner の実行分岐 (SaaS)

* `build_dispatch_material` の戻りが MailMaterial → 既存 `wp_mail` 経路。
* HttpRequestMaterial → `wp_remote_request` + SaasExecutionOutcome → `map_dispatch_result( $kind, … )`。
* 添付・ From / Reply-To は **Mail 経路のみ**。

## D-005— 自動返信のみ失敗

* SMF の systemerror に **しない**。
* 訪問者: SMF 完了画面に、本プラグインの i18n Notice (確認メール未送信 + 再送不要 + 相関 ID)。
* ログ: [logging_spec.md](./logging_spec.md) の `failure_code` = `autoreply_failed`。

## D-006— Snow Monkey Forms バージョン

* WordPress プラグイン SMF を Composer では pin しない。
* Integration テストで固定した semver を **README** と **本 spec §前提** に同じ値で書く。
* 添付取得 API は、その semver の SMF ソースを正本に [status.md](./status.md) チェックリストにクラス名を記録する。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-19 | 初版。レビュー指摘1.1–1.6 / 6.x を固定 |
| 2026-10-20 | D-007– D-009。管理 UI3タブ + List Table + dispatch channel |
| 2026-10-20 | D-010。`channel=mail` 時の SaaS 接続 UI / 保存ガード |
| 2026-10-24 | D-011– D-012。Service v0.3 (Backlog / Asana) に合わせた SaaS 仕様 |
