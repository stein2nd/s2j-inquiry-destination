# S2J Inquiry Destination - 決定事項 (初版 + v0.2)

採用・合意前の **実装固定** を ADR 風に記録します。詳細手順は、各分割 spec が正本です。ガバナンス: [governance/documentation_governance.md](./governance/documentation_governance.md)。

| ID | 決定 | 正本 |
| --- | --- | --- |
| D-001 | Dispatch Runner は、`administrator_mailer/is_sended` で **最大1回**。`skip` では呼ばない | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| D-002 | 管理者宛の成功フラグは、同一リクエストの `MailerHook` 内 **RequestContext** (小さなオブジェクト) | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| D-003 | `mail_to` UI は、textarea **改行区切り** → 保存時 `sanitize_email` 配列 | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-004 | 種別表は、**可変** (行追加・削除)。登録名は、Service v0.2+ のスラッグ規則。削除時は、フォームマッピング参照中は拒否 | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-005 | 自動返信のみ失敗: 完了維持 + ログ `autoreply_failed` + 完了画面に短い i18n Notice と **相関 ID** | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| D-006 | SMF 検証下限バージョンは、`snow_monkey_forms_spec.md` §前提が正本。README に同じ semver を1行 | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| D-007 | 管理画面は、**3タブ** (`service` / `types` / `forms`)。**タブごと** 別 HTML フォーム、別 nonce、**当該 option のみ** POST 更新 | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-008 | タブ2・タブ3の一覧は、**`WP_List_Table`**。Bulk / Views / 検索 / 列ソートは、**メディアライブラリ** 一覧に倣う | [admin_ui_spec.md](./admin_ui_spec.md) |
| D-009 | サイト全体 dispatch チャネルは、タブ1の `s2j_inquiry_destination_dispatch.channel` (`mail` / `saas`) | [saas_connection_spec.md](./saas_connection_spec.md) |
| D-010 | **`channel=mail` の間**、タブ1の SaaS 接続は **保存 POST を無視**。UI は初期 `disabled` + **チャネル切替 JS** で mail/saas を同期 | [admin_ui_spec.md](./admin_ui_spec.md) §タブ1 |
| D-011 | `saas_product` (`backlog` / `asana`) → Service **`destination.kind` = `saas_{saas_product}`** | [data_dictionary.md](./data_dictionary.md) |
| D-012 | SaaS 対応後、Runner は material 型で **`wp_mail` または `wp_remote_request`** を1箇所で分岐。Service の4関数の呼び出し口は Runner のみ | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| D-013 | **Mail 認証 (DKIM/SPF/DMARC 実装、DNS 編集)** を本プラグインに **実装しない** | [mail_deliverability_spec.md](./mail_deliverability_spec.md) |
| D-014 | **`Email Auth`** は **推奨コンパニオン**。`require`、同梱、必須 activation **しない** | [mail_deliverability_spec.md](./mail_deliverability_spec.md)、[admin_ui_spec.md](./admin_ui_spec.md) §メール認証案内 |
| D-015 | Mail 経路の **`From:`** は Runner の `$headers` に **含めない**。`wp_mail_from` / `Email Auth` に委譲。**Reply-To** はペイロード `email` (有効時のみ) | [dispatch_execution_spec.md](./dispatch_execution_spec.md) § Mail |
| D-016 | タブ2・3で List Table と保存を同一フォームにする場合、保存 nonce の HTML **name** は `_wpnonce` に **しない** (bulk の `_wpnonce` と衝突)。Bulk 適用時は `bulk-*` nonce を検証 | [admin_ui_spec.md](./admin_ui_spec.md) § POST |
| D-017 | 設定画面の **待ち時間** に、コア **`.spinner`** + `aria-busy` / `aria-disabled` でフィードバック (タブ GET 切替、POST 保存 / Bulk / 接続検証)。全画面オーバーレイは **採用しない** | [admin_ui_spec.md](./admin_ui_spec.md) §読み込み・送信中 |

## D-001— Runner の呼び出し位置

* **採用:** `snow_monkey_forms/administrator_mailer/is_sended` コールバック内で `Dispatch\Runner` を **1回だけ** 実行する。
* **採用しない:** `skip` フィルター内での Runner 実行 (二重送信の恐れ)。
* Service の4関数は `Runner.php` のみが呼ぶ ([architecture.md](./architecture.md))。

## D-002— リクエスト内コンテキスト

* `MailerHook` が `RequestContext` を保持する (同一 HTTP リクエスト限定)。
* 管理者宛 Runner 成功後に `administrator_mail_succeeded = true` と `correlation_id` を格納。
* `auto_reply_mailer/is_sended` はこのフラグを参照する。

## D-003 / D-004 / D-007— 管理 UI

* タブ1 (service)、タブ2 (types)、タブ3 (forms) は、**別 HTML フォーム・別 nonce action** (D-007)。保存時は、**当該 option キーのみ** 更新し、他 option は触らない。
* 各フォームの `action` は当該タブ付き設定画面 URL。POST 後も同一タブ ([admin_ui_spec.md](./admin_ui_spec.md) § POST)。
* タブ3の種別 UI は **select のみ** (表示時点の types)。タブ3保存時、`inquiry_type` は `s2j_inquiry_destination_types` に存在する登録名のみ許可 — 不存在は **FormOptions** が保存エラー (手動 UI からは通常、再現不可、サーバー防御)。
* タブ3のマッピング入力に **HTML5 `required` は付けない** — 未マッピング行が一覧に混在するため。必須判定は [FormOptions](../includes/Options/FormOptions.php) サーバー側のみ。
* タブ2の **Add row** は、保存ボタン左隣。行追加は `typesTabScript()` (クライアント)、確定は types 保存 POST。Bulk 削除 confirm も同スクリプト (POST フォーム `#s2j-inquiry-destination-types-form`)。

## D-008— 一覧表 UX

* 種別表: Bulk **削除** (マッピング参照中は拒否)、Views **宛先未設定**、検索・列ソート (登録名 / 表示名)。Bulk 削除の **適用** 前はブラウザ **`confirm`**。成功通知は保存 (`types_saved`) と削除 (`types_deleted`) で **分離** ([admin_ui_spec.md](./admin_ui_spec.md) §タブ2)。
* フォーム表: Bulk **種別変更** / **マッピング解除**、Views **マッピング済み / 未マッピング**、検索・列ソート (タイトル / ID 等)。
* 表内入力の見た目: コア `.regular-text` の固定幅でセルからはみ出さないよう、`assets/admin/types-tab.css` / `forms-tab.css` をタブ表示時に読み込む ([admin_ui_spec.md](./admin_ui_spec.md) §タブ別管理 CSS)。
* タブ2の Mail: ≤782px Z 字 + `data-colname`。タブ2の SaaS Asana (`--asana`): ≤782px も Mail 同型 Z 字 (副列をコアに隠されない)。タブ2の SaaS Backlog: ビューポート **1489 / 785 / 558 / 557px** の4段階 (正本 §タブ2表)。タブ3: ≤782px `data-colname` 縦積み。
* Bulk と保存の同一フォーム: D-016。保存 nonce フィールド名 `_wpnonce_s2j_*`、Bulk のみ POST は `bulk-inquiry_types` / `bulk-smf_forms`。
* wait フィードバック (POST / タブ GET): **D-017**。

## D-009— Dispatch チャネル

* `mail` / `saas` をタブ1で保存。Runner は D-012に従い material 型で分岐 — [status.md](./status.md)。
* SaaS 接続・検証: [saas_connection_spec.md](./saas_connection_spec.md)。

## D-010— `channel=mail` 時の SaaS 接続

* `SaasOptions::sanitizeFromPost` は `channel=mail` なら POST 無視。
* UI: 保存済み mail 時は SaaS 製品ラジオ等を初期 `disabled`。画面上で `saas` に切り替た場合は **保存前** から `serviceTabScript()` が製品・接続欄を有効化 ([admin_ui_spec.md](./admin_ui_spec.md))。
* **接続検証** POST も保存しない (option 更新なし)。

## D-011— SaaS 製品 ID と Service `kind`

* タブ1 `saas_product` は **`backlog` | `asana` のみ** (sanitize_key)。
* Runner / 検証 / `map_dispatch_result` 第1引数は **`saas_backlog` / `saas_asana`** (Service [data_dictionary — destination.kind](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md#destinationkind))。

## D-012— Runner の実行分岐 (SaaS)

* `build_dispatch_material` の戻りが MailMaterial → 既存 `wp_mail` 経路。
* HttpRequestMaterial → `wp_remote_request` + SaasExecutionOutcome → `map_dispatch_result( $kind, … )`。
* 添付 / From / Reply-To は **Mail 経路のみ** (From の付け方は D-015)。

## D-005— 自動返信のみ失敗

* SMF の systemerror に **しない**。
* 訪問者: SMF 完了画面に、本プラグインの i18n Notice (確認メール未送信 + 再送不要 + 相関 ID)。
* ログ: [logging_spec.md](./logging_spec.md) の `failure_code` = `autoreply_failed`。

## D-006— Snow Monkey Forms バージョン

* WordPress プラグイン SMF を Composer では pin しない。
* Integration テストで固定した semver を **README** と **本 spec §前提** に同じ値で書く。
* 添付取得 API は、その semver の SMF ソースを正本に [status.md](./status.md) チェックリストにクラス名を記録する。

## D-013— Mail 認証の非実装

* **採用:** 問い合わせ Mail の到達性・認証は、サイト全体の **`wp_mail` + 任意の `Email Auth` (等)** に任せる。
* **採用しない:** 本プラグイン内の DKIM 鍵管理、SPF レコード生成、DMARC ポリシー編集。
* Service は Transport を扱わない ([Service transport_boundary_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md))。

## D-014— `Email Auth` コンパニオン

* タブ1 `channel=mail` 時に **案内 notice** を表示 (未導入 / 導入済で文言分岐)。実装: `SettingsPage::renderServiceMailAuthNotice()`。
* Mail チャネルは `Email Auth` **なしでも** 送信可能 (D-009 / D-012維持)。
* **`Email Auth` を Composer / プラグインヘッダーで require しない。**

## D-015— From / Reply-To (Runner)

* **採用:** `Runner::executeMail` の `$headers` から **`From: …` 行を載せない**。
* **維持:** `Content-Type: …`、有効なペイロード `email` がある場合、**`Reply-To: …` のみ**。
* **採用しない:** `Email Auth` 専用 API への依存 (WordPress / PHPMailer フックのみ)。
* **運用:** `Email Auth` の Reply-To グローバル設定と問い合わせ Reply-To の注意 — [mail_deliverability_spec.md](./mail_deliverability_spec.md) §5。

## D-016— 管理 UI nonce (List Table + 保存)

* **背景:** `WP_List_Table` の bulk `tablenav` は hidden **`name="_wpnonce"`** を出力する。保存用 `wp_nonce_field()` もデフォルトで同名のため、**後勝ちで保存 nonce が無効化** され「リンクの期限切れ」になる。
* **採用:** 保存 nonce の HTML name を **`_wpnonce_s2j_service` / `_wpnonce_s2j_types` / `_wpnonce_s2j_forms`** に分離。検証 action 文字列は従来どおり。
* **Bulk のみ POST:** 保存ボタンが POST にないときは **`bulk-inquiry_types`** (タブ2削除) または **`bulk-smf_forms`** (タブ3) を検証。
* **採用:** 各タブ form の **`action`** に `page` + `tab` クエリーを含める (POST 後に **設定 > 一般** に落ちない)。
* **通知:** `SettingsPage::render()` では `settings_errors()` を呼ばない (`options-head.php` に任せる)。

## D-017— 読み込み・送信 wait フィードバック

* **採用:** WordPress 管理画面コアの **`.spinner.is-active`** (独自 GIF / 全画面ギアは使わない)。
* **enqueue:** `settings_page_s2j-inquiry-destination` 表示時は **タブに関係なく** `settings-busy.css` + インライン `adminSubmitBusyScript()` + `wp_localize_script` → `s2jInquiryDestinationAdmin.tabLoadingMessage`。
* **POST:** `.s2j-inquiry-destination-settings` 内 `form[method="post"]` の submit で、押下 submit を無効化し隣接スピナー + **アクティブ** タブリップ内スピナー + ステータス行 (`tabSubmitMessage`)。タブ2の Bulk 削除で `confirm` キャンセル時は busy にしない (`typesTabScript` capture)。
* **GET (タブ切替):** `nav-tab` クリックで当該タブリップ内スピナー + `#s2j-inquiry-destination-tab-loading-status`。navigation はやめない。
* **a11y:** `prefers-reduced-motion` でスピナーアニメ弱体化 ([settings-busy.css](../assets/admin/settings-busy.css))。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-19 | 初版。レビュー指摘1.1–1.6 / 6.x を固定 |
| 2026-10-20 | D-007– D-009。管理 UI は3タブ + List Table + dispatch channel |
| 2026-10-20 | D-010。`channel=mail` 時の SaaS 接続 UI / 保存ガード |
| 2026-10-24 | D-011– D-012。Service v0.3 (Backlog / Asana) に合わせた SaaS 仕様 |
| 2026-10-27 | D-013– D-015。v0.2Mail 配送・`Email Auth` コンパニオン |
| 2026-11-01 | D-016。v0.2.1以降の管理 UI POST / nonce / 通知。タブ3の HTML5 `required` 廃止・狭幅 CSS を D-007 / D-008本文に追記 |
| 2026-11-01 | タブ2の SaaS Backlog 行レイアウト4段 (1489/785/558/557px) を [admin_ui_spec.md](./admin_ui_spec.md) §タブ2、D-008に確定 |
| 2026-11-01 | タブ2の SaaS Asana 狭幅 Z 字 (`--asana`)。手動 #2c-sort (列ソート)、#2h 記録 |
| 2026-11-01 | 手動 #2c: タブ2の Bulk / Views / 検索・列ソート (D-008) Pass |
| 2026-11-01 | タブ2の Bulk 削除: `confirm` + 通知 `types_deleted` / `types_saved` 分離。`typesTabScript`、POST ルーティングを [admin_ui_spec.md](./admin_ui_spec.md) § POST / §タブ2に追記 |
| 2026-11-01 | D-017。POST / タブ GET の `.spinner` フィードバック (`settings-busy.css`、`adminSubmitBusyScript`)。手動 #2j |
| 2026-11-01 | タブ3「不存在 inquiry_type」手動 UI テストを N/A に整理 — select + タブ再読み込み ([admin_ui_spec.md](./admin_ui_spec.md) §タブ3) |
