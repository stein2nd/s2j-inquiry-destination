# S2J Inquiry Destination - Mail 配送・認証

索引: [specs.md](./specs.md) How 層。Service Transport 正本: [transport_boundary_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md) (**v0.4.0**)。

## 1. 設計意図

問い合わせを **Mail チャネル** (`s2j_inquiry_destination_dispatch.channel` = `mail`) で届ける場合、本プラグインは **宛先、本文、SMF 連携** までを担い、**メール認証 (DKIM 署名、SPF/DMARC に関する DNS 診断)** は **WordPress サイト全体の `wp_mail` パイプライン (Transport 層)** に任せます。

**SaaS チャネル** (`channel=saas`) は HTTP 起票のみであり、本 spec の対象外です ([saas_connection_spec.md](./saas_connection_spec.md))。

計算 (件名・本文行順・不足コード) の正本は [Inquiry Destination Service](https://github.com/stein2nd/s2j-inquiry-destination-service) です。Service は Transport 認証を知りません (上記 transport_boundary_spec)。

## 2. 用語

| 用語 | 意味 |
| --- | --- |
| **Mail チャネル** | タブ1: dispatch `channel=mail`。Runner が `wp_mail` を呼ぶ |
| **Transport 層** | `wp_mail` → PHPMailer → ホスト `mail()` / SMTP プラグイン / **`Email Auth`** |
| **`Email Auth`** | WordPress.org プラグイン [Email Auth](https://wordpress.org/plugins/email-auth/) (GPL-2.0+)。設定画面 **設定 > Email Auth** (`options-general.php?page=email-auth`) |
| **推奨コンパニオン** | 導入を案内するが、本プラグインの **必須依存ではない** (D-014) |

プラグイン識別子 (実装): プラグインファイル `email-auth/email-auth.php`、定数 `EAUTH_PLUGIN_VERSION` (有効時)。

## 3. 責務分界

GatherPress + S2J Webinar 型の **ホスト設定画面に UI 統合しません**。`Email Auth` はサイト全体設定のみです。

| 責務 | 主体 | 備考 |
| --- | --- | --- |
| SMF フォーム ↔ 種別・フィールド写像 | **本プラグイン** | `s2j_inquiry_destination_forms` (D-007タブ3) |
| dispatch チャネル | **本プラグイン** | D-009 |
| SaaS API 秘密・接続 | **本プラグイン** | `channel=saas` 時、`s2j_inquiry_destination_saas` (D-010) |
| 種別ごと `mail_to` | **本プラグイン** | `channel=mail` 時、D-003 |
| ペイロード正規化、Mail 材料 (4キー) | **Service** | D-001、Runner のみが呼ぶ |
| `wp_mail` 呼び出し、Reply-To (訪問者) | **本プラグイン** `Dispatch\Runner` | [dispatch_execution_spec.md](./dispatch_execution_spec.md) |
| From / DKIM / Bounce / SPF / DMARC 診断 UI | **`Email Auth`** (推奨) または同等 | サイト全体。D-013 |
| SPF/DMARC **DNS レコード作成** | **運用者 / DNS** | プラグイン外 |

## 4. 実行経路

1. SMF `administrator_mailer/is_sended` → `Dispatch\Runner` (D-001)。
2. Service `build_dispatch_material` → MailMaterial。
3. Runner が `wp_mail( $to, $subject, $body, $headers, $attachments )`。
4. `Email Auth` (有効時) が `wp_mail_from` / `phpmailer_init` 等で PHPMailer を変更 — **写像フォームの管理者宛、SMF 自動返信、コアの `wp_mail` すべて** に適用。

```text
Runner::executeMail()  [includes/Dispatch/Runner.php]
  → wp_mail()
    → wp_mail_from / wp_mail_from_name
    → phpmailer_init  ← Email Auth: DKIM, Sender, 任意 Reply-To
    → 送信
```

## 5. From / Reply-To 規則 (D-015)

| ヘッダー | 設定者 | 規則 |
| --- | --- | --- |
| To / Subject / Body / Content-Type | Service 材料 + Runner | 既存 |
| **From** | **Runner は `$headers` に載せない** | WordPress デフォルト + `Email Auth` (Force from 等) |
| **Reply-To** | Runner | ペイロード `email` が `is_email()` の場合 **`Reply-To: {email}`** の1行 |

**`Email Auth`「Force the use of the address above and ignore other plugins」** が ON の場合、From は `Email Auth` 設定が優先されます。Runner が `From:` を直書きしないことで二重定義を避けます。

**Reply-To の二重:** `Email Auth` がグローバル Reply-To を設定している場合、PHPMailer に Reply-To が複数載る可能性があります。運用では問い合わせサイトでは **`Email Auth` の Reply-To を空** (From と同じにフォールバック) を **推奨** します (強制しない)。

## 6. 管理 UI (D-014)

**正本:** [admin_ui_spec.md](./admin_ui_spec.md) § **`channel=mail` 時のメール認証案内**。

| 項目 | 規則 |
| --- | --- |
| 表示 | `channel=mail` のみ (PHP 初期 + `serviceTabScript()` で `saas` 時非表示) |
| 体裁 | `notice notice-info inline`、チャネル fieldset 直後 |
| 検出 | `SettingsPage::isEmailAuthActive()` — `EAUTH_PLUGIN_VERSION` または `is_plugin_active( 'email-auth/email-auth.php' )` |
| 未導入 | WordPress.org リンク + DKIM/SPF/DMARC 検討の i18n |
| 導入済 | **設定 → Email Auth** (`admin_url( 'options-general.php?page=email-auth' )`) |

v0.2 **必須外:** Webinar 型 `CompanionStatus` (installed / inactive / activate URL)。

## 7. 共存・検証

正本チェックリスト: [status.md](./status.md) §手動、[test-results.md](./test-results.md)。

### 7.1. 必須確認 (手動)

| # | 条件 | 期待 |
| --- | --- | --- |
| 1 | `Email Auth` **無効**、Mail チャネル | 写像フォーム送信成功 |
| 2 | `Email Auth` **有効** (Local 可。DKIM selector 任意) | 写像フォーム送信成功 |
| 3 | `Email Auth` 有効 | `wp-login.php` パスワードリセット成功 (D-005 / 0.1.1回帰) |
| 4 | `channel=saas` | タブ1に `Email Auth` 案内 **非表示** |

### 7.2. 注意 (運用)

* **二重 DKIM:** 下流 SMTP がすでに署名する場合、WordPress 側 DKIM と競合しないか確認。
* **localhost / Local:** SPF/DMARC 診断の失敗表示は正常。本番ドメインで再検証。
* **他 SMTP プラグイン:** `wp_mail` 差し替えと `Email Auth` の順序は環境依存。

## 8. 決定事項

D-013— Mail 認証を本プラグインに実装しない  
D-014— `Email Auth` は推奨・任意依存  
D-015— Runner は `From:` ヘッダーを直書きしない  

詳細: [decisions.md](./decisions.md)。

## 9. Service との境界

Service の Mail 材料に **`dkim_selector`** 等を **追加しません**。Transport は [Service transport_boundary_spec.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md) が正本です。
