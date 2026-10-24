# S2J Inquiry Destination - SaaS 接続

## 設計意図

問い合わせの **起票先** を、サイト全体で **メール** または **問い合わせ管理 SaaS (Backlog / Asana)** のいずれか1系統に固定します。HTTP の規則・材料・結果写像は [Inquiry Destination Service ^0.3](https://github.com/stein2nd/s2j-inquiry-destination-service) が正本です。本プラグインは **接続設定の保存**、**宛先レコード組立**、**`wp_remote_request` 実行** を担います。

空 stub の REST 送信は置きません ([Service dispatch_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/dispatch_spec.md))。

## 用語 (本プラグイン docs 内)

| 用語 | 保存場所 | 値の例 | Service への対応 |
| --- | --- | --- | --- |
| **dispatch チャネル** | `DispatchSettings.channel` | `mail` / `saas` | Mail 時 `kind=mail`。SaaS 時は下記 `saas_product` で `kind` を決める |
| **SaaS 製品 ID** | `DispatchSettings.saas_product` | `backlog` / `asana` | **`destination.kind` = `saas_{saas_product}`** → `saas_backlog` / `saas_asana` ([decisions.md](./decisions.md) D-011) |
| **サイト接続** | option `s2j_inquiry_destination_saas` | 製品ごとの秘密、URL | 実行時に `destination.credentials` / `saas_endpoint` に **マージ** (平文は option のみ) |
| **種別ごと設定** | `TypeRow` (タブ2) | `mail_to` または `saas_project_id` 等 | [Service destination_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/destination_spec.md) |

`channel=mail` のとき `saas_product` は空文字に正規化して保存します (未使用)。

## Service v0.3で確定した製品

| `saas_product` | `destination.kind` | 起票 | Service 仕様 |
| --- | --- | --- | --- |
| `backlog` | `saas_backlog` | Backlog 課題 `POST /api/v2/issues` | [saas_backlog_adapter_spec (Service)](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/saas_backlog_adapter_spec.md) |
| `asana` | `saas_asana` | Asana タスク `POST /api/1.0/tasks` | [saas_asana_adapter_spec (Service)](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/saas_asana_adapter_spec.md) |

## 実装段階 (コード vs 仕様)

| 段階 | Service | プラグイン Runner | タブ1 UI | 正本 |
| --- | --- | --- | --- | --- |
| **0.0.x (SaaS 実装済)** | **^0.3.0** | Mail **または** HTTP ([dispatch_execution_spec.md](./dispatch_execution_spec.md)) | `channel` / `saas_product` 保存、製品別接続、**接続検証**、チャネル切替 JS | [status.md](./status.md)、本 doc、[admin_ui_spec.md](./admin_ui_spec.md) |

## サイト全体のチャネル

dispatch の正本は option `s2j_inquiry_destination_dispatch` です ([data_dictionary.md](./data_dictionary.md))。

| `channel` | Runner 実行 |
| --- | --- |
| `mail` | `wp_mail` + `map_dispatch_result( 'mail', … )` |
| `saas` | `wp_remote_request` + `map_dispatch_result( $kind, SaasExecutionOutcome, … )`。`$kind` は `saas_backlog` または `saas_asana` |

## サイト接続 (タブ1· option / サーバー)

[Service destination_spec — プラグイン設定の想定](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/destination_spec.md#プラグイン設定の想定) に追随します。

### Backlog (`saas_product=backlog`)

Backlog の **課題種別 ID / 優先度 ID** はサイト共通にせず、**種別行 (タブ2)** に置きます → `credentials.issue_type_id` / `credentials.priority_id`。

| 保存 (サイト1組) | 実行時の載せ方 |
| --- | --- |
| `endpoint` — スペース URL (例: `https://example.backlog.com`) | `destination.saas_endpoint` |
| `api_key` — Backlog API キー | `destination.credentials.api_key` |

### Asana (`saas_product=asana`)

**プロジェクト GID** (`saas_project_id`) は **種別ごと** (タブ2)。v0.3では **1プロジェクトのみ** ([Service destination_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/destination_spec.md))。

| 保存 (サイト1組) | 実行時の載せ方 |
| --- | --- |
| `access_token` — PAT | `destination.credentials.access_token` |
| `endpoint` — 省略可 (空 → Service デフォルト `https://app.asana.com`) | `destination.saas_endpoint` |

### 読み取り優先順位 (秘密)

サーバー側がある場合、タブ1は **使用中** と示し option で上書きしません ([admin_ui_spec.md](./admin_ui_spec.md) タブ1)。

| 優先 | Backlog | Asana |
| --- | --- | --- |
| 1 | 定数 / 環境変数 (下表) | 同左 |
| 2 | option `s2j_inquiry_destination_saas` | 同左 |

| 定数 (例) | 用途 |
| --- | --- |
| `S2J_INQUIRY_DESTINATION_BACKLOG_API_KEY` | Backlog API キー |
| `S2J_INQUIRY_DESTINATION_BACKLOG_SPACE_URL` | スペース URL (`endpoint`) |
| `S2J_INQUIRY_DESTINATION_ASANA_ACCESS_TOKEN` | Asana PAT |

既存の `S2J_INQUIRY_DESTINATION_SAAS_API_KEY` は **Backlog API キー** の別名として読んでもよい (実装でどちらか一方を正とし、仕様と README に明記する)。

### 管理 UI (タブ1)

[admin_ui_spec.md](./admin_ui_spec.md) §タブ1が画面正本です。

| 項目 | 規則 |
| --- | --- |
| チャネル | メール vs 問い合わせ管理 SaaS |
| SaaS 製品 | `SaasProductCatalog` のラジオ。`channel=mail` 保存時は製品ラジオ初期 `disabled` + JS で切替 |
| 秘密 | 保存後 **平文再表示しない**。「設定済み / 未設定」のみ |
| `channel=mail` | 保存 POST 無視 (D-010)。UI は案内文 + JS 同期 ([admin_ui_spec.md](./admin_ui_spec.md) §タブ1) |
| `channel=saas` | 選択中製品パネルのみ編集可 (サーバー未設定時) |
| 接続検証 | [admin_ui_spec.md](./admin_ui_spec.md) §接続の検証 — `SaasConnectionProbe` / `resolveForConnectionTest` |

### 接続検証 (管理画面、保存しない)

運営者が秘密・URL を保存する前に疎通を確認する。**Runner 起票** とは別経路 (認証用 GET のみ)。

| 項目 | 実装 |
| --- | --- |
| クラス | [SaasConnectionProbe.php](../includes/Support/SaasConnectionProbe.php) |
| 入力解決 | [SaasOptions::resolveForConnectionTest()](../includes/Options/SaasOptions.php) |
| 製品名 (エラー文) | [SaasProductCatalog::choicePhrase()](../includes/Support/SaasProductCatalog.php) |
| Backlog | `GET /api/v2/users/myself` + クエリ `apiKey` |
| Asana | `GET /api/1.0/users/me` + Bearer PAT。`endpoint` 空は `https://app.asana.com` |
| 成功条件 | HTTP 200。それ以外は HTTP コードまたは到達不可メッセージ (レスポンス body は記録しない) |

## 種別ごと (タブ2)

Runner は option と TypeRow から [Service destination_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/destination_spec.md) の **全キー** を欠落なく組み立てます (`mail_to` は SaaS 時 `[]` 可)。

| `channel` | `saas_product` | TypeRow で持つ主なフィールド |
| --- | --- | --- |
| `mail` | — | `mail_to` |
| `saas` | `backlog` | `saas_project_id`, `issue_type_id`, `priority_id` |
| `saas` | `asana` | `saas_project_id` |

## 検証・失敗 (Service 正本)

不足コード・`failure_code` は Service のみ正本です。プラグイン docs には **代表例** のみ:

| 種別 | 例 |
| --- | --- |
| deficiency | `destination_saas_endpoint_empty`, `destination_saas_project_id_empty`, `destination_credentials_api_key_empty`, … |
| failure_code | `saas_http_error` ( **`http_status=0` 含む** ) , `saas_body_invalid` (2xx だが **201 + ticket id** 不足) |

訪問者画面に deficiency / failure_code 文字列は出しません ([dispatch_execution_spec.md](./dispatch_execution_spec.md))。

## Composer (SaaS 対応時)

```bash
composer require s2j/inquiry-destination-service:^0.3.0
```

手順の正本: [Service usage_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/interfaces/usage_spec.md)。

## アンインストール

`uninstall.php` で削除:

* `s2j_inquiry_destination_dispatch`
* `s2j_inquiry_destination_saas`

環境変数・定数は触りません ([plugin_spec.md](./plugin_spec.md))。

## 関連

* 送信 Runner: [dispatch_execution_spec.md](./dispatch_execution_spec.md)
* 管理 UI: [admin_ui_spec.md](./admin_ui_spec.md)
* データ: [data_dictionary.md](./data_dictionary.md)
