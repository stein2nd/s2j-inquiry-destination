# S2J Inquiry Destination - 管理 UI 仕様

## 設計意図

運営者 (`manage_options`) が、**問い合わせの届け先チャネル** (メール / 将来は問い合わせ管理 SaaS)、**種別ごとの宛先**、**Snow Monkey Forms フォームの対応** を **設定 > S2J Inquiry Destination** だけで終えられるようにします。計算規則は Service に置きません。

製品の到達点は、統一ペイロードから **問い合わせ管理 SaaS (Backlog / Asana)** で起票することです。Runner は **mail / SaaS HTTP** 双方に対応 ([status.md](./status.md)、[dispatch_execution_spec.md](./dispatch_execution_spec.md))。UI は Service **^0.3** に追随 ([saas_connection_spec.md](./saas_connection_spec.md))。

## 画面概要

React は必須にしません。WordPress 管理画面の **タブ** + **`WP_List_Table` サブクラス** (コア API) で実装します ([decisions.md](./decisions.md) D-007 / D-008)。

| 項目 | 内容 |
| --- | --- |
| メニュー | **設定 > S2J Inquiry Destination** |
| スラッグ (例) | `s2j-inquiry-destination` |
| タブ切替 | `nav-tab-wrapper` + クエリー `tab=` (`service` / `types` / `forms`)。保存 POST 後も **同一タブ** (form `action` + hidden `tab`、§ POST)。GET 切替時の wait 表示は §[読み込み・送信中](#読み込み送信中) (D-017) |
| 権限 | `manage_options` のみ |
| SMF 無効時 | 画面は開く。送信は動かない。**Snow Monkey Forms を有効化してください** 旨の `Notice` を出す ([snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)) |

### タブ構成

タブ2の見出しは **Mail に限定しない** 名称とします (SaaS 有効化後、種別行に SaaS 向け項目が増えてもタブ名を維持できる)。

| タブ ( `tab=` ) | 見出し (例) | 内容 |
| --- | --- | --- |
| `service` | 問い合わせ管理サービス | dispatch チャネル (`mail` / `saas`)、SaaS 製品 (`backlog` / `asana`)、サイト接続 |
| `types` | 問い合わせタイプと宛先 | 種別行。チャネルに応じ `mail_to` または SaaS プロジェクト等 |
| `forms` | Snow Monkey Forms のマッピング | 公開 SMF フォームと種別・フィールドマッピング |

### 一覧表の UX 基準 (メディアライブラリ)

タブ2・タブ3の表は、WordPress **メディアライブラリ** 一覧に倣います ([decisions.md](./decisions.md) D-008)。実装は `TypesListTable` / `FormsListTable` (`includes/Admin/`)。

| 要素 | 要件 |
| --- | --- |
| 表本体 | `WP_List_Table` (チェック列、列見出し、行) |
| `tablenav` 上段 | **Bulk actions** ドロップダウン + 適用、**Views** (`subsubsub`)、必要なら月/件数フィルターに相当する絞り込み |
| `tablenav` 下段 | ページネーション (フォーム数が多いタブ3で必須。タブ2は行数に応じて) |
| 検索 | 表上部の検索ボックス (GET `s`)。タブ2: slug / 表示名。タブ3: フォームタイトル / 投稿 ID |
| ソート | クリック可能な列見出し (昇降)。タブ2: 登録名、表示名。タブ3: タイトル、投稿 ID、種別、投稿日 |
| 保存ボタン | 各タブ **1HTML フォーム = 1保存用 nonce** (フィールド名は下記 § POST)。Bulk 適用も **同一 POST** 内で処理 |

### POST ・フォーム action、nonce、通知

親画面は **設定 > 一般** (`options-general.php`) 上のサブメニューです。各タブは **1HTML `<form method="post">`** です。

| 項目 | 規則 |
| --- | --- |
| `action` | `options-general.php?page=s2j-inquiry-destination&tab={service\|types\|forms}` ([SettingsPage::settingsScreenUrl](../includes/Admin/SettingsPage.php))。`action` 省略 (コア一般設定 URL のみ) だと POST 後に **設定 > 一般** 本体に戻るため **付与必須** |
| タブ維持 | 各フォームに hidden `page` / `tab`。POST 後の表示タブは `currentTab()` が **POST の `tab` を優先** (GET `tab` より先) |
| タブ1再表示 | **接続を検証** POST 後 (成功/失敗): **POST の `channel` / `saas_product`** でラジオ描画。`endpoint` も POST 再表示 (秘密は再表示しない)。**サービス設定の保存が成功** した POST 直後は **`DispatchOptions::get()`** (保存結果) を描画 — POST に古い SaaS 値が残っても UI を上書きしない。保存 **失敗** 時のみ POST replay |
| 成功・エラー通知 | `add_settings_error( 's2j-inquiry-destination', … )`。表示は親 `options-general.php` が読み込む `options-head.php` 内の **`settings_errors()` のみ** — `SettingsPage::render()` では **二重表示を避けて呼ばない** |
| List Table | タブ2・3は表 + 保存を **同一フォーム** で包む。`WP_List_Table::display()` の `tablenav` が出力する bulk 用 hidden **`_wpnonce`** (action `bulk-{plural}`) は、保存用 nonce と **同名フィールドのため上書きされる** ([decisions.md](./decisions.md) D-016) |

**保存用 nonce** (検証 action は従来どおり。HTML の **name** のみ分離):

| タブ | 検証 action | フィールド name (定数) |
| --- | --- | --- |
| 1service | `s2j_inquiry_destination_service` | `_wpnonce_s2j_service` |
| 2types | `s2j_inquiry_destination_types` | `_wpnonce_s2j_types` |
| 3forms | `s2j_inquiry_destination_forms` | `_wpnonce_s2j_forms` |

**Bulk 適用 POST** (保存ボタン名が POST に **無い** 場合):

| タブ | 条件 | 検証 |
| --- | --- | --- |
| 2types | `action=delete` かつチェックした slug あり | `check_admin_referer( 'bulk-inquiry_types' )` — コア List Table が出力した `_wpnonce` |
| 3forms | `action=clear_mapping` または `change_type` かつチェックした form ID あり | `check_admin_referer( 'bulk-smf_forms' )` |

Bulk のみの POST も `handlePost()` がタブ2/3ハンドラにルーティングする (`isTypesBulkPost` / `isFormsBulkPost`)。保存ボタン付き POST は従来どおり **保存用 nonce** を検証する。

| タブ | Bulk ルーティング条件 (保存ボタン名が POST に **無い**) |
| --- | --- |
| 2types | `tab=types` かつ `action` / `action2` = `delete` かつ `type[]` が1件以上 (`isTypesBulkPost`) → `handleTypesPost()` |
| 3forms | `tab=forms` かつ `clear_mapping` または `change_type` かつ `form[]` が1件以上 (`isFormsBulkPost`) → `handleFormsPost()` |

**タブ2Bulk 削除 (サーバー):** POST の `types[…]` 行データに加え、チェックした slug を `type[]` で受け取る。`TypeOptions::markRemovedInInput()` で該当行に `remove=1` を付与し、既存の `sanitizeFromPost()` で option から除去 (マッピング参照中は D-004でエラー)。

### タブ別管理 CSS / JS (`assets/admin/`、`SettingsPage`)

`SettingsPage::enqueueAssets` は **設定画面を開いたら常に** (表示タブに関係なく) D-017用アセットを読み込む。タブ2・3の表レイアウト CSS は **当該タブ表示時のみ** 追加 enqueue。

| 対象 | ファイル / スクリプト | スコープ (例) | 目的 |
| --- | --- | --- | --- |
| **全タブ** | [settings-busy.css](../assets/admin/settings-busy.css) | `.s2j-inquiry-destination-settings` | POST / タブ nav の `.spinner` 配置、`prefers-reduced-motion` |
| **全タブ** | インライン `adminSubmitBusyScript()` | `.wrap.s2j-inquiry-destination-settings` | POST busy + タブ GET busy (下記 §読み込み・送信中) |
| **全タブ** | `wp_localize_script` → `s2jInquiryDestinationAdmin` | — | `tabLoadingMessage` (GET 切替)、`tabSubmitMessage` (POST 保存 / Bulk / 接続検証) |
| `types` | `types-tab.css` | `.s2j-inquiry-types-list-table` + wrap | Mail、Asana ≤782px Z 字。Backlog4段 (§タブ2表) |
| `types` | インライン `typesTabScript()` | `#s2j-inquiry-destination-types-form` | **Add row** + Bulk **削除** `confirm` (capture)。`s2jInquiryDestinationTypesTab.confirmBulkDelete` |
| `forms` | `forms-tab.css` | `.s2j-inquiry-forms-list-table` + wrap | 狭幅 `data-colname` 縦積み |
| `service` | インライン `serviceTabScript()` | `.s2j-inquiry-destination-tab-service` | チャネル / SaaS 製品 / 接続欄同期 |

**HTML フォーム ID (POST):**

| タブ | form `id` / class |
| --- | --- |
| 1 | `class="s2j-inquiry-destination-tab-service"`。接続検証・**保存** は `s2j-inquiry-destination-submit-with-spinner` でボタン + `.spinner` |
| 2 | `id="s2j-inquiry-destination-types-form"` |
| 3 | `id="s2j-inquiry-destination-forms-form"` |

タブ2・3の **検索** は List Table 外の **別 GET フォーム** (`search_box`)。POST busy の対象外。

### 読み込み・送信中 {#読み込み送信中}

[decisions.md](./decisions.md) **D-017**。コア **`.spinner`** のみ (全画面オーバーレイは使わない)。

| 契機 | 動作 |
| --- | --- |
| **POST submit** | **`event.submitter` 以外** の `type="submit"` を即 `disabled` (submitter は次 tick で disable — POST に submit 名が載るため)。押下ボタン横 `.spinner.is-active`、`form[aria-busy="true"]`。**アクティブ** `nav-tab` 内 `.s2j-inquiry-destination-nav-tab-spinner.is-active` + ステータス行 (`tabSubmitMessage`)。Bulk **適用** も同様。タブ1は **保存** クリックで `#s2j-inquiry-destination-service-save-flag` を有効化 (submit 名欠落の保険) |
| **POST + タブ2Bulk 削除** | `typesTabScript()` が capture で `confirm`。キャンセル時 `preventDefault` → busy **しない** |
| **GET タブ切替** | `nav.s2j-inquiry-destination-nav-tabs` 内、非 `nav-tab-active` の `nav-tab` 左クリック。クリックタブ内 `.s2j-inquiry-destination-nav-tab-spinner.is-active`、全タブ `aria-disabled` + `pointer-events: none`、`wrap[aria-busy="true"]` |
| **ステータス行** | `#s2j-inquiry-destination-tab-loading-status` (`role="status"`, `aria-live="polite"`) — GET 切替時のみ表示。文言: `tabLoadingMessage` |

修飾キー付きクリック・中クリックは busy にしない。GET / POST とも **navigation / 通常 submit はやめない** (フィードバックのみ)。

## タブ1: 問い合わせ管理サービス

サイト **1組** の dispatch チャネルと (SaaS 時) 製品・接続を選びます。種別ごとのプロジェクト ID 等はタブ2 ([saas_connection_spec.md](./saas_connection_spec.md))。

### 項目

| 項目 | 説明 |
| --- | --- |
| チャネル | **メール (`wp_mail`)** または **問い合わせ管理 SaaS** |
| SaaS 製品 | `channel=saas` 時 **`backlog`** / **`asana`** → Service `kind` = `saas_{product}` (D-011) |
| 接続 | 製品ごと [saas_connection_spec.md](./saas_connection_spec.md) |

### タブ1実装 ([SettingsPage.php](../includes/Admin/SettingsPage.php))

| 項目 | 動作 |
| --- | --- |
| チャネル | `mail` / `saas` ラジオ。保存は `DispatchOptions::sanitizeFromPost` |
| SaaS 製品 | `SaasProductCatalog::labels()` 由来のラジオ (`backlog` / `asana`) |
| チャネル切替 (クライアント) | `channel` 変更で SaaS 製品の `disabled`、接続案内 ↔ 接続フォーム、製品別パネル、**接続を検証** ボタンの表示を `serviceTabScript()` が同期 (**保存前** も可) |
| 接続 DOM | Backlog / Asana 用パネルを **両方** 出力。表示中製品のみ有効 (非表示パネルの `endpoint` 等は `disabled` で POST 重複を防ぐ) |
| D-010 | `channel=mail` 時: 保存 POST で接続 option を更新しない。UI は初期表示 + JS で mail 側に同期 |
| 接続検証 | 下記 §接続の検証。`Support\SaasConnectionProbe` |

### `channel=mail` 時の SaaS 接続 (UI + 保存)

**dispatch チャネルが `mail` の間** SaaS 接続は編集・保存対象にしません (D-010)。

| 層 | 規則 |
| --- | --- |
| UI (初期) | 保存済み `channel=mail` なら SaaS 製品ラジオ `disabled`、接続は案内文のみ |
| UI (クライアント) | 画面上で `saas` を選べば JS が製品・接続欄を有効化 (保存前の入力・検証可) |
| 保存 | `SaasOptions::sanitizeFromPost( $post, $channel )` は `channel=mail` なら POST を無視 |

### `channel=mail` 時のメール認証案内

| 項目 | 規則 |
| --- | --- |
| 表示 | dispatch チャネルが **mail** の場合のみ (初期表示 + `serviceTabScript()` で `saas` 選択時は非表示) |
| 体裁 | `notice notice-info inline` (タブ1・チャネル fieldset 直後) |
| 未導入 | DKIM/SPF/DMARC が必要なら **Email Auth** (WordPress.org) の導入を検討する旨。`wp_mail` 全体 (本プラグインの問い合わせ含む) に効くこと |
| 導入済 | **設定 → Email Auth** へのリンク。本プラグインは Email Auth を必須依存にしない |

### `channel=saas` 時

| `saas_product` | 接続欄 (サイト1組) |
| --- | --- |
| `backlog` | スペース URL (`endpoint`)、API キー (`api_key`) |
| `asana` | PAT (`access_token`)。`endpoint` は任意 |

### サーバー側設定の優先

環境変数または `wp-config.php` 定数で SaaS が設定されている場合 ([saas_connection_spec.md](./saas_connection_spec.md)):

* 画面に **サーバー側で設定済み** と表示する。
* option で **上書きしない** (入力欄は read-only または非表示)。

### API キー / PAT (SaaS 有効化後)

* 保存後 **平文再表示しない**。「**ステータス: 設定済み** / **未設定**」のみ ([saas_connection_spec.md](./saas_connection_spec.md))。
* **保存 POST:** 秘密欄が空なら option の既存値を **維持** (`SaasOptions::sanitizeFromPost`)。初回保存は非空入力が必要。
* **ガイド (秘密欄):** **設定済み** (option またはサーバー側) のときのみ「空欄のまま = 現在の秘密を維持」。**未設定** のときは「検証・初回保存には入力が必要」と表示 (「空欄で維持」は出さない)。

### 接続の検証 (`channel=saas` 時)

UI ラベル例: **Verify connection** (i18n → 接続を検証)。

| 項目 | 規則 |
| --- | --- |
| 配置 | secondary **[接続を検証]** を primary **[サービス設定の保存]** の **直前** |
| POST | `s2j_inquiry_destination_test_saas_connection`。nonce は保存と同じ action `s2j_inquiry_destination_service`、フィールド `_wpnonce_s2j_service` |
| 保存 | **行わない** (`handleServiceConnectionTest` のみ) |
| 入力の解決 | `SaasOptions::resolveForConnectionTest()` — 下表。保存は行わない |
| 製品の未選択等 | 文言の製品名リストは `SaasProductCatalog::choicePhrase()` (ハードコードしない) |
| HTTP | `SaasConnectionProbe::test()` → `wp_remote_get`、タイムアウト15s |
| Backlog | `GET {endpoint}/api/v2/users/myself?apiKey=…` |
| Asana | `GET {endpoint or https://app.asana.com}/api/1.0/users/me` + `Authorization: Bearer` |
| 成功 | HTTP **200** のみ。`settings_errors` success |
| 失敗 | HTTP コードまたは到達不可。本文・秘密は **ログに出さない** |

**`resolveForConnectionTest` (Backlog / Asana 共通パターン):**

| フィールド | POST が空 | 結果 |
| --- | --- | --- |
| スペース URL / API ベース URL | — | POST を正規化。空なら option の `endpoint`。Backlog で URL も解決後空 → エラー (検証不可) |
| API キー / PAT | 空 | option の保存済み秘密を使用。**option にもサーバー側にも秘密がない** → エラー (「検証前に入力」— **未設定** 時の空欄は「維持」ではない) |
| POST に非空 | — | POST 値で検証 (保存はしない) |

サーバー側のみ設定済みのときは env/定数を使い、画面の秘密欄は read-only ([saas_connection_spec.md](./saas_connection_spec.md))。

### 保存

* nonce: action `s2j_inquiry_destination_service`、フィールド `_wpnonce_s2j_service` (§ POST)
* 更新 option: `s2j_inquiry_destination_dispatch` および (SaaS 接続フィールドのみ) `s2j_inquiry_destination_saas` ([data_dictionary.md](./data_dictionary.md))
* **他 option (types / forms) は、触らない**

## タブ2: 問い合わせタイプと宛先

行と **種別** (`inquiry_type` の登録名) が、1: 1です。

### 行の追加・削除 (Service ^0.2.0)

[decisions.md](./decisions.md) D-004。運営者が **行を追加・削除** できます。登録名 (slug) は `^[a-z][a-z0-9_]{0,63}$` です (Service v0.2が検証)。option 未作成時のシードは **sales / recruit** の2行です。

**Add new inquiry type** ボタンは **Save inquiry types** (保存) ボタンの **左隣** に置きます。クリックで tbody に空行を追加します (行マークアップは `WP_List_Table` の primary 列 `slug` と同型)。確定は **保存 POST1回** とします。実装は `typesTabScript()` (§タブ別管理 CSS / JS)。

**Bulk 削除** は List Table の **削除** + **適用** (保存ボタンは POST に含まれない)。サーバーは § POST の Bulk 削除フロー。クライアントは適用前に `confirm` (マッピング参照で拒否された場合はサーバー側エラー通知)。

**狭幅 (≤782px, Mail):** `assets/admin/types-tab.css` で **Z 字折り返し** (チェック + slug → 表示名 → `mail_to`)。各 `td`/`th` は `data-colname` + `::before` で列見出しを出す。

**SaaS Backlog 行レイアウト** (`types-tab.css`, wrap `--saas` + `--backlog`、ビューポート幅):

| 幅 | レイアウト |
| --- | --- |
| **≥1489px** | 6列1行 (通常の表) |
| **785–1488px** | 1行目: チェック + スラッグ + 表示名 + プロジェクト ID。2–3行目: 課題種別 / 優先度 ID を4列目下 |
| **558–784px** | 1行目: チェック + スラッグ + 表示名。2–4行目: プロジェクト / 課題種別 / 優先度 ID を4列目下 |
| **≤557px** | チェック + スラッグのあと、各フィールドを `data-colname` 付きで縦積み (タブ3同型) |

折り返し時は表頭の ID 列を一部非表示し、積んだセルに `data-colname` を付ける。表は `s2j-inquiry-types-list-table-wrap` で包み、`channel=saas` かつ Backlog 時は [SettingsPage.php](../includes/Admin/SettingsPage.php) が **`--saas`** と **`--backlog`** を付与 (CSS のメディアクエリーは **ビューポート幅**)。

**SaaS Asana** (`--saas` + **`--asana`**): 列は slug / 表示名 / プロジェクト ID。**≤782px** は Mail と同型の Z 字 (表示名・プロジェクト ID を非表示にしない)。**≥783px** は通常4列表 + プロジェクト ID 入力 `max-width: 11em`。

**List Table 実装メモ:** primary 列は `slug`。`fixed` クラスは付けない (コアデフォルト幅25% を避ける)。行描画は `single_row_columns` (子クラスで `column_cb` を二重定義しない)。

### 初期行 (option 未作成時のシード)

登録名の許容値は [Service data_dictionary — inquiry_type](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md#inquiry_type) が正本です。

| 登録名 | 表示名 (`label`) |
| --- | --- |
| `sales` | 営業 |
| `recruit` | 採用 |

### 列

**種類 (`kind`) 列は置きません。** サイトの dispatch チャネル (タブ1) と Service `kind` が決めます。Mail チャネル時は宛先レコードの `kind` は `mail` です ([data_dictionary.md](./data_dictionary.md))。

「未設定種別」「共通アドレスに退避」は作りません。

### 列 (チャネル依存)

**`kind` 列は置きません。** タブ1の `channel` + `saas_product` が Service `destination.kind` を決めます ([data_dictionary.md](./data_dictionary.md))。

#### `channel=mail` (現行列)

| 列 | 内容 |
| --- | --- |
| チェック | Bulk 用 |
| Type (slug) | 登録名。`mail_to` 有効0件で行内警告 |
| Display name | `label` |
| Mail to | `mail_to` (D-003) |

#### `channel=saas` (Service ^0.3、実装済)

| 列 | 内容 |
| --- | --- |
| チェック | Bulk 用 |
| Type (slug) | 登録名。必須 SaaS フィールド欠落で行内警告 |
| Display name | `label` |
| Project ID | `saas_project_id` (Backlog projectId / Asana プロジェクト GID) |
| Backlog のみ | `issue_type_id`, `priority_id` (課題種別 / 優先度) |

SaaS 時 **Mail to 列は出さない** (または非表示。保存は空配列)。

**タブ1 `saas_product` と種別行 (D-007):** 問い合わせタイプは option **`s2j_inquiry_destination_types` のみ** に保存される。タブ1で **Asana ↔ Backlog** (または Mail ↔ SaaS) に変えて **サービス設定を保存** しても、種別行の **追加・削除・リセットは行わない** — 既存 slug / `label` / `saas_project_id` 等は option に残る。変わるのは (1) タブ2の **列表示** (`TypesListTable` が dispatch の `channel` / `saas_product` を参照)、(2) 送信時の Service **`destination.kind`** ([data_dictionary.md](./data_dictionary.md) TypeRow)。

### Views / 検索 / Bulk

| 種別 | 動作 |
| --- | --- |
| Views (mail) | **すべて** / **宛先未設定** (`mail_to` 有効0件) |
| Views (saas) | **すべて** / **プロジェクト未設定** (`saas_project_id` 空) |
| 検索 | 登録名・表示名の部分一致 |
| Bulk actions | **削除** (マッピング参照中は拒否 — 既存 D-004)。**適用** 前にブラウザ `confirm` で意思確認 (破壊的操作) |

### 常時表示の案内 (表の上)

* **mail:** メーリングリスト / 共有受信箱の1アドレス推奨。CC / BCC なし。
* **saas:** タブ1の接続と種別ごとのプロジェクト ID をセットすること (Backlog は種別ごと課題種別 / 優先度も)。

### 空宛先の警告

* **mail:** `mail_to` 有効0件 → slug 列警告 + View「宛先未設定」。送信時 `destination_empty`。
* **saas:** `saas_project_id` 空等 → 行内警告 + View「プロジェクト未設定」。送信時 Service deficiency (例: `destination_saas_project_id_empty`)。

### 保存

* 保存 POST: ボタン名 `s2j_inquiry_destination_save_types` あり → nonce action `s2j_inquiry_destination_types`、フィールド `_wpnonce_s2j_types`
* Bulk **削除** POST: 上記ボタン名 **なし** → `bulk-inquiry_types` (§ POST)。`settings_errors` 成功 code `types_deleted`
* 保存成功: code `types_saved` —「問い合わせタイプが保存されました」相当
* 削除成功: code `types_deleted` —「問い合わせタイプを削除しました」相当 (保存メッセージと混同しない)
* 検証失敗: code `types_error`
* option: `s2j_inquiry_destination_types` のみ更新
* サニタイズ: D-003 / D-004

## タブ3: Snow Monkey Forms のマッピング

1行 = SMF フォーム1件です (`snow-monkey-forms` 投稿)。

| 列 | 内容 |
| --- | --- |
| チェック | Bulk 用 |
| フォーム | 投稿タイトル + ID (ソート可) |
| 種別 | `TypeRow` の登録名 (`inquiry_type`) |
| フィールドマッピング | 氏名 / メール / 電話 / 本文。各1つの SMF コントロール `name` |

**List Table 実装メモ:** 表クラス `s2j-inquiry-forms-list-table`。表示は `s2j-inquiry-forms-list-table-wrap` でラップ。

**フィールド名列 (`column-fields`):** 各マッピングは `p > label > input.regular-text`。`forms-tab.css` で `width: 100%` / `max-width: 100%` / `box-sizing: border-box` とし、管理画面幅を狭めても **セル右端からはみ出さない** (コア `25em` をオーバーライト)。

**狭幅 (≤782px):** タブ2 ([types-tab.css](../assets/admin/types-tab.css)) と同様、行を grid 化し **`data-colname` を `::before` で全幅表示** → その下に `select` / 入力群。コア List Table の省略ラベル (例: `フィールド名 (SMF コントロ…`) に依存しない。

* 1フォーム→一種別。同一種別に複数フォームを対応付けてよい。
* **マッピングなし** (option にキーがない、または Bulk でマッピング解除) のフォームは、本プラグインが触れない (SMF デフォルト管理者宛)。
* 行のデータ源: `post_type=snow-monkey-forms` かつ **`post_status=publish` のみ**。

### Views / 検索 / Bulk

| 種別 | 動作 |
| --- | --- |
| Views | **すべて** / **マッピング済み** / **未マッピング** |
| 検索 | タイトルまたは投稿 ID |
| Bulk actions | **種別を変更** (一括で `inquiry_type` 選択)、**マッピングを解除** (option から当該 form ID を削除) |

### フィールドマッピング

マッピングに含めないコントロールは [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) のとおり **message 末尾** に追記します。

| 欄 | 必須 | 用途 |
| --- | --- | --- |
| 氏名 | はい | ペイロード `name` |
| メール | はい | ペイロード `email`、Reply-To |
| 電話 | いいえ | 空 name 可 |
| 本文 | はい | ペイロード `message` |

### 保存

マッピングを保存する場合、必須フィールド name が空の行は **マッピング済みとみなしません** (Views「マッピング済み」と整合 — 実装は [FormOptions](../includes/Options/FormOptions.php) の保存規則に従う)。**HTML5 `required` は使わない** (一覧に未マッピング行も載るため、ブラウザ検証で保存全体が止まる)。

* nonce: action `s2j_inquiry_destination_forms`、フィールド `_wpnonce_s2j_forms`。Bulk **種別変更** / **マッピング解除** のみ適用時は `bulk-smf_forms` (§ POST)
* option: `s2j_inquiry_destination_forms` のみ更新
* キー: フォーム投稿 ID (整数正規化)
* **UI:** 種別列・ Bulk「種別を変更」は **`<select>`** — 描画時に `TypeOptions::get()` から生成するため、**画面上では存在しない登録名を選べない** (タブ表示のたびに types を再取得 — D-007)。
* **サーバー:** [FormOptions](../includes/Options/FormOptions.php) は POST の `inquiry_type` が types に **無い場合は保存エラー** (改ざん POST、stale 画面等)。**手動 UI: N/A** — Unit: [FormOptionsTest.php](../tests/Unit/Options/FormOptionsTest.php) ([test-results.md](./test-results.md) #10)。

## サイト移行

* DB ごと移行: 投稿 ID と option の対応はそのまま。
* フォームのみ移行 / ID 衝突: タブ3で付け直す。

## i18n

画面文言は、テキストドメイン `s2j-inquiry-destination` の国際化関数を使います。deficiency コード文字列は、訪問者向けに出しません ([dispatch_execution_spec.md](./dispatch_execution_spec.md))。

## 関連

* データ: [data_dictionary.md](./data_dictionary.md)
* SaaS 接続: [saas_connection_spec.md](./saas_connection_spec.md)
* 実装状況: [status.md](./status.md)
