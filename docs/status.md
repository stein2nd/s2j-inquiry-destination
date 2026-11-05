# S2J Inquiry Destination - 実装状況

最終更新: 2026-11-01 (Unit **25**、`FormOptionsTest`)

索引は [specs.md](./specs.md) です。計算規則の正本は [Inquiry Destination Service](https://github.com/stein2nd/s2j-inquiry-destination-service) の `docs/` です。Pass 記録は [test-results.md](./test-results.md) です。

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [plugin_spec.md](./plugin_spec.md) — 統合見取り図
* [admin_ui_spec.md](./admin_ui_spec.md) — 設定画面 (タブ1–3)
* [saas_connection_spec.md](./saas_connection_spec.md) — dispatch / サイト接続
* [dispatch_execution_spec.md](./dispatch_execution_spec.md) — Runner、Mail / SaaS HTTP
* [mail_deliverability_spec.md](./mail_deliverability_spec.md) — Mail Transport、`Email Auth` (v0.2)
* [data_dictionary.md](./data_dictionary.md) — option キー
* Service: [usage_spec §SaaS](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/interfaces/usage_spec.md)

## Service 組み込み

| 項目 | 状態 |
| --- | --- |
| Packagist `s2j/inquiry-destination-service` | **^0.4.0** — [plugin_spec.md](./plugin_spec.md)、`composer.lock` **v0.4.0** |
| Service **v0.4.0** (Transport 境界 + v0.3の SaaS) | 組込み済 |
| Dispatch Runner → Service 公開4関数 | 実装済、**Mail + SaaS HTTP** ([includes/Dispatch/Runner.php](../includes/Dispatch/Runner.php)) |
| Service 同梱 dist zip | `npm run dist` + [dist-zip.yml](../.github/workflows/dist-zip.yml) |

## 機能一覧

**完了条件列:** 100% 到達用チェックリスト (1行 ≒ PHPUnit の1ケース、CI の1ステップ、または手動確認1項)。`[x]` = 満たし済み、`[ ]` = 未。正本 spec は各§リンク。

**環境の場合分け:** **非 HTTPS** = サイトを `http://` (Local 等) で動かしたまま完結できる項。**HTTPS** = 実 SaaS API、本番相当 URL、TLS 前提の外部結合 ([§ HTTPS 環境](#status-https))。

**§ 内の並び:** 各環境ブロックで **自動テスト → 手動** の順。

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (100%) |
| --- | --- | --- | --- |
| 仕様分割 (`docs/`) | 確定 | — | [§ 非 HTTPS · 自動](#status-local-automated) · 仕様分割 |
| Service 組込み (^0.4.0) | 実装済 | 100 | [§ Service 組み込み](#status-composer-service) (3/3) |
| Options サニタイズ (dispatch / SaaS / types) | 実装済 | 100 | [§ Options](#status-options) (10/10) |
| 宛先レコード (`DestinationBuilder`) | 実装済 | 100 | [§ DestinationBuilder](#status-destination) (1/1) |
| Service 連携 (Unit) | 実装済 | 100 | [§ Service 連携](#status-service-integration) (3/3) |
| Runner Mail ヘッダー (D-015、Unit) | 実装済 | 100 | [§ Runner Mail](#status-runner-mail) (2/2) |
| 接続検証 Probe (Unit、HTTP スタブ) | 実装済 | 100 | [§ SaasConnectionProbe](#status-saas-probe) (3/3) |
| SaaS 製品カタログ | 実装済 | 100 | [§ SaasProductCatalog](#status-saas-catalog) (3/3) |
| Runner (SaaS HTTP 起票) | コード済 / E2E 未 | 0 | [§ HTTPS · 手動](#status-https-manual) |
| 品質ゲート (PHPUnit / PHPStan / PHPCS / docs) | 実装済 | 100 | [§ 品質ゲート](#status-quality) (6/6) |
| dist zip CI | 実装済 | 100 | [§ 配布](#status-dist) (2/2) |
| 管理 UI (3タブ + タブ1の JS) | 実装済 / 手動 UI 可能分完了 | 17 | [§ 非 HTTPS · 手動](#status-local-manual) · 管理 UI (7/7) |
| Mail 送信、ログ、SMF マッピング | コード済 / 手動一部 | 50 | [§ 非 HTTPS · 手動](#status-local-manual) · Mail (3/6)、SMF skip (0/1) |
| v0.2の Mail / `Email Auth` | コード済 / smoke 完了 | 100 | [§ v0.2 手動](#status-v02-manual) (4/4) |
| SaaS 実 API (検証 + 起票) | 未記録 | 0 | [§ HTTPS · 手動](#status-https-manual) (0/4) |

## v0.2 (Mail 配送・認証) サマリー {#status-v02}

正本: [mail_deliverability_spec.md](./mail_deliverability_spec.md)、[decisions.md](./decisions.md) D-013– D-015。証跡: [archive/impl-v0.2-mail-auth/](./archive/impl-v0.2-mail-auth/)。

| 項目 | 状態 |
| --- | --- |
| spec (`docs/`) | **確定** (2026-10-27) |
| タブ1 `Email Auth` 案内 (Plugin Name 動的) | **実装済** |
| Runner D-015 (`From:` 削除) | **実装済** — Unit [§ Runner Mail](#status-runner-mail) |
| Composer **^0.4.0** | **実装済** (lock `v0.4.0`) |
| 手動 smoke (v0.2-1–4) | **Pass** (2026-11-03 `Local`) — [test-results.md §v0.2](./test-results.md#test-v02)、[§ v0.2 手動](#status-v02-manual) |

## 100% 完了条件チェックリスト

### 非 HTTPS 環境でテスト可能なこと {#status-local}

WordPress を **HTTP** (例: Local `http://…`) でホストしたまま実行できる項。外向き **実 SaaS API** は [§ HTTPS 環境](#status-https) に分離する。

#### 自動テストで可能なこと {#status-local-automated}

##### 仕様分割 {#status-spec-docs}

* [x] CI: `docs-lint` が `docs/**` 等を lint ([docs-lint.yml](../.github/workflows/docs-lint.yml))
* [x] 正本が `docs/`、起草が `docs_mod/` ([governance/documentation_governance.md](./governance/documentation_governance.md))

##### Service 組込み {#status-composer-service}

正本: [plugin_spec.md](./plugin_spec.md)、[saas_connection_spec.md](./saas_connection_spec.md)

* [x] Local: `composer install` — `s2j/inquiry-destination-service` **^0.4.0** が `vendor/` に入る
* [x] Test: `ServiceGateTest::test_service_package_is_available_when_vendor_installed`
* [x] Local: `npm run dist` — `vendor/` 同梱 zip が生成される ([dist-zip.yml](../.github/workflows/dist-zip.yml))

##### Options サニタイズ {#status-options}

正本: [data_dictionary.md](./data_dictionary.md)、[decisions.md](./decisions.md) D-009– D-010、[admin_ui_spec.md](./admin_ui_spec.md) §接続の検証

* [x] Test: `DispatchOptionsTest::test_sanitize_accepts_saas_backlog`
* [x] Test: `DispatchOptionsTest::test_destination_kind` — D-011 `saas_{product}`
* [x] Test: `DispatchOptionsTest::test_sanitize_rejects_unknown_saas_product_with_choice_phrase`
* [x] Test: `SaasOptionsTest::test_sanitize_from_post_ignores_credentials_when_channel_is_mail` — D-010
* [x] Test: `SaasOptionsTest::test_resolve_for_connection_test_uses_post_over_stored_secrets_for_backlog`
* [x] Test: `SaasOptionsTest::test_resolve_for_connection_test_requires_backlog_endpoint`
* [x] Test: `SaasOptionsTest::test_resolve_for_connection_test_requires_backlog_api_key_when_endpoint_known`
* [x] Test: `SaasOptionsTest::test_resolve_for_connection_test_accepts_asana_token_from_post`
* [x] Test: `SaasOptionsTest::test_resolve_for_connection_test_unknown_product_uses_choice_phrase`
* [x] Test: `TypeOptionsTest::test_valid_slug` / `TypeOptionsTest::test_sanitize_from_post_adds_custom_type`
* [x] Test: `FormOptionsTest::test_sanitize_from_post_rejects_unknown_inquiry_type` / `FormOptionsTest::test_sanitize_from_post_accepts_known_inquiry_type` (#10)

##### DestinationBuilder {#status-destination}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md)

* [x] Test: `DestinationBuilderTest::test_builds_backlog_destination`

##### Service 連携 (Unit) {#status-service-integration}

正本: Service [php_api_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/interfaces/php_api_spec.md)、[transport_boundary_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md) (v0.4)

* [x] Test: `ServiceIntegrationTest::test_validate_and_build_mail_material`
* [x] Test: `ServiceIntegrationTest::test_custom_inquiry_type_slug`
* [x] Test: Mail 材料 — `to` / `subject` / `body` / `content_type` の **4キーのみ** (上記 `test_validate_and_build_mail_material` 内)

##### Runner Mail (Unit) {#status-runner-mail}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md) § Mail、D-015

* [x] Test: `RunnerMailHeadersTest::test_execute_mail_omits_from_header_d015` — `$headers` に `From:` なし
* [x] Test: `RunnerMailHeadersTest::test_execute_mail_skips_reply_to_when_email_invalid`

##### SaasConnectionProbe (Unit) {#status-saas-probe}

正本: [saas_connection_spec.md](./saas_connection_spec.md) §接続検証、[admin_ui_spec.md](./admin_ui_spec.md) §接続の検証

* [x] Test: `SaasConnectionProbeTest::test_backlog_success_on_http_200` — URL / `apiKey` クエリー
* [x] Test: `SaasConnectionProbeTest::test_asana_failure_on_http_401` — デフォルト base + Bearer
* [x] Test: `SaasConnectionProbeTest::test_network_error` — `WP_Error` 経路

##### SaasProductCatalog {#status-saas-catalog}

正本: [includes/Support/SaasProductCatalog.php](../includes/Support/SaasProductCatalog.php)

* [x] Test: `SaasProductCatalogTest::test_labels_include_supported_slugs`
* [x] Test: `SaasProductCatalogTest::test_is_supported`
* [x] Test: `SaasProductCatalogTest::test_choice_phrase_joins_supported_product_labels`

##### 品質ゲート {#status-quality}

正本: [test-results.md](./test-results.md) 段階3

* [x] Local: `composer test` — Unit **25** 件全緑 (`tests/Unit/`、`FormOptionsTest` 含む)
* [x] Local: `composer phpstan` — エラー0
* [x] Local: `composer lint:php` — PHPCS (PSR-12 + WPCS 分割) 通過
* [x] CI: [.github/workflows/phpunit.yml](../.github/workflows/phpunit.yml) — PHP **v8.3**、`composer test`
* [x] CI: [.github/workflows/docs-lint.yml](../.github/workflows/docs-lint.yml)
* [x] Local: `npm run lint:docs` (docs-lint と同等)

##### 配布 {#status-dist}

* [x] CI: [dist-zip.yml](../.github/workflows/dist-zip.yml) — タグ `v*` / `workflow_dispatch`
* [x] 記録: artifact zip Pass — [test-results.md §GitHub Actions](./test-results.md#github-actions-main) (2026-10-25· run [40137154220](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40137154220))

#### 自動テストでは不可能なこと (手動) {#status-local-manual}

##### 管理 UI (HTTP Local) {#status-admin-local}

正本: [admin_ui_spec.md](./admin_ui_spec.md)

* [x] Manual: タブ1–3各保存 — **当該 option のみ** 更新 (D-007) —2026-11-01Local: タブ2label のみ変更・タブ3反映・タブ1UI 不変。**#2i:** Asana →種別追加→ Backlog 切替後も種別行維持 ([test-results.md](./test-results.md))
* [x] Manual: タブ1— `channel=mail` 保存で `s2j_inquiry_destination_saas` が POST から更新されない (D-010) —2026-11-01Local: SaaS → Asana で API ベース URL 入力→ Mail に戻して保存→他タブ往復→再 Asana で URL 空 ([test-results.md](./test-results.md) #2b)
* [x] Manual: タブ1— 画面上 `channel=saas` で SaaS 製品・接続欄・**接続を検証** が有効化 (`serviceTabScript`)。検証 POST で option 不変 —2026-11-01Local Pass ([test-results.md](./test-results.md) #2e)
* [x] Manual: タブ2— Bulk / Views / 検索 (D-008) — 列ソート (スラッグ・表示名) —2026-11-01Local Pass ([#2c](./test-results.md)、[#2c-sort](./test-results.md))
* [x] Manual: タブ2— SaaS Asana 狭幅 (≤782px): 表示名・プロジェクト ID が表示 (`--asana` Z 字) —2026-11-01Pass ([#2h](./test-results.md))
* [x] Manual: 管理 UI — タブ切替 (GET) ・ POST (保存 / 接続検証 / Bulk) の wait 表示 (D-017、`.spinner`) —2026-11-01Local Pass ([#2j](./test-results.md))
* [x] Manual: タブ3— 狭幅 (≤782px): `data-colname` 縦積み・長ラベル省略なし・`column-fields` はみ出しなし (`forms-tab.css`) —2026-11-01Local Pass ([test-results.md](./test-results.md) #2d)
* **N/A (手動 UI) / Unit Pass:** タブ3— 存在しない `inquiry_type` 保存でエラー — UI は `<select>` のため手動不可。**Unit:** [FormOptionsTest.php](../tests/Unit/Options/FormOptionsTest.php) (`#10` [test-results.md](./test-results.md))

##### Mail、ログ (HTTP Local、MailHog 等) {#status-mail-local}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md)、[logging_spec.md](./logging_spec.md)

**共通前提 (再現可):** SMF **有効**、タブ1 **`channel=mail` 保存済み**、タブ3 **マッピング済み** 公開フォーム、Mailpit / MailHog 等。**管理 UI のタブ切替仕様ではブロックされない** (フロント SMF 送信)。

**証跡:** 実メール・氏名を含むスクショは **リポジトリに載せない** ([test-results.md §記録](./test-results.md#記録) 2026-11-03)。

#### 残り手動項 — 推奨順 (Local / Mailpit) {#status-mail-local-runbook}

| 順 | 項目 | 手順 (要約) | 期待 |
| --- | --- | --- | --- |
| 1 | [v0.2-4](#status-v02-manual) | タブ1で **問い合わせ管理 SaaS** を選択 (保存前でも可)。Mail 認証 (`Email Auth`) 案内が **出ない** | UI のみ。SaaS 保存は不要 |
| 2 | `error_log` (#6) | マッピングフォームを **1回送信** (成功で可)。Local の PHP / WP ログで [InquiryLogger](../includes/Logging/InquiryLogger.php) の **JSON1行** を確認: `correlation_id`・`inquiry_type`・`form_id`・`success`・`channel` あり。**氏名・メール・本文・`mail_to` 具体アドレスなし** | [logging_spec.md](./logging_spec.md) |
| 3 | [v0.2-2](#status-v02-manual) | **`Email Auth` プラグイン有効** (設定画面で DKIM 等 **未設定でも可** — [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7.1 #2・§7.2)。タブ1 **Mail 保存済み**のまま、マッピングフォーム送信 → Mailpit で **管理者1通** + 従来どおり SMF 自動返信。完了画面 | 送信成功。From は Transport / `Email Auth` 任せ (Runner `From:` 直書きなし) |
| 4 | [v0.2-3](#status-v02-manual) | 管理ユーザーで **パスワードリセット** (`wp-login.php` → 忘れたほう)。届くメール (Mailpit) | リセットメール到達 = D-005回帰 (Runner 非経路) |
| 5 | 複数 `mail_to` (#9) | タブ2: テスト用種別の textarea に **2行** (有効アドレス2つ) → 保存 → マッピングフォーム送信。Mailpit **1通**、To に **両方** | [admin_ui_spec.md](./admin_ui_spec.md) D-003 |
| 6 | 未マッピングコントロール (#8) | SMF フォームに **タブ3未マッピング**のコントロールを1つ追加 (例: チェックボックス)。送信 → 管理者本文 **末尾**に `{control名}: {値}` | [PayloadTranslator](../includes/SnowMonkey/PayloadTranslator.php) |
| 7 | 空宛先 (#4) | タブ2: マッピング先種別の **メール欄を空** → 保存 → 同フォーム送信 | SMF **systemerror** (完了画面にならない)。画面に **相関 ID**。Mailpit に管理者通知 **なし** (または失敗ログ) |
| 8 | 自動返信のみ失敗 (#7) | SMF **自動返信 ON** のまま、**2通目**の `wp_mail` だけ失敗させる (例: 自動返信テンプレの To を不正、または一時的に SMTP 拒否)。**1通目 (Runner)** は成功させる | **完了画面** + 警告 Notice + 相関 ID。ログ `failure_code` = `autoreply_failed` |

**Email Auth (localhost):** 有効化済み・**未設定** = v0.2-2 / v0.2-3の前提を満たす。SPF/DMARC 診断の警告表示は Local では **正常** (§7.2)。問い合わせ Mail は **必須設定なし** (D-014)。

**#7の注意:** 環境を壊しやすい。最後に実施。管理者 Runner が失敗すると systemerror になり #4と混同する。

* [x] Manual: マッピング済みフォーム送信 — To = 種別 `mail_to`、Reply-To = 訪問者、**From は Runner `$headers` に載せない** (D-015) —2026-11-03Local **Mailpit** Pass ([#3](./test-results.md))。**Unit:** [RunnerMailHeadersTest](../tests/Unit/Dispatch/RunnerMailHeadersTest.php)。セッション: タブ2 `recruit`/`sales`、タブ3フォーム **157** → `recruit`、SMF 完了画面、管理者1通 + SMF 自動返信1通
* [ ] Manual: 空宛先種別 — systemerror、相関 ID、完了しない — マッピング先種別の **`mail_to` 空** (Mail チャネル)。SaaS 時はプロジェクト ID 等欠落で同様に dispatch 失敗 (別 deficiency)
* [x] Manual: `error_log` 1行 — PII なし、`correlation_id` あり —2026-11-03Local Pass ([#6](./test-results.md))。`php/error.log` — `sid-c6fa…` / `recruit` / form **157** / `success` true / `channel` mail。氏名・メール・本文・宛先アドレス **なし**
* [ ] Manual: 自動返信のみ失敗 — 完了維持 + Notice + `autoreply_failed` ログ — **要:** SMF **自動返信 ON** + 管理者 Runner 成功後に **自動返信の経路の `wp_mail` だけ失敗** ([MailerHook::onWpMailFailed](../includes/SnowMonkey/MailerHook.php))。再現は可能だが **環境操作が必要** (不正 To、SMTP 拒否等)
* [ ] Manual: 未マッピング SMF コントロール — 管理者本文の末尾に `{name}: {value}` — フォームにマッピング外コントロールを追加 ([PayloadTranslator::appendUnmappedControls](../includes/SnowMonkey/PayloadTranslator.php))
* [x] Manual: 複数 `mail_to` — `wp_mail` 1回・同一 To 行 —2026-11-03Local Pass ([#9](./test-results.md))。`recruit` に **2アドレス** → 管理者通知 **1通**・ To **1行2件**

##### v0.2Mail / `Email Auth` {#status-v02-manual}

正本: [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7.1

**共通前提:** 上記 [§ Mail Local](#status-mail-local) と同様 (マッピング・ Mail チャネル)。v0.2-2 / v0.2-3は **`Email Auth` プラグイン有効** が追加前提 (未導入時は v0.2-2は **保留**、v0.2-3は不可)。**有効・設定未完了 (localhost)** でも v0.2-2 / v0.2-3は実施可 — [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7.1–7.2。実行順は [§ 残り手動項 — 推奨順](#status-mail-local-runbook)。

* [x] Manual (v0.2-1): `Email Auth` **無**、Mail チャネル、マッピングフォーム送信成功 —2026-11-03Local Pass (Mailpit、[#3 / v0.2-1](./test-results.md#test-v02) と同一)
* [x] Manual (v0.2-2): `Email Auth` **有**、Mail チャネル、マッピングフォーム送信成功 —2026-11-03Local Pass (Email Auth **有効・ Local 未設定**、Mailpit、[#6 セッション](./test-results.md#記録))
* [x] Manual (v0.2-3): パスワードリセット成功 (D-005 / 0.1.1回帰) —2026-11-03Local Pass (Mailpit、リセット **リンク未使用**。[test-results.md §記録](./test-results.md#記録))
* [x] Manual (v0.2-4): `channel=saas` — タブ1の Mail 認証案内 **非表示** —2026-11-03Local Pass (SaaS ラジオ・**未保存**、`serviceTabScript`。Mail 選択時は案内表示を同セッションで確認)

##### SMF skip (HTTP Local) {#status-smf-local}

正本: [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)

* [ ] Manual: 未マッピングフォーム — 本プラグイン `skip` せず SMF デフォルト管理者宛 — **再現可** (タブ3未マッピングの公開 SMF フォーム。`FormRegistry::isMappedForm` false → [filterAdministratorSkip](../includes/SnowMonkey/MailerHook.php) が `skip` を true にしない)

---

### HTTPS 環境でのみテスト可能なこと {#status-https}

**前提 (分類):** 実 API ・本物プロジェクトへの **副作用** と秘密の扱いのため、Pass 記録は **HTTPS ステージング相当** を正本とする。**実装上** Local HTTP でも outbound HTTPS が通れば接続検証・起票 POST は **動き得る** (サイト URL が HTTP であること自体はコード上の禁止ではない)。**必須:** 有効な Backlog / Asana 資格情報、タブ1 `channel=saas` 保存、種別行のプロジェクト ID (Backlog は課題種別 / 優先度)。

#### 自動テストで可能なこと {#status-https-automated}

現時点では **該当なし**。実 API 向け `wp_remote_*` の Pass は下記手動が正本 (Unit の `SaasConnectionProbe` は HTTP スタブで [§ 非 HTTPS · 自動](#status-local-automated) に含む)。

#### 自動テストでは不可能なこと (手動) {#status-https-manual}

##### タブ1、接続検証 (実 API) {#status-verify-https}

正本: [saas_connection_spec.md](./saas_connection_spec.md) §接続検証

* [ ] Manual (HTTPS): Backlog — **接続を検証** → HTTP `200`、`settings_errors` 成功 (秘密をログに出さない) — **再現可** (タブ1POST 検証のみ。Unit はスタブ: [SaasConnectionProbeTest](../tests/Unit/Support/SaasConnectionProbeTest.php))
* [ ] Manual (HTTPS): Asana — 同上 (`/api/1.0/users/me`) — **再現可**

##### SaaS 起票 E2E {#status-saas-dispatch-https}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md)、Service [testing_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/testing_spec.md)

* [ ] Manual (HTTPS): `channel=saas` + Backlog — SMF 送信 → HTTP **201** + `external_ticket_id`、ログに body なし — **再現可** (Mail チャネル・空 `mail_to` では不可。マッピング + 種別 SaaS 宛先必須)
* [ ] Manual (HTTPS): `channel=saas` + Asana — 同上 — **再現可**

## SMF 添付 (記録)

| 項目 | 値 |
| --- | --- |
| SMF semver | 13.0.0 |
| クラス | `Snow_Monkey\Plugin\Forms\App\Model\MailParser` |
| メソッド | `get_attachments( administrator_email_body )` |

## 補足

* **実装％** (機能一覧) は、当該§チェックリストの `[x]` / 全項 (仕様分割行を除く)。Runner **SaaS E2E** 未記録の行は0% 手動に寄せる。
* **非 HTTPS** に含めないもの: 実 Backlog / Asana への認証 GET ・起票 POST の **手動 Pass** (資格情報・プロジェクト副作用のため [§ HTTPS · 手動](#status-https-manual) 正本)。
* Unit の `SaasConnectionProbe` は [tests/stubs/wordpress.php](../tests/stubs/wordpress.php) で **TLS 不要**。`RunnerMailHeadersTest` は `wp_mail` スタブで D-015を検証。
* **手動項目の再現性 (2026-11-01):** 上記 § Mail / v0.2 / SMF skip / HTTPS 見出しの各項は、**管理 UI の `<select>` やタブ再読込により再現不能なものはない** (タブ3不存在 `inquiry_type` 保存のみ [N/A + Unit](#status-admin-local))。送信系は **タブ1チャネル** と **マッピング** の前提を満たすこと。Runner 実行時の `inquiry_type_invalid` は D-004 (マッピング中種別の削除拒否) により **通常 UI では起きにくい** — 手動項目には含めない。
* Service 側進捗: [Service status.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/status.md)

## 関連

* [test-results.md](./test-results.md)
* [decisions.md](./decisions.md)
