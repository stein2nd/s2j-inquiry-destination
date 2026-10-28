# S2J Inquiry Destination - 概要

## 概要

**S2J Inquiry Destination** は、Snow Monkey Forms で作った問い合わせフォームの **管理者宛の送信** を、種別ごとの宛先 (Mail または SaaS) に差し替える WordPress プラグインです。ペイロード正規化と Mail 文面の組立は [S2J Inquiry Destination Service](https://github.com/stein2nd/s2j-inquiry-destination-service) (**^0.4.0**) が担います。

実行環境の PHP 下限は **8.3** です ([plugin_spec.md](./plugin_spec.md))。

## 目的

* WordPress DB に問い合わせ案件を溜めない (送信先に直接渡す)。
* 営業 / 採用など **種別ごと** に宛先を分ける。
* 同じ統一ペイロードから **Backlog / Asana** 等に HTTP 起票 ([saas_connection_spec.md](./saas_connection_spec.md))。
* **Mail チャネル:** 到達性・認証 (DKIM/SPF/DMARC) は **`Email Auth` 等の Transport** に任せ、本プラグインは写像、`wp_mail` 実行まで ([mail_deliverability_spec.md](./mail_deliverability_spec.md))。

## 非目的 (初版 + v0.2)

* フォームビルダ機能 (SMF 任せ)。
* チケット管理 UI。
* 再送キュー、失敗本文の永続化。
* **DKIM/SPF/DMARC の実装、DNS 編集** (本プラグイン、D-013)。

## 責務

| 責務 | 参照 |
| --- | --- |
| 設定画面 (dispatch チャネル、種別・宛先、フォーム対応) | [admin_ui_spec.md](./admin_ui_spec.md) |
| SMF フックとフィールド写像 | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| Service 呼び出しと `wp_mail` / HTTP | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| Mail Transport 案内 (`Email Auth`) | [mail_deliverability_spec.md](./mail_deliverability_spec.md) |
| 相関 ID と `error_log` | [logging_spec.md](./logging_spec.md) |

## 非責務

| 非責務 | 担当 |
| --- | --- |
| 統一ペイロード検証、Mail 材料 (4キー) | Service |
| Mail Transport (SMTP、DKIM 署名、SPF/DMARC 診断) | **`Email Auth`** / ホスト (Service [transport_boundary_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md)) |
| 自動返信の文面と送信タイミング (管理者メール成功の後) | SMF |
| 訪問者向け完了文の編集 | SMF |

## 関連

* 索引: [specs.md](./specs.md)
* 統合見取り図: [plugin_spec.md](./plugin_spec.md)
* v0.2イニシアチブ証跡: [archive/impl-v0.2-mail-auth/](./archive/impl-v0.2-mail-auth/)
