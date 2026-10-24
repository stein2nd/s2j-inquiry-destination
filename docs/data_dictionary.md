# S2J Inquiry Destination - データ辞書

本ドキュメントは、**プラグイン固有** の永続化と識別子を定義します。統一ペイロード / 宛先レコード / 結果レコードのフィールド意味は、[Service data_dictionary](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md) が正本です。

## option

| option キー | 型 | 説明 |
| --- | --- | --- |
| `s2j_inquiry_destination_dispatch` | `DispatchSettings` | サイト全体の dispatch チャネル (タブ1) ([admin_ui_spec.md](./admin_ui_spec.md)) |
| `s2j_inquiry_destination_types` | `array<string, TypeRow>` | 種別登録名 → 行 (タブ2) |
| `s2j_inquiry_destination_forms` | `array<int, FormRow>` | SMF フォーム投稿 ID → 行 (タブ3) |
| `s2j_inquiry_destination_saas` | `SaasConnection` | サイト接続 (タブ1)。**`dispatch.channel=mail` の保存 POST では更新しない** (D-010) |

### DispatchSettings

| キー | 型 | 説明 |
| --- | --- | --- |
| `channel` | `'mail' \| 'saas'` | サイト全体の dispatch チャネル ([decisions.md](./decisions.md) D-009) |
| `saas_product` | `'' \| 'backlog' \| 'asana'` | `channel=saas` 時のみ。Service `kind` = **`saas_` + 本値** (D-011) |

### 保存例 (`s2j_inquiry_destination_dispatch`)

Mail:

```json
{
  "channel": "mail",
  "saas_product": ""
}
```

SaaS (Backlog):

```json
{
  "channel": "saas",
  "saas_product": "backlog"
}
```

### SaasConnection (`s2j_inquiry_destination_saas`)

製品ごとに使うキーのみ保存 (秘密は平文再表示しない — [admin_ui_spec.md](./admin_ui_spec.md))。

| キー | Backlog | Asana |
| --- | --- | --- |
| `endpoint` | スペース URL (**必須**) | 省略可 (空 → Service デフォルト) |
| `api_key` | API キー | — |
| `access_token` | — | PAT |

### TypeRow

**登録名** (option のキー) = Service の `inquiry_type` slug。

| キー | 型 | チャネル |
| --- | --- | --- |
| `label` | string | 常に |
| `mail_to` | list&lt;string&gt; | **`channel=mail`** (D-003) |
| `saas_project_id` | string | **`channel=saas`** |
| `issue_type_id` | string | **`channel=saas` + `saas_product=backlog`** → `credentials.issue_type_id` |
| `priority_id` | string | **`channel=saas` + `saas_product=backlog`** → `credentials.priority_id` |

SaaS 時 `mail_to` は保存しても Runner は `[]` を渡してよい。

### FormRow

| キー | 型 | 説明 |
| --- | --- | --- |
| `inquiry_type` | string | `TypeRow` の登録名 |
| `fields` | FormFieldMap | SMF コントロール name の対応 |

### FormFieldMap

| キー | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `name` | string | はい | 氏名 |
| `email` | string | はい | メール (Reply-To) |
| `phone` | string | いいえ | 空 name 可 |
| `message` | string | はい | 本文 |

### 保存例 (`s2j_inquiry_destination_types`)

```json
{
  "sales": {
    "label": "営業",
    "mail_to": ["team@example.com"]
  },
  "recruit": {
    "label": "採用",
    "mail_to": []
  }
}
```

### 保存例 (`s2j_inquiry_destination_forms`)

キー `42` は `snow-monkey-forms` 投稿 ID です (整数。PHP では string キー化されうる — 読み取り時は `(int)` 正規化)。

```json
{
  "42": {
    "inquiry_type": "sales",
    "fields": {
      "name": "your-name",
      "email": "your-email",
      "phone": "your-tel",
      "message": "your-message"
    }
  }
}
```

## 統一ペイロード (プラグイン → Service)

プラグインが組み立てる連想配列です。正本: [Service payload_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/payload_spec.md)。

| キー | プラグインでの埋め方 |
| --- | --- |
| `inquiry_type` | `FormRow.inquiry_type` |
| `name` / `email` / `phone` / `message` | `fields` で指定した SMF name から |
| `subject_hint` | **必須 (初版):** 対応する `TypeRow.label` (Service 件名の i18n 正本) |
| `locale` | `determine_locale()` |
| `meta` | 初版 `[]`。未対応コントロールを載せない (本文末尾に足す — [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)) |

## 宛先レコード (プラグイン → Service)

1送信 = 1レコード。**全キーを欠落なく** 渡します。正本: [Service destination_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/destination_spec.md)。組立表: [dispatch_execution_spec.md](./dispatch_execution_spec.md)。

| `channel` | `kind` |
| --- | --- |
| `mail` | `mail` |
| `saas` + `backlog` | `saas_backlog` |
| `saas` + `asana` | `saas_asana` |

## correlation_id

| 項目 | 規則 |
| --- | --- |
| 形式 | `sid-` + **32文字の小文字 hex** (正規表現: `^sid-[0-9a-f]{32}$`)。生成は `bin2hex( random_bytes( 16 ) )` を推奨 (PHPUnit は形式のみ固定) |
| 生成タイミング | 管理者宛の送信試行 (**attempt**) 1回の **開始時** (成功・失敗どちらも同じ ID) |
| 表示 | **systemerror** 時の Notice。自動返信のみ失敗時は **完了画面 Notice** も ([decisions.md](./decisions.md) D-005) |
| Service | `map_dispatch_result` の `context['correlation_id']` に渡し、結果にエコー推奨 |

## WordPress フック (本プラグインが使用)

詳細: [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)。

| フック | 用途 |
| --- | --- |
| `admin_menu` | 設定ページ |
| `snow_monkey_forms/administrator_mailer/skip` | 対応フォームで SMF 管理者メールを抑止 |
| `snow_monkey_forms/administrator_mailer/is_sended` | skip 経路の Runner 成否。失敗時は例外 |
| `snow_monkey_forms/auto_reply_mailer/is_sended` | 管理者宛成功後、自動返信のみ失敗で完了維持 |

## アンインストール

`uninstall.php` で削除:

* `s2j_inquiry_destination_dispatch`
* `s2j_inquiry_destination_types`
* `s2j_inquiry_destination_forms`
* `s2j_inquiry_destination_saas` (存在時)

## 関連

* 管理 UI: [admin_ui_spec.md](./admin_ui_spec.md)
