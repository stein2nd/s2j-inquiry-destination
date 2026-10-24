# S2J Inquiry Destination - 概要

## 概要

**S2J Inquiry Destination** は、Snow Monkey Forms で作った問い合わせフォームの **管理者宛の送信** を、種別ごとの宛先 (初版: メール) に差し替える WordPress プラグインです。ペイロード正規化と Mail 文面の組立は [S2J Inquiry Destination Service](https://github.com/stein2nd/s2j-inquiry-destination-service) が担います。

実行環境の PHP 下限は **8.3** です ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/) に合わせる — [plugin_spec.md](./plugin_spec.md))。

## 目的

* WordPress DB に問い合わせ案件を溜めない (送信先に直接渡す)。
* 営業 / 採用など **種別ごと** に宛先を分ける。
* 同じ統一ペイロードから **Backlog / Asana** に HTTP 起票 (Service ^0.3— [saas_connection_spec.md](./saas_connection_spec.md))。

## 非目的 (初版)

* フォームビルダ機能 (SMF 任せ)。
* チケット管理 UI。
* 再送キュー、失敗本文の永続化。

## 責務

| 責務 | 参照 |
| --- | --- |
| 設定画面 (dispatch チャネル、種別・宛先、フォーム対応) | [admin_ui_spec.md](./admin_ui_spec.md) |
| SMF フックとフィールド写像 | [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md) |
| Service 呼び出しと `wp_mail` | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| 相関 ID と `error_log` | [logging_spec.md](./logging_spec.md) |

## 非責務

| 非責務 | 担当 |
| --- | --- |
| 統一ペイロード検証・ Mail 材料 | Service |
| 自動返信の文面と送信タイミング (管理者宛の成功後) | SMF |
| 訪問者向け完了文の編集 | SMF |

## 関連

* 索引: [specs.md](./specs.md)
* 統合見取り図: [plugin_spec.md](./plugin_spec.md)
