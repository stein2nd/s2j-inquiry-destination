# S2J Inquiry Destination - 仕様書の起点

本プロジェクトの仕様は、下記のドキュメントに分散して定義します。
各ドキュメントへの導線のみを提供し、詳細は個別ファイルに委譲します。

統合見取り図は [plugin_spec.md](./plugin_spec.md) です。構成は [S2J Webinar の docs/](https://github.com/stein2nd/s2j-webinar/blob/main/docs/specs.md) と [S2J Webinar Survey の docs/](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/specs.md) に倣い、本プラグインに必要な層だけを置いています。

**いまの置き場:** 確定した正本は **`docs/`** (本ディレクトリ) です。改訂案の起草は [`../docs_mod/`](../docs_mod/README.md) ([governance/documentation_governance.md](./governance/documentation_governance.md))。

## 共通仕様

* [SPECS.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md)
* [PHP_CODING_STANDARDS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/PHP_CODING_STANDARDS.md)

## 計算の正本 (Composer ライブラリ)

統一ペイロードの検証、Mail 材料、結果写像の規則は、本プラグインには置きません。

* 索引: [s2j-inquiry-destination-service / docs/specs.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/specs.md)
* Transport 境界 (v0.4): [transport_boundary_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md)
* 統合見取り図: [service_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/service_spec.md)
* 公開 API: [php_api_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/interfaces/php_api_spec.md)
* 使用方法 (プラグイン向け): [usage_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/interfaces/usage_spec.md)
* 用語 (`inquiry_type`、`kind`、`correlation_id`): [data_dictionary.md (Service)](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md)

プラグイン `composer.json`: **`s2j/inquiry-destination-service` ^0.4.0** (v0.2 spec)。

## 読み方ガイド

### 推奨読み順 (初読)

1. **[concept.md](./concept.md)** — なぜ存在するか
2. **[overview.md](./overview.md)** — 責務と非対応
3. **[principles.md](./principles.md)** — SoT、境界、Service 呼び出し
4. **[architecture.md](./architecture.md)** — レイヤとフォルダー
5. **[data_dictionary.md](./data_dictionary.md)** — option、フック名
6. **[admin_ui_spec.md](./admin_ui_spec.md)** — 設定画面
7. **[snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)** — SMF 接続とフィールド写像
8. **[dispatch_execution_spec.md](./dispatch_execution_spec.md)** — 送信、`wp_mail`、失敗と自動返信
9. **[mail_deliverability_spec.md](./mail_deliverability_spec.md)** — Mail Transport、`Email Auth` (v0.2)
10. **[logging_spec.md](./logging_spec.md)** — `error_log` と相関 ID

### 役割別

#### 実装者 (本プラグイン)

1. [architecture.md](./architecture.md)
2. [data_dictionary.md](./data_dictionary.md)
3. [admin_ui_spec.md](./admin_ui_spec.md)
4. [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)
5. [dispatch_execution_spec.md](./dispatch_execution_spec.md)
6. [mail_deliverability_spec.md](./mail_deliverability_spec.md)
7. [logging_spec.md](./logging_spec.md)
8. [decisions.md](./decisions.md) — 実装固定 (D-001– D-015)
9. [status.md](./status.md) / [test-results.md](./test-results.md)
10. [governance/documentation_governance.md](./governance/documentation_governance.md)

#### サービス側の実装者

1. [Service docs/specs.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/specs.md)
2. 本プラグインの境界だけ [plugin_spec.md](./plugin_spec.md) §サービスとの境界

## ドキュメント一覧

### Why

| ドキュメント | 内容 |
| --- | --- |
| [概要](./overview.md) | 基本情報、責務、非対応 |
| [コンセプト](./concept.md) | 背景、ユースケース、処理フロー |
| [アーキテクチャー](./architecture.md) | レイヤ、フォルダー、依存 |
| [設計原則](./principles.md) | FOP、境界、SoT |
| [統合見取り図](./plugin_spec.md) | 要約。細部は分割仕様 |

### What (データと画面)

| ドキュメント | 内容 |
| --- | --- |
| [データ辞書](./data_dictionary.md) | option キー、SMF フック、相関 ID |
| [管理 UI](./admin_ui_spec.md) | 設定 > S2J Inquiry Destination (3タブ、List Table) |
| [Snow Monkey Forms 接続](./snow_monkey_forms_spec.md) | フォーム対応、skip、未対応コントロール |
| [SaaS 接続](./saas_connection_spec.md) | Backlog / Asana、タブ1/2· Runner HTTP (Service ^0.4) |

### How (実行)

| ドキュメント | 内容 |
| --- | --- |
| [送信実行](./dispatch_execution_spec.md) | Service 呼び出し、`wp_mail`、成功条件、自動返信 |
| [Mail 配送・認証](./mail_deliverability_spec.md) | Transport、`Email Auth` コンパニオン (v0.2) |
| [ログ](./logging_spec.md) | PII (個人を識別できる情報) 境界、`error_log` |

### 運用

| ドキュメント | 内容 |
| --- | --- |
| [実装状況](./status.md) | 機能単位の進捗 |
| [テスト戦略と結果](./test-results.md) | 段階1–3 |
| [決定事項](./decisions.md) | Runner、UI、SMF、Mail (D-013– D-015) |
| [ドキュメンテーション・ガバナンス](./governance/documentation_governance.md) | SoT、用語、lint、`docs_mod` 起草 → `docs` 正本 |
| [archive 索引](./archive/README.md) | 完了イニシアチブ (v0.2 Mail 等) |

## Source of Truth

矛盾時は **Service の core/contracts** を正とし、プラグイン docs を追随させます。画面専用の決定 (メニュー名、option キー) は本プラグイン docs が正です。

| 対象 | 正本 |
| --- | --- |
| ペイロード・宛先・ Mail 材料 (4キー)・結果写像 | Inquiry Destination **Service** の `docs/core/` |
| Mail Transport (DKIM 等) | Service [transport_boundary_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md) + 本 [mail_deliverability_spec.md](./mail_deliverability_spec.md) |
| 公開 PHP API | Service の `docs/interfaces/php_api_spec.md` |
| option 構造、SMF フック、Runner ヘッダー、画面文言 | 本 `docs/` |
| ユーザー向け最短手順 | ルート `README.md` |

## 用語 (プラグイン docs 内)

| 用語 | 意味 |
| --- | --- |
| **種別** | `inquiry_type` (登録名 `sales` / `recruit` 等)。表示名は option の `label` |
| **統一ペイロード** | Service に渡す連想配列 ([Service payload_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/payload_spec.md)) |
| **宛先レコード** | 1回の送信に渡す `DestinationRecord` ([Service destination_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/destination_spec.md)) |
| **材料 (material)** | `MailMaterial` または `HttpRequestMaterial` ([Service dispatch_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/dispatch_spec.md)) |
| **dispatch チャネル** | タブ1 `channel` (`mail` / `saas`)。SaaS 時の製品 ID は `saas_product` → Service `kind` = `saas_{product}` |
| **Mail チャネル** | `channel=mail`。Runner が `wp_mail` を呼ぶ |
| **Transport 層** | `wp_mail` 以降 (SMTP、DKIM 等)。Service 非目標 |
| **`Email Auth`** | 推奨コンパニオン (WordPress.org)。必須依存ではない (D-014) |
| **SaaS 製品 ID** | `backlog` / `asana` (プラグイン)。Service `kind` は `saas_backlog` / `saas_asana` |
| **相関 ID** | 1送信試行の `correlation_id`。ログと画面に出す非 PII (個人を識別できる情報) 識別子 |

## 補足

* 各仕様は「責務・非責務」で境界を定義する。
* 新規仕様は、必ず上記レイヤーに分類する。
