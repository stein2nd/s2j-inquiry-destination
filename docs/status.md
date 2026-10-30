# S2J Inquiry Destination - 実装状況

最終更新: 2026-10-28 (v0.2 コード反映、Unit **23**)

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
| Service **v0.4.0** (Transport 境界 + v0.3の SaaS) | 組み込み済 |
| Dispatch Runner → Service 公開4関数 | 実装済、**Mail + SaaS HTTP** ([includes/Dispatch/Runner.php](../includes/Dispatch/Runner.php)) |
| Service 同梱 dist zip | `npm run dist` + [dist-zip.yml](../.github/workflows/dist-zip.yml) |

## 機能一覧

**完了条件列:** 100% 到達用チェックリスト (1行 ≒ PHPUnit の1ケース、CI の1ステップ、または手動確認 1項)。`[x]` = 満たし済み、`[ ]` = 未。正本 spec は各§リンク。

**環境の分け方:** **非 HTTPS** = サイトを `http://` (Local 等) で動かしたまま完結できる項。**HTTPS** = 実 SaaS API、本番相当 URL、TLS 前提の外部結合 ([§ HTTPS 環境](#status-https))。

**§ 内の並び:** 各環境ブロックで **自動テスト → 手動** の順。

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (100%) |
| --- | --- | --- | --- |
| 仕様分割 (`docs/`) | 確定 | — | [§ 非 HTTPS · 自動](#status-local-automated) · 仕様分割 |
| Service 組み込み (^0.4.0) | 実装済 | 100 | [§ Service 組み込み](#status-composer-service) (3/3) |
| Options サニタイズ (dispatch / SaaS / types) | 実装済 | 100 | [§ Options](#status-options) (10/10) |
| 宛先レコード (`DestinationBuilder`) | 実装済 | 100 | [§ DestinationBuilder](#status-destination) (1/1) |
| Service 連携 (Unit) | 実装済 | 100 | [§ Service 連携](#status-service-integration) (3/3) |
| Runner Mail ヘッダー (D-015、Unit) | 実装済 | 100 | [§ Runner Mail](#status-runner-mail) (2/2) |
| 接続検証 Probe (Unit、HTTP スタブ) | 実装済 | 100 | [§ SaasConnectionProbe](#status-saas-probe) (3/3) |
| SaaS 製品カタログ | 実装済 | 100 | [§ SaasProductCatalog](#status-saas-catalog) (3/3) |
| Runner (SaaS HTTP 起票) | コード済 / E2E 未 | 0 | [§ HTTPS · 手動](#status-https-manual) |
| 品質ゲート (PHPUnit / PHPStan / PHPCS / docs) | 実装済 | 100 | [§ 品質ゲート](#status-quality) (6/6) |
| dist zip CI | 実装済 | 100 | [§ 配布](#status-dist) (2/2) |
| 管理 UI (3タブ + タブ1の JS) | 実装済 / 手動未 | 0 | [§ 非 HTTPS · 手動](#status-local-manual) · 管理 UI (0/6) |
| Mail 送信、ログ、SMF 写像 | コード済 / 手動未 | 0 | [§ 非 HTTPS · 手動](#status-local-manual) · Mail (0/6)、SMF skip (0/1) |
| v0.2の Mail / `Email Auth` | コード済 / smoke 未 | 0 | [§ v0.2 手動](#status-v02-manual) (0/4) |
| SaaS 実 API (検証 + 起票) | 未記録 | 0 | [§ HTTPS · 手動](#status-https-manual) (0/4) |

## v0.2 (Mail 配送・認証) サマリー {#status-v02}

正本: [mail_deliverability_spec.md](./mail_deliverability_spec.md)、[decisions.md](./decisions.md) D-013– D-015。証跡: [archive/impl-v0.2-mail-auth/](./archive/impl-v0.2-mail-auth/)。

| 項目 | 状態 |
| --- | --- |
| spec (`docs/`) | **確定** (2026-10-27) |
| タブ1 `Email Auth` 案内 (Plugin Name 動的) | **実装済** |
| Runner D-015 (`From:` 削除) | **実装済** — Unit [§ Runner Mail](#status-runner-mail) |
| Composer **^0.4.0** | **実装済** (lock `v0.4.0`) |
| 手動 smoke (v0.2-1–4) | 未 — [test-results.md §v0.2](./test-results.md#test-v02)、[§ v0.2 手動](#status-v02-manual) |

## 100% 完了条件チェックリスト

### 非 HTTPS 環境でテスト可能なこと {#status-local}

WordPress を **HTTP** (例: Local `http://…`) でホストしたまま実行できる項。外向き **実 SaaS API** は [§ HTTPS 環境](#status-https) に分離する。

#### 自動テストで可能なこと {#status-local-automated}

##### 仕様分割 {#status-spec-docs}

* [x] CI: `docs-lint` が `docs/**` 等を lint ([docs-lint.yml](../.github/workflows/docs-lint.yml))
* [x] 正本が `docs/`、起草が `docs_mod/` ([governance/documentation_governance.md](./governance/documentation_governance.md))

##### Service 組み込み {#status-composer-service}

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

* [x] Test: `SaasConnectionProbeTest::test_backlog_success_on_http_200` — URL / `apiKey` クエリ
* [x] Test: `SaasConnectionProbeTest::test_asana_failure_on_http_401` — デフォルト base + Bearer
* [x] Test: `SaasConnectionProbeTest::test_network_error` — `WP_Error` 経路

##### SaasProductCatalog {#status-saas-catalog}

正本: [includes/Support/SaasProductCatalog.php](../includes/Support/SaasProductCatalog.php)

* [x] Test: `SaasProductCatalogTest::test_labels_include_supported_slugs`
* [x] Test: `SaasProductCatalogTest::test_is_supported`
* [x] Test: `SaasProductCatalogTest::test_choice_phrase_joins_supported_product_labels`

##### 品質ゲート {#status-quality}

正本: [test-results.md](./test-results.md) 段階3

* [x] Local: `composer test` — Unit **23** 件全緑 (`tests/Unit/`)
* [x] Local: `composer phpstan` — エラー0
* [x] Local: `composer lint:php` — PHPCS (PSR-12 + WPCS 分割) 通過
* [x] CI: [.github/workflows/phpunit.yml](../.github/workflows/phpunit.yml) — PHP **v8.3**、`composer test`
* [x] CI: [.github/workflows/docs-lint.yml](../.github/workflows/docs-lint.yml)
* [x] Local: `npm run lint:docs` (docs-lint と同等)

##### 配布 {#status-dist}

* [x] CI: [dist-zip.yml](../.github/workflows/dist-zip.yml) — タグ `v*` / `workflow_dispatch`
* [x] 記録: artifact zip Pass — [test-results.md §GitHub Actions](./test-results.md#github-actions-main) (2026-10-25 · run [40137154220](https://github.com/stein2nd/s2j-inquiry-destination/actions/runs/40137154220))

#### 自動テストでは不可能なこと (手動) {#status-local-manual}

##### 管理 UI (HTTP Local) {#status-admin-local}

正本: [admin_ui_spec.md](./admin_ui_spec.md)

* [ ] Manual: タブ1–3 各保存 — **当該 option のみ** 更新 (D-007)
* [ ] Manual: タブ1 — `channel=mail` 保存で `s2j_inquiry_destination_saas` が POST から更新されない (D-010)
* [ ] Manual: タブ1 — 画面上 `channel=saas` で SaaS 製品・接続欄・**接続を検証** が有効化 (`serviceTabScript`)。検証 POST で option 不変
* [ ] Manual: タブ2 — Bulk / Views / 検索 / 列ソート (D-008)
* [ ] Manual: タブ3 — 狭幅で `column-fields` がはみ出さない (`forms-tab.css`)
* [ ] Manual: タブ3 — 存在しない `inquiry_type` 保存でエラー

##### Mail、ログ (HTTP Local、MailHog 等) {#status-mail-local}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md)、[logging_spec.md](./logging_spec.md)

* [ ] Manual: 写像済みフォーム送信 — To = 種別 `mail_to`、Reply-To = 訪問者、**From は Runner `$headers` に載せない** (D-015)
* [ ] Manual: 空宛先種別 — systemerror、相関 ID、完了しない
* [ ] Manual: `error_log` 1行 — PII なし、`correlation_id` あり
* [ ] Manual: 自動返信のみ失敗 — 完了維持 + Notice + `autoreply_failed` ログ
* [ ] Manual: 未写像 SMF コントロール — 管理者本文末尾に `{name}: {value}`
* [ ] Manual: 複数 `mail_to` — `wp_mail` 1回・同一 To 行

##### v0.2 Mail / `Email Auth` {#status-v02-manual}

正本: [mail_deliverability_spec.md](./mail_deliverability_spec.md) §7.1

* [ ] Manual (v0.2-1): `Email Auth` **無**、Mail チャネル、写像フォーム送信成功
* [ ] Manual (v0.2-2): `Email Auth` **有**、Mail チャネル、写像フォーム送信成功
* [ ] Manual (v0.2-3): パスワードリセット成功 (D-005 / 0.1.1 回帰)
* [ ] Manual (v0.2-4): `channel=saas` — タブ1の Mail 認証案内 **非表示**

##### SMF skip (HTTP Local) {#status-smf-local}

正本: [snow_monkey_forms_spec.md](./snow_monkey_forms_spec.md)

* [ ] Manual: 未写像フォーム — 本プラグイン `skip` せず SMF デフォルト管理者宛

---

### HTTPS 環境でのみテスト可能なこと {#status-https}

**前提:** ステージング (または本番相当) で **サイト URL が HTTPS**、有効な **Backlog / Asana 資格情報**、種別行にプロジェクト ID (Backlog は課題種別 / 優先度) を設定済み。Pass は [test-results.md](./test-results.md) に日付・環境付きで記録する。

#### 自動テストで可能なこと {#status-https-automated}

現時点では **該当なし**。実 API 向け `wp_remote_*` の Pass は下記手動が正本 (Unit の `SaasConnectionProbe` は HTTP スタブで [§ 非 HTTPS · 自動](#status-local-automated) に含む)。

#### 自動テストでは不可能なこと (手動) {#status-https-manual}

##### タブ1、接続検証 (実 API) {#status-verify-https}

正本: [saas_connection_spec.md](./saas_connection_spec.md) §接続検証

* [ ] Manual (HTTPS): Backlog — **接続を検証** → HTTP `200`、`settings_errors` 成功 (秘密をログに出さない)
* [ ] Manual (HTTPS): Asana — 同上 (`/api/1.0/users/me`)

##### SaaS 起票 E2E {#status-saas-dispatch-https}

正本: [dispatch_execution_spec.md](./dispatch_execution_spec.md)、Service [testing_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/testing_spec.md)

* [ ] Manual (HTTPS): `channel=saas` + Backlog — SMF 送信 → HTTP **201** + `external_ticket_id`、ログに body なし
* [ ] Manual (HTTPS): `channel=saas` + Asana — 同上

## SMF 添付 (記録)

| 項目 | 値 |
| --- | --- |
| SMF semver | 13.0.0 |
| クラス | `Snow_Monkey\Plugin\Forms\App\Model\MailParser` |
| メソッド | `get_attachments( administrator_email_body )` |

## 補足

* **実装％** (機能一覧) は、当該§チェックリストの `[x]` / 全項 (仕様分割行を除く)。Runner **SaaS E2E** 未記録の行は 0% 手動に寄せる。
* **非 HTTPS** に含めないもの: 実 Backlog / Asana への認証 GET・起票 POST の **手動 Pass** (資格情報・プロジェクト副作用のため [§ HTTPS · 手動](#status-https-manual) 正本)。
* Unit の `SaasConnectionProbe` は [tests/stubs/wordpress.php](../tests/stubs/wordpress.php) で **TLS 不要**。`RunnerMailHeadersTest` は `wp_mail` スタブで D-015 を検証。
* Service 側進捗: [Service status.md](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/status.md)

## 関連

* [test-results.md](./test-results.md)
* [decisions.md](./decisions.md)
