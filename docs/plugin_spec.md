# S2J Inquiry Destination - プラグイン仕様 (統合見取り図)

記録の起点は、2026-10-04。

確定した正本として `docs/plugin_spec.md` に置きます。索引は [specs.md](./specs.md) です。改訂案は [`../docs_mod/`](../docs_mod/README.md) で起草します。

計算の正本は [S2J Inquiry Destination Service](https://github.com/stein2nd/s2j-inquiry-destination-service) の `docs/` です。

## 概要

本プラグインは、問い合わせフォーム (Snow Monkey Forms) の **管理者宛の送信** を差し替えます。統一ペイロードへの翻訳、種別ごとの宛先、`wp_mail` (将来 HTTP) の実行を担います。

フォーム項目・確認画面・完了文・自動返信の文面は **Snow Monkey Forms** です。本プラグインはフォームを作りません。

kis-core は本体にしません。KIS のページ構成は **kis-inquiry** が担い、本プラグインを有効化して使います。

| 項目 | 値 |
| --- | --- |
| スラッグ | `s2j-inquiry-destination` |
| テキストドメイン | `s2j-inquiry-destination` |
| Requires PHP | **8.3** 以降 ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/)) |
| ライセンス | GPL-3.0-or-later |
| Composer | `s2j/inquiry-destination-service` **^0.3.0** (Mail + SaaS Backlog / Asana) ([saas_connection_spec.md](./saas_connection_spec.md)) |

## 位置付け

| 層 | 名称 | 役割 |
| --- | --- | --- |
| 計算 | [s2j-inquiry-destination-service](https://github.com/stein2nd/s2j-inquiry-destination-service) | 統一ペイロード、検証、Mail 材料、結果写像。WordPress 非依存 |
| 副作用 | **本プラグイン** | 設定、SMF フック、`wp_mail`、ログ、相関 ID |
| フォーム | [Snow Monkey Forms](https://wordpress.org/plugins/snow-monkey-forms/) | 入力・確認・完了・自動返信・一時ファイル |
| サイト | kis-inquiry | KIS の問い合わせページ配置 |

## 目的 (初版)

運営者 (`manage_options`) が下記を終えることです。

* サイト全体の dispatch チャネル (**mail** または **SaaS: Backlog / Asana**) を選ぶ。接続は保存前に **検証** 可 ([admin_ui_spec.md](./admin_ui_spec.md) タブ1、[decisions.md](./decisions.md) D-009– D-011)。
* 種別ごとに宛先 (`mail_to` 等) を設定する (0件以上。0件ならその種別は送らない)。種別表は **可変** ([decisions.md](./decisions.md) D-004)。
* SMF フォーム (投稿タイプ `snow-monkey-forms`) を種別に対応付ける。
* 対応付けたフォームのみ、管理者宛を本プラグイン経由で送る。
* 宛先が空の種別を設定画面で見えるようにする。

同じ受信箱にまとめる場合は、**種別ごとに同じ `mail_to` を明示** します。件名だけで種別を分ける運用はしません (Service 件名は補助ラベル)。

## 非目標 (初版)

* フォーム HTML、入力バリデーション、完了文、自動返信文面 (SMF)
* 問い合わせページの作成 (kis-inquiry)
* 案件一覧、ステータス、再送キュー、WP DB への本文保存 (SaaS **起票** は Runner が担う — [status.md](./status.md))
* テーマの色・テンプレート

## サービスとの境界

詳細: [dispatch_execution_spec.md](./dispatch_execution_spec.md)、[data_dictionary.md](./data_dictionary.md)。

| 本プラグイン | Service |
| --- | --- |
| SMF 送信値 → 統一ペイロード | `$_POST` / SMF を読まない |
| フォーム投稿 ID → `inquiry_type` | フォーム ID を持たない |
| option から **宛先レコード** を組み立て渡す | `kind` に応じて材料を返す |
| `wp_mail` / HTTP (`wp_remote_request`) を実行 | 送信しない |
| `subject_hint` に種別 **表示名** (`TypeRow.label`) を **必ず** 載せる | 件名組立 ([mail_adapter_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/mail_adapter_spec.md)) |
| From / Reply-To / 添付パス | — |
| ログ (個人を識別できる情報なし) | 結果レコード (個人を識別できる情報なし) |

## 設定・送信・ログ (要約)

正本は分割仕様です。

* 管理 UI: [admin_ui_spec.md](./admin_ui_spec.md)
* SMF: [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)
* 送信: [dispatch_execution_spec.md](./dispatch_execution_spec.md)
* ログ: [logging_spec.md](./logging_spec.md)
* SaaS (Backlog / Asana): [saas_connection_spec.md](./saas_connection_spec.md)

## 設計方針

[kis-wordpress エコシステム仕様](https://github.com/stein2nd/kis-wordpress/blob/main/docs_mod/specs.md) および [wp-plugin-spec](https://github.com/stein2nd/wp-plugin-spec) に従います。詳細は [principles.md](./principles.md)。

## アンインストール

* 削除する option: `s2j_inquiry_destination_types`、`s2j_inquiry_destination_forms`、将来の SaaS 用 option ([data_dictionary.md](./data_dictionary.md))。
* SMF の投稿は削除しない。
* 環境変数・`wp-config.php` 定数は触らない。

## 実装順

1. 本 `docs/` の合意 (2026-10-19確定)。
2. 設定 UI (種別・宛先、フォーム対応、空宛先の警告)。
3. 対応フォームで SMF 管理者宛 skip + Service + `wp_mail`。
4. 失敗時システムエラー、入力復元、相関 ID ([dispatch_execution_spec.md](./dispatch_execution_spec.md))。
5. Service **^0.3.0** + SaaS Runner / 管理 UI ([saas_connection_spec.md](./saas_connection_spec.md)、D-011– D-012)。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-04 | 初版ドラフト (単一 `specs.md`) |
| 2026-10-19 | Webinar / Survey 同型に分割。Service `docs/` と用語・件名・相関 ID を整合 |
| 2026-10-19 | [decisions.md](./decisions.md) で Runner 呼び出し・ UI 保存・ SMF 接続を固定 |
| 2026-10-19 | 正本を `docs/` に移行。`docs_mod/` は起草用 ([../docs_mod/README.md](../docs_mod/README.md)) |
| 2026-10-20 | 管理 UI3タブ + List Table 実装に合わせ分割 spec を更新 ([admin_ui_spec.md](./admin_ui_spec.md)、D-007– D-010) |
| 2026-10-24 | Requires PHP **8.3** ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/)) |
| 2026-10-24 | Service **v0.3** (Backlog / Asana) に合わせ docs 更新。プラグイン SaaS 実装は未了 |
