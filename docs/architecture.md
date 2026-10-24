# S2J Inquiry Destination - アーキテクチャー

## 設計意図

SMF、設定、送信実行をモジュール分割し、Service 呼び出しは **1箇所** (Dispatch Runner) に集約します。

## フォルダー構成 (想定)

ファイル名は、実装時に [status.md](./status.md) と同期してかまいません。

```plaintext
s2j-inquiry-destination/
├── s2j-inquiry-destination.php
├── uninstall.php              # option 削除 ([data_dictionary.md](./data_dictionary.md))
├── composer.json              # s2j/inquiry-destination-service
├── vendor/
├── assets/admin/
│   ├── types-tab.css          # タブ2 List Table レイアウト
│   └── forms-tab.css          # タブ3 フィールド名列
├── includes/
│   ├── Plugin.php             # ブートストラップ
│   ├── Admin/
│   │   ├── SettingsPage.php   # 3タブ、タブ別 POST / nonce、タブ1 JS + タブ2/3 CSS enqueue
│   │   ├── ListTableLoader.php
│   │   ├── TypesListTable.php / FormsListTable.php
│   ├── Options/
│   │   ├── DispatchOptions.php / SaasOptions.php / TypeOptions.php / FormOptions.php
│   ├── Support/
│   │   ├── SaasProductCatalog.php   # 製品表示名・choicePhrase
│   │   ├── SaasConnectionProbe.php  # タブ1接続検証 (GET)
│   │   └── ServiceGate.php 等
│   ├── SnowMonkey/
│   │   ├── FormRegistry.php   # option 読み取り
│   │   ├── PayloadTranslator.php
│   │   └── MailerHook.php     # skip + 送信パイプライン
│   ├── Dispatch/
│   │   ├── DestinationBuilder.php
│   │   └── Runner.php           # Service 4関数 + wp_mail | wp_remote_request (SaaS)
│   └── Logging/
│       └── InquiryLogger.php
├── tests/
│   ├── Unit/
│   └── Integration/           # WP テスト (任意)
├── docs/                      # 確定仕様 (正本)
└── docs_mod/                  # 改訂案・進行中証跡 (起草)
```

## モジュール責務

| モジュール | 責務 |
| --- | --- |
| `Admin/SettingsPage` | タブルーティング、option 保存 (タブごと nonce)、SMF 未有効 Notice、タブ1 **接続検証 POST** + **serviceTabScript**、タブ2/3 CSS、types Add-row JS |
| `Admin/*ListTable` | 種別・フォーム一覧 (Bulk / Views / 検索 / ソート) |
| `Options/*` | option 読み書き、POST サニタイズ (タブ1: dispatch + SaaS 接続ガード D-010、`resolveForConnectionTest`) |
| `Support/SaasConnectionProbe` | タブ1接続検証用 GET (Runner 起票とは別) |
| `Support/SaasProductCatalog` | サポート製品 slug → 表示名 (UI・エラー文) |
| `Dispatch/DestinationBuilder` | option + TypeRow → Service 宛先レコード |
| `SnowMonkey/FormRegistry` | フォーム ID が対応表に含まれるか |
| `SnowMonkey/PayloadTranslator` | SMF 値 → 統一ペイロード + `subject_hint` |
| `SnowMonkey/MailerHook` | `skip` (副作用なし) / `is_sended` から Runner **1回** 委譲、`RequestContext` |
| `Dispatch/Runner` | Service4関数 + 副作用実行 (**唯一の Service 呼び出し口** — mail / SaaS HTTP、D-012) |
| `Logging/InquiryLogger` | 1行 `error_log` |

## 技術スタック

| 項目 | 方針 |
| --- | --- |
| PHP | `>=8.3` ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/) に合わせる) |
| テスト | PHPUnit。Service 部分はモック不要 (純関数をそのまま呼ぶ) |
| 静的解析 | PHPStan + PHPCS (wp-plugin-spec 同型) |
| ドキュメント | `@s2j/docs-linter` |

## 関連

* 送信: [dispatch_execution_spec.md](./dispatch_execution_spec.md)
* データ: [data_dictionary.md](./data_dictionary.md)
