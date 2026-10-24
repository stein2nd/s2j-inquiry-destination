# S2J Inquiry Destination - Snow Monkey Forms 接続

## 設計意図

[Snow Monkey Forms](https://wordpress.org/plugins/snow-monkey-forms/) (SMF) の完了処理の **管理者宛メール** だけを差し替えます。確認画面・完了文・自動返信・ファイル一時保存は SMF のままです。

## 前提

本プラグインは SMF を同梱しません。無効時は管理画面で通知し、対応フォームでも **skip しません** (SMF デフォルト動作)。

| 項目 | 内容 |
| --- | --- |
| 投稿タイプ | `snow-monkey-forms` |
| フォーム ID | `Setting::get( 'form_id' )` (整数) |
| 送信値 | `Responser` 経由 ([PayloadTranslator](./architecture.md)) |
| **検証下限 (SMF)** | **13.0.0** — 添付取得は `MailParser::get_attachments()` + `administrator_email_body` ([decisions.md](./decisions.md) D-006、[README.md](../README.md)) |

## 対象フォームの判定

1. `form_id = (int) $setting->get( 'form_id' )`
2. `s2j_inquiry_destination_forms[ $form_id ]` が存在 → **対応フォーム**
3. それ以外 → フックは no-op (`skip` false)

## フック (正本)

実装は [decisions.md](./decisions.md) D-001 / D-002に従い、**下記名称を固定** します。

| フック | 優先度方針 | 責務 |
| --- | --- | --- |
| `snow_monkey_forms/administrator_mailer/skip` | デフォルト: 10 | 対応フォームなら `true` のみ。**Runner は呼ばない** |
| `snow_monkey_forms/administrator_mailer/is_sended` | デフォルト: 10 | 対応フォーム (skip 経路) で **Dispatch Runner を最大1回** 同期実行し、成否を返す。失敗時は `RuntimeException` で SMF を **systemerror** ([governance](./governance/documentation_governance.md) §2) にする |
| `snow_monkey_forms/auto_reply_mailer/is_sended` | デフォルト: 10 | 管理者宛成功後、自動返信だけ失敗した場合 **true を返して完了を維持** ([dispatch_execution_spec.md](./dispatch_execution_spec.md)) |

**使わない (初版):** `administrator_mailer/args` で SMF 文面を上書きする方式は採用しない (宛先の正は種別設定のため)。

### skip と is_sended の理由

SMF の `AdministratorMailer` は、`skip === true` の場合、内部送信をせず **デフォルトで成功** を返します (`View.php` は戻り値 `false` を見ないため、失敗は **例外** が必要)。よって:

1. `skip` で SMF 本体の管理者メールを抑止 (副作用なし)
2. `is_sended` 内で Runner を **1回だけ** 同期実行
3. 失敗時 `is_sended` 内で `RuntimeException`
4. 成功時 `is_sended` は `true`。`RequestContext` に管理者宛成功と `correlation_id` を記録 ([decisions.md](./decisions.md) D-002)

相関 ID とカスタムエラー文は [dispatch_execution_spec.md](./dispatch_execution_spec.md) です。

## Runner へのコンテキスト

`MailerHook` は `Dispatch\Runner::run( RunContext $ctx )` に下記を渡します。

| フィールド | 内容 |
| --- | --- |
| `setting` | SMF `Setting` (filter コールバック引数) |
| `responser` | SMF `Responser` |
| `form_id` | `(int) $setting->get( 'form_id' )` |
| `form_row` | option `s2j_inquiry_destination_forms[ form_id ]` |
| `attachment_paths` | 下記 §添付 |

`PayloadTranslator` は `responser` + `form_row` から生ペイロードを組み、Runner 内で Service 正規化以降を実行します。

## ペイロード組立

1. `FormRow` から `fields` を読む
2. `Responser` から各 name の値を取得 (trim)
3. **未写像コントロール**: `Setting::get_controls()` と送信値を走査し、写像済み name 以外を `message` **末尾** に追記

追記形式 (1行1 項目):

```text
{name}: {value}
```

区切りは `\n\n` (Service 本文との境界が分かるよう、追記ブロックの前にも空行1つ)。

4. `subject_hint` = 当該 `TypeRow.label` (**初版必須** — [data_dictionary.md](./data_dictionary.md))
5. `locale` = `determine_locale()`
6. `meta` = `[]` (未写像は meta に載せない)
7. `normalize_inquiry_payload` 以降は Runner 内で Service 公開 API 経由

## 宛先レコード

`FormRow.inquiry_type` から `s2j_inquiry_destination_types` の行を引きます。登録名が存在しない場合は Runner 前に失敗 ([admin_ui_spec.md](./admin_ui_spec.md))。

```php
[
    'kind'            => 'mail',
    'inquiry_type'    => $inquiry_type,
    'mail_to'         => $type_row['mail_to'],
    'saas_endpoint'   => '',
    'saas_project_id' => '',
    'credentials'     => [],
]
```

## 添付ファイル

SMF が一時保存したファイルパスは、Service 材料には含めません。Runner が `wp_mail` の添付引数に **そのまま** 渡します (コピーしない)。削除は SMF 任せです ([dispatch_execution_spec.md](./dispatch_execution_spec.md))。

**取得 (初版):** 検証下限 SMF 版の `AdministratorMailer` (または同等クラス) が管理者メール用に解決する **絶対パスの list** を `RunContext.attachment_paths` に入れる。参照した **クラス・メソッド名** は [status.md](./status.md) §実装前チェックリストに記録する (Integration テストの前提)。

## 入力復元

systemerror 時、SMF は responser に値を保持します。本プラグインは **追加の DB 保存をしません**。ファイル入力はブラウザが復元できないため、訪問者が選び直します。

旧ドラフト (プラグインが欄に戻す) からの変更は [archive/spec-migration-notes.md](./archive/spec-migration-notes.md) を参照してください。

## 実装前チェックリスト

実装着手前に [status.md](./status.md) と合わせて確認します。

- [ ] README と §前提の SMF semver が一致 (D-006)
- [ ] Runner 呼び出しは `is_sended` のみ (D-001)
- [ ] 添付 API の参照クラスを status に記録
- [ ] 自動返信のみ失敗の完了 Notice (D-005)

## 関連

* 決定: [decisions.md](./decisions.md)
* 送信: [dispatch_execution_spec.md](./dispatch_execution_spec.md)
* 管理 UI: [admin_ui_spec.md](./admin_ui_spec.md)
