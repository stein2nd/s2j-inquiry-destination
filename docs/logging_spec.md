# S2J Inquiry Destination - ログ

## 設計意図

障害調査に足る **非 PII (個人を識別できる情報)** 情報だけを、サーバー標準の `error_log` に1行で残します。問い合わせ本文や氏名・連絡先は **書きません** ([Service result_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/result_spec.md) と同境界)。

## 出力先

運営者は、訪問者画面に出した **相関 ID** でサーバーログを検索します。

| 項目 | 初版 |
| --- | --- |
| 出力 | PHP `error_log()` のみ |
| `WP_DEBUG` | off でも書く |
| 管理画面履歴 | なし |
| 独自ログファイル / DB 表 | なし |
| 保持期間 | サーバーローテーション任せ |

## 1送信試行あたり1行 (推奨フィールド)

JSON の1行またはタブ区切りなど **固定形式** (実装で固定し、PHPUnit でスナップショット)。

| フィールド | 含める | 例 |
| --- | --- | --- |
| `ts` | はい | ISO8601 |
| `correlation_id` | はい | `sid-…` |
| `inquiry_type` | はい | 登録名 (`sales`) |
| `form_id` | 推奨 | SMF 投稿 ID |
| `success` | はい | bool |
| `failure_code` | 失敗時 | deficiency 先頭1件、または Service 結果 (`mail_rejected`, `saas_http_error`, `saas_body_invalid` 等 — [Service data_dictionary](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md))。自動返信のみ `autoreply_failed` |
| `channel` | はい | `mail` または `saas` (ログ用。SaaS 時は `kind` / `saas_product` も推奨) |
| `http_status` | SaaS 失敗時 | int (PII なし) |

## 含めない (個人を識別できる情報 / 秘密)

* 氏名、メール、電話、message 本文
* `mail_to` の具体アドレス (必要ならドメインのみにとどめる — 初版は **省略** 推奨)
* SaaS API キー、Authorization

## 画面との対応

* **systemerror** (システムエラー画面): 汎用エラー + 相関 ID (deficiency コード文字列は出さない)
* 自動返信のみ失敗: 完了画面 Notice + 相関 ID。ログは `failure_code` = `autoreply_failed` ([dispatch_execution_spec.md](./dispatch_execution_spec.md))

## 関連

* 相関 ID: [data_dictionary.md](./data_dictionary.md)
* 送信: [dispatch_execution_spec.md](./dispatch_execution_spec.md)
