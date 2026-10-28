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
| タブ切替 | `nav-tab-wrapper` + クエリー `tab=` (`service` / `types` / `forms`)。保存 POST 後も **同一タブ** にリダイレクト |
| 権限 | `manage_options` のみ |
| SMF 無効時 | 画面は開く。送信は動かない。**Snow Monkey Forms を有効化してください** 旨の `Notice` を出す ([snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)) |

### タブ構成

タブ2の見出しは **Mail に限定しない** 名称とします (SaaS 有効化後、種別行に SaaS 向け項目が増えてもタブ名を維持できる)。

| タブ ( `tab=` ) | 見出し (例) | 内容 |
| --- | --- | --- |
| `service` | 問い合わせ管理サービス | dispatch チャネル (`mail` / `saas`)、SaaS 製品 (`backlog` / `asana`)、サイト接続 |
| `types` | 問い合わせタイプと宛先 | 種別行。チャネルに応じ `mail_to` または SaaS プロジェクト等 |
| `forms` | Snow Monkey Forms のマッピング | 公開 SMF フォームと種別・フィールド写像 |

### 一覧表の UX 基準 (メディアライブラリ)

タブ2・タブ3の表は、WordPress **メディアライブラリ** 一覧に倣います ([decisions.md](./decisions.md) D-008)。実装は `TypesListTable` / `FormsListTable` (`includes/Admin/`)。

| 要素 | 要件 |
| --- | --- |
| 表本体 | `WP_List_Table` (チェック列、列見出し、行) |
| `tablenav` 上段 | **Bulk actions** ドロップダウン + 適用、**Views** (`subsubsub`)、必要なら月/件数フィルターに相当する絞り込み |
| `tablenav` 下段 | ページネーション (フォーム数が多いタブ3で必須。タブ2は行数に応じて) |
| 検索 | 表上部の検索ボックス (GET `s`)。タブ2: slug / 表示名。タブ3: フォームタイトル / 投稿 ID |
| ソート | クリック可能な列見出し (昇降)。タブ2: 登録名、表示名。タブ3: タイトル、投稿 ID、種別、投稿日 |
| 保存ボタン | 各タブ **1HTML フォーム = 1nonce**。Bulk 適用も **同一 POST** 内で処理 |

### タブ別管理 CSS (`assets/admin/`)

コアの `.regular-text { width: 25em }` 等で表セルからはみ出さないよう、タブ2・タブ3は **当該タブ表示時のみ** CSS を enqueue します (`SettingsPage::enqueueAssets`)。

| タブ | ファイル | スコープ (例) | 目的 |
| --- | --- | --- | --- |
| `types` | `types-tab.css` | `.s2j-inquiry-types-list-table` + wrap | 狭幅 Z 字、slug / 表示名 / `mail_to` の整列 |
| `forms` | `forms-tab.css` | `.s2j-inquiry-forms-list-table` + wrap | フィールド名列 (`column-fields`) の `input.regular-text` を列幅100% |
| `service` | (インライン JS) | `.s2j-inquiry-destination-tab-service` | `SettingsPage::serviceTabScript()` — チャネル切替で SaaS 製品・接続欄・接続検証を同期 |

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

### API キー (SaaS 有効化後)

* 保存後 **平文再表示しない**。「設定済み / 未設定」のみ。
* 変更時は、新キーを入力して上書き保存。

### 接続の検証 (`channel=saas` 時)

UI ラベル例: **Verify connection** (i18n → 接続を検証)。

| 項目 | 規則 |
| --- | --- |
| 配置 | secondary **[接続を検証]** を primary **[サービス設定の保存]** の **直前** |
| POST | `s2j_inquiry_destination_test_saas_connection`。nonce は保存と同じ `s2j_inquiry_destination_service` |
| 保存 | **行わない** (`handleServiceConnectionTest` のみ) |
| 入力の解決 | `SaasOptions::resolveForConnectionTest()` — POST の秘密、URL、空 secret は option、サーバー設定時は env/定数 |
| 製品の未選択等 | 文言の製品名リストは `SaasProductCatalog::choicePhrase()` (ハードコードしない) |
| HTTP | `SaasConnectionProbe::test()` → `wp_remote_get`、タイムアウト15s |
| Backlog | `GET {endpoint}/api/v2/users/myself?apiKey=…` |
| Asana | `GET {endpoint or https://app.asana.com}/api/1.0/users/me` + `Authorization: Bearer` |
| 成功 | HTTP **200** のみ。`settings_errors` success |
| 失敗 | HTTP コードまたは到達不可。本文・秘密は **ログに出さない** |

### 保存

* nonce: `s2j_inquiry_destination_service`
* 更新 option: `s2j_inquiry_destination_dispatch` および (SaaS 接続フィールドのみ) `s2j_inquiry_destination_saas` ([data_dictionary.md](./data_dictionary.md))
* **他 option (types / forms) は、触らない**

## タブ2: 問い合わせタイプと宛先

行と **種別** (`inquiry_type` の登録名) が、1:1です。

### 行の追加・削除 (Service ^0.2.0)

[decisions.md](./decisions.md) D-004。運営者が **行を追加・削除** できます。登録名 (slug) は `^[a-z][a-z0-9_]{0,63}$` です (Service v0.2が検証)。option 未作成時のシードは **sales / recruit** の2行です。

**Add new inquiry type** ボタンは **Save inquiry types** (保存) ボタンの **左隣** に置きます。クリックで tbody に空行を追加します (行マークアップは `WP_List_Table` の primary 列 `slug` と同型)。確定は **保存 POST1回** とします。

**狭幅 (≤782px):** `assets/admin/types-tab.css` で **Z 字折り返し** (チェック + slug → 表示名 → 宛先列)。コア List Table の `display:none` による副列非表示は **上書きしない**。各 `td`/`th` は `data-colname` + `::before` で列見出しを出す。

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

### Views / 検索 / Bulk

| 種別 | 動作 |
| --- | --- |
| Views (mail) | **すべて** / **宛先未設定** (`mail_to` 有効0件) |
| Views (saas) | **すべて** / **プロジェクト未設定** (`saas_project_id` 空) |
| 検索 | 登録名・表示名の部分一致 |
| Bulk actions | **削除** (写像参照中は拒否 — 既存 D-004) |

### 常時表示の案内 (表の上)

* **mail:** メーリングリスト / 共有受信箱の1アドレス推奨。CC / BCC なし。
* **saas:** タブ1の接続と種別ごとのプロジェクト ID をセットすること (Backlog は種別ごと課題種別 / 優先度も)。

### 空宛先の警告

* **mail:** `mail_to` 有効0件 → slug 列警告 + View「宛先未設定」。送信時 `destination_empty`。
* **saas:** `saas_project_id` 空等 → 行内警告 + View「プロジェクト未設定」。送信時 Service deficiency (例: `destination_saas_project_id_empty`)。

### 保存

* nonce: `s2j_inquiry_destination_types`
* option: `s2j_inquiry_destination_types` のみ更新
* サニタイズ: D-003 / D-004

## タブ3: Snow Monkey Forms のマッピング

1行 = SMF フォーム1件です (`snow-monkey-forms` 投稿)。

| 列 | 内容 |
| --- | --- |
| チェック | Bulk 用 |
| フォーム | 投稿タイトル + ID (ソート可) |
| 種別 | `TypeRow` の登録名 (`inquiry_type`) |
| フィールド写像 | 氏名 / メール / 電話 / 本文。各1つの SMF コントロール `name` |

**List Table 実装メモ:** 表クラス `s2j-inquiry-forms-list-table`。表示は `s2j-inquiry-forms-list-table-wrap` でラップ。

**フィールド名列 (`column-fields`):** 各写像は `p > label > input.regular-text`。`forms-tab.css` で `width: 100%` / `max-width: 100%` / `box-sizing: border-box` とし、管理画面幅を狭めても **セル右端からはみ出さない** (コア `25em` を上書き)。

* 1フォーム→一種別。同一種別に複数フォームを対応付けてよい。
* **写像なし** (option にキーがない、または Bulk で写像解除) のフォームは、本プラグインが触れない (SMF デフォルト管理者宛)。
* 行のデータ源: `post_type=snow-monkey-forms` かつ **`post_status=publish` のみ**。

### Views / 検索 / Bulk

| 種別 | 動作 |
| --- | --- |
| Views | **すべて** / **写像済み** / **未写像** |
| 検索 | タイトルまたは投稿 ID |
| Bulk actions | **種別を変更** (一括で `inquiry_type` 選択)、**写像を解除** (option から当該 form ID を削除) |

### フィールド写像

写像に含めないコントロールは [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) のとおり **message 末尾** に追記します。

| 欄 | 必須 | 用途 |
| --- | --- | --- |
| 氏名 | はい | ペイロード `name` |
| メール | はい | ペイロード `email`、Reply-To |
| 電話 | いいえ | 空 name 可 |
| 本文 | はい | ペイロード `message` |

### 保存

写像を保存する場合、必須フィールド name が空の行は **写像済みとみなしません** (Views「写像済み」と整合 — 実装は [FormOptions](../includes/Options/FormOptions.php) の保存規則に従う)。

* nonce: `s2j_inquiry_destination_forms`
* option: `s2j_inquiry_destination_forms` のみ更新
* キー: フォーム投稿 ID (整数正規化)
* `inquiry_type` は、`s2j_inquiry_destination_types` に **存在する登録名のみ** 許可 (不存在は保存エラー)

## サイト移行

* DB ごと移行: 投稿 ID と option の対応はそのまま。
* フォームのみ移行 / ID 衝突: タブ3で付け直す。

## i18n

画面文言は、テキストドメイン `s2j-inquiry-destination` の国際化関数を使います。deficiency コード文字列は、訪問者向けに出しません ([dispatch_execution_spec.md](./dispatch_execution_spec.md))。

## 関連

* データ: [data_dictionary.md](./data_dictionary.md)
* SaaS 接続: [saas_connection_spec.md](./saas_connection_spec.md)
* 実装状況: [status.md](./status.md)
