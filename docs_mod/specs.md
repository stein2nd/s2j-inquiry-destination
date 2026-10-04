# S2J Inquiry Destination - プラグイン仕様 (ドラフト)

採用・合意前の設計メモとして `docs_mod/` に置き、確定後は `docs/SPEC.md` に移行します。状態はドラフトです。記録日は2026-10-04です。

組立の正は [S2J Inquiry Destination Service のサービス仕様](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs_mod/service_spec.md) です。本書は、Snow Monkey Forms との接続、種別と宛先の設定、`wp_mail` の実行を定義します。

## 概要

本プラグインは、問い合わせの送信先を差し替えます。統一ペイロードへの正規化、宛先の選択、メール文面または将来の HTTP リクエスト材料は、S2J Inquiry Destination Service が組み立てます。

フォームの項目、確認画面、完了文、自動返信の文面は、[Snow Monkey Forms](https://wordpress.org/plugins/snow-monkey-forms/) で作ります。本プラグインはフォームを作りません。

スラッグは `s2j-inquiry-destination` です。ライセンスは GPL-3.0-or-later です。テキストドメインは `s2j-inquiry-destination` です。

kis-core は本機能の本体にしません。KIS のページ構成と文言は kis-inquiry に残します。kis-inquiry は本プラグインを有効化して使います。

## 位置付け

| 層 | 名称 | 役割 |
| --- | --- | --- |
| 計算 | [s2j-inquiry-destination-service](https://github.com/stein2nd/s2j-inquiry-destination-service) | 統一ペイロード、送信先の選択、メール文面。WordPress を知らない |
| 副作用 | **本プラグイン** | 設定、秘密情報、Snow Monkey Forms のフック、`wp_mail`、将来の HTTP |
| フォーム | Snow Monkey Forms | 入力項目、確認、完了文、自動返信、一時ファイル |
| サイト | kis-inquiry | KIS の問い合わせページと、どのフォームを置くか |

出発点は、MW WP Form が保守停止であり、問い合わせ案件を WordPress のデータベースに溜めたくない、ということです。SaaS の製品名は未定です。初版の送信先はメールだけです。

## 目的

初版で、管理者が次を終えることです。

* 種別ごとに、メールの宛先を1つ書く。
* Snow Monkey Forms のフォーム (投稿タイプ `snow-monkey-forms` の投稿) を、1つの種別に対応付ける。
* 対応付けたフォームの送信を、統一ペイロードにして、その種別の宛先へ送る。
* 宛先が空の種別を、設定画面で見られるようにする。

同じ受信箱にまとめたい種別は、各行へ同じアドレスを書きます。件名だけで種別を分けることはしません。

## 非目標 (初版)

* フォーム HTML、入力チェック、完了文、自動返信の文面。これらは Snow Monkey Forms です。
* 問い合わせページの作成。KIS では kis-inquiry です。
* 案件の一覧、ステータス、再送キュー。本文は WordPress に残しません。
* SaaS のエンドポイントと API キーの入力欄。製品が決まってから足します。空の送信先実装は置きません。
* テーマの色とテンプレート。

## サービスとの境界

本プラグインは、統一ペイロードと、その種別の宛先設定をサービスに渡します。戻りは、メールの材料と結果レコードです。`wp_mail` は本プラグインが呼びます。

| 本プラグイン | サービス |
| --- | --- |
| Snow Monkey Forms の送信値を、統一ペイロードにする | `$_POST` は読まない。解決済みの種別だけを受け取る |
| フォーム ID から種別を引く | フォーム ID は持たない |
| 種別ごとの宛先を渡す | 種類 (`mail`) に対応する関数で、メール材料を返す |
| `wp_mail` を実行する | `wp_mail` も HTTP も呼ばない |
| 結果のうち、個人情報を除いてログに書く | 結果レコードに氏名、連絡先、本文、API キーを含めない |

宛先が空の種別は送りません。結果は失敗です。

## 設定

画面は「設定 > 問い合わせの送信先」です。操作できるのは `manage_options` を持つユーザーです。Snow Monkey Forms が無効でも、この画面は開きます。そのときは、送信は動かず、有効化を促す文を出します。

表は2つです。どちらも `WP_List_Table` 相当です。

### 種別と宛先

行と種別が対応しています。初期の行は次の2つです。行は追加できます。

| 登録名 | 表示名 |
| --- | --- |
| `sales` | 営業 |
| `recruit` | 採用 |

| 列 | 内容 |
| --- | --- |
| 種別 | 表示名と登録名 |
| 宛先 | メールアドレス1つ。空ならその行を警告する |

種類の列は置きません。初版はすべての行が `mail` です。未設定の種別という選択肢と、共通アドレスへの退避は作りません。宛先が空の種別は、表の上に名前を出します。

保存先はオプション `s2j_inquiry_destination_types` です。登録名をキーにし、値は表示名と宛先メールです。

### フォームと種別

1行が1フォームです。選択肢は、投稿タイプ `snow-monkey-forms` の投稿 (タイトルと ID) です。1つのフォームは1つの種別です。同じ種別へ複数のフォームを対応付けてよいです。

対応付けていないフォームには、本プラグインは触れません。そのフォームの管理者宛メールは、Snow Monkey Forms の設定のままです。

各行で、コントロールの name を次の欄へ対応付けます。

| 欄 | 内容 |
| --- | --- |
| 氏名 | 1つの name |
| メール | 1つの name。Reply-To に使う |
| 電話 | 1つの name。空でもよい |
| 本文 | 1つの name |

対応付けていないコントロールは、送信する本文の末尾へ「name: 値」の行で足します。WordPress には保存しません。

保存先はオプション `s2j_inquiry_destination_forms` です。フォームの投稿 ID をキーにします。

データベースごと移すと、投稿 ID とこの対応は戻ります。エクスポートでフォームを作り直したときと、投稿 ID が衝突したときは、この表で付け直します。

## 送信

対応付けたフォームの完了処理で、次を行います。

1. `snow_monkey_forms/administrator_mailer/skip` を true にし、Snow Monkey Forms 自身の管理者宛メールは送らせません。宛先の正は、本プラグインの種別の行です。
2. 送信値から統一ペイロードを作り、サービスへ渡します。
3. 戻ったメール材料で `wp_mail` を呼びます。From はサイトの管理者メールアドレスです。Reply-To は、ペイロードのメールです。件名には種別の表示名を含めます。振り分けは宛先アドレスで行います。
4. `wp_mail` が送信を受け取ったら成功です。相手の受信箱までは確認しません。
5. 宛先が空、または `wp_mail` が失敗したときは、例外でその送信を終わらせます。Snow Monkey Forms はシステムエラーにし、完了画面へ進みません。受け付けた旨は出しません。
6. 管理者宛が例外なく終わったあと、自動返信は Snow Monkey Forms が、そのフォームの設定で送ります。自動返信の文面は本プラグインが持ちません。

ファイル添付があるフォームでは、Snow Monkey Forms が一時保存したパスを `wp_mail` の添付に渡します。本プラグインはファイルをコピーしません。一時ファイルの削除は Snow Monkey Forms に任せます。

失敗した本文は残しません。再送は、訪問者がその場でもう一度送ることにします。

ログに書くのは、時刻、種別の登録名、成功または失敗、相関 ID です。氏名、メール、電話、本文、送信先が返した本文は書きません。失敗のシステムエラー文には、相関 ID だけを足します。

## 設計方針

[kis-wordpress エコシステム仕様](https://github.com/stein2nd/kis-wordpress/blob/main/docs_mod/specs.md) および [wp-plugin-spec](https://github.com/stein2nd/wp-plugin-spec) に従います。ペイロードとメール材料はサービス側の純関数です。本プラグインはアダプタです。

| 借用する原則 | 本プラグインでの意味 |
| --- | --- |
| 依存の向きは外 → 内 | 画面とフックがサービスを呼ぶ。サービスは WordPress を呼ばない |
| 内側はビジネスルール | 正規化、宛先の選択、文面はサービス |
| 外側は詳細 | オプション、Snow Monkey Forms、`wp_mail` |

Composer で `s2j/inquiry-destination-service` を require します。パッケージの参照元 (`VCS` か `path` か) は実装時に決めます。

アンインストールでは、オプション `s2j_inquiry_destination_types` と `s2j_inquiry_destination_forms` を消します。Snow Monkey Forms の投稿は消しません。

## 関連リポジトリ

| 名称 | 種別 | 役割 |
| --- | --- | --- |
| **本プラグイン** | WP プラグイン | 設定、Snow Monkey Forms のフック、`wp_mail` |
| [s2j-inquiry-destination-service](https://github.com/stein2nd/s2j-inquiry-destination-service) | Composer | 統一ペイロード、送信先、メール材料 |
| [Snow Monkey Forms](https://wordpress.org/plugins/snow-monkey-forms/) | WP プラグイン | フォーム本体。本プラグインは同梱しない |
| [kis-wordpress](https://github.com/stein2nd/kis-wordpress) / kis-inquiry | モノレポ内プラグイン | KIS の問い合わせページ。送信先は抱え込まない |
| [kis2026_base](https://github.com/stein2nd/kis2026_base) | テーマ | 見た目。現行の MW WP Form フックは、kis-inquiry の Snow Monkey Forms へ移す |

## 実装順

1. 本ドラフトの合意。
2. 種別と宛先、フォーム対応、変えた行だけの保存。空の宛先を見せる。
3. 対応付けたフォームで管理者宛メールをやめ、サービスと `wp_mail` につなぐ。
4. 失敗をシステムエラーにし、相関 ID を出す。
5. SaaS が決まったあと、設定の種類と HTTP 実行を足す。

## 本ドラフトの提案

合意前の提案です。

* メニューは「設定 > 問い合わせの送信先」。初期種別は `sales` (営業) と `recruit` (採用)。行は追加できる。
* 設定キーは `s2j_inquiry_destination_types` と `s2j_inquiry_destination_forms`。
* 種別1つにつき、宛先メールは1つ。同報は、そのアドレスの先で行う。
* 対応付けたフォームだけ `snow_monkey_forms/administrator_mailer/skip` を true にする。失敗は例外にして、Snow Monkey Forms のシステムエラーに乗せる。
* From はサイトの管理者メールアドレス。Reply-To は訪問者のメール。
* 対応付けていないコントロールは、送信する本文の末尾に足す。
* 添付は、Snow Monkey Forms の一時ファイルをそのまま渡す。
* ログは、個人情報を含まない1行を `error_log` に書く。管理画面の履歴は持たない。

## 未決事項

* `s2j/inquiry-destination-service` を Composer にどう参照するか。
* 種別1つについての宛先を、複数アドレスにするか。本ドラフトでは1つ。
* システムエラーのあと、Snow Monkey Forms の画面が入力値を残すか。残すことが要件。実装時に、残らない場合は画面側で補う。
* 管理者宛が成功したあと、自動返信だけが失敗したとき。初版は Snow Monkey Forms のシステムエラーのままにする。
* SaaS 決定後の、エンドポイントと API キーの置き場。
* ログを `error_log` 以外に残すか。

## 改訂履歴

| 日付 | 内容 |
| --- | --- |
| 2026-10-04 | 初版ドラフト。Snow Monkey Forms でフォームを作り、対応付けたフォームの管理者宛だけを本プラグインが送る、と記録 |
