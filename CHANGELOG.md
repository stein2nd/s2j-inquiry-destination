# S2J Inquiry Destination - CHANGELOG

## unreleased

## 0.2.3 - 2026-11-20

### Changed

* WordPress プラグイン版 **0.2.3** (`Version` / `S2J_INQUIRY_DESTINATION_VERSION`)。`package.json` 同期
* CI: GitHub Actions — `ubuntu-24.04`、`actions/checkout` / `setup-node` / `upload-artifact` **v5**、Lint / dist の Node **24** (Node v20非推奨アノテーション解消)

### Notes

* WordPress プラグイン実装の変更点は **0.2.2** (2026-11-05) に集約。要点: **D-017** (POST/GET busy、タブリップスピナー、submitter 遅延 disable)、タブ1 **Mail 保存** (`save-flag` / dispatch 再描画)、タブ2 **Bulk 削除** UX、表レイアウト、**FormOptionsTest**、Local **Mail / v0.2** 手動 Pass 記録 ([test-results.md](./docs/test-results.md))

## 0.2.2 - 2026-11-05

### Tests

* `FormOptionsTest` — 存在しない `inquiry_type` の POST を拒否 (タブ3手動 N/A の代替、`test-results.md` #10)

### Changed

* 管理 UI (全タブ): POST 送信時にコア `.spinner` + submit 無効化。アクティブ **タブリップ** 内スピナー + ステータス行 (`tabSubmitMessage`、`adminSubmitBusyScript`、`settings-busy.css`)
* 管理 UI (タブ1–3): 保存・タブ1 **接続検証** を `s2j-inquiry-destination-submit-with-spinner` で統一
* 管理 UI: タブ (`nav-tab`) 切替 GET 時にタブリップ上スピナー + 読み込み中ステータス行
* i18n: `tabSubmitMessage` / `tabLoadingMessage` (`s2jInquiryDestinationAdmin`)
* docs: v0.2.1以降の管理 UI 修正 (form `action`、nonce D-016、通知の二重表示を回避、タブ3 `required` 廃止・狭幅 CSS) を [admin_ui_spec.md](./docs/admin_ui_spec.md) / [decisions.md](./docs/decisions.md) / [architecture.md](./docs/architecture.md) に反映
* docs: タブ2 (SaaS > Backlog) **4段階行レイアウト** (1489/785/558/557px) を仕様確定 — [admin_ui_spec.md](./docs/admin_ui_spec.md) §タブ2、D-008、[types-tab.css](./assets/admin/types-tab.css) ヘッダー
* docs: タブ2 (SaaS > Asana) **≤782px Z 字** (`--asana` wrap) を仕様、D-008に反映。[test-results.md](./docs/test-results.md) #2h / #2c-sort
* docs: タブ1 `saas_product` 切替が種別 option をリセットしない旨を [admin_ui_spec.md](./docs/admin_ui_spec.md) §タブ2に明記 (#2i)
* docs: タブ2の一括削除 (`confirm`、`types_deleted` 通知、`typesTabScript`、POST ルーティング) を [admin_ui_spec.md](./docs/admin_ui_spec.md) / [decisions.md](./docs/decisions.md) D-008 / [architecture.md](./docs/architecture.md) に反映
* docs: D-017 (POST / タブ GET の wait フィードバック) を [admin_ui_spec.md](./docs/admin_ui_spec.md) §読み込み・送信中 / [decisions.md](./docs/decisions.md) / [architecture.md](./docs/architecture.md) に反映
* docs: タブ3「不存在 `inquiry_type` 保存」手動テストを N/A (select UI) と明記 — サーバー検証は [FormOptions](../includes/Options/FormOptions.php) に維持
* docs: [status.md](./docs/status.md) § Mail / v0.2 / SMF / HTTPS 手動項の再現性注記 (2026-11-01照合)
* docs: Local 手動 Pass 記録 (Mail #3/#6/#9、v0.2-1—4) と Mail 系 [runbook](./docs/status.md#status-mail-local-runbook) — [test-results.md](./docs/test-results.md) §記録

### Fixed

* 管理 UI (D-017): POST busy で **submitter 自体** を即 `disabled` すると submit **name** が POST から落ち保存、Bulk がスキップされる — submitter 以外を即 disable、submitter は次 tick で disable (`setNavTabBusy` でタブリップ表示)
* 管理 UI (タブ1): SaaS → Mail で **サービス設定の保存** 後も送信チャネル SaaS が再表示される問題 — 上記に加え `#s2j-inquiry-destination-service-save-flag`、保存成功時は option の dispatch を描画、Mail 時 JS で `saas_product` ラジオをクリア
* 管理 UI (タブ2): 一括 **削除** に `confirm` 意思確認がなく、成功通知が「保存されました」のままだった問題 — 削除専用メッセージに分離
* 管理 UI (タブ2で SaaS > Asana): ≤782px で表示名・プロジェクト ID が消える問題 — Mail 同型 Z 字 (`--asana` wrap)
* 管理 UI (タブ2で SaaS > Backlog): 1489 / 785 / 558 / 557px の4段階行レイアウト (6列1行〜 ID 折り返し〜全面縦積み) ([types-tab.css](./assets/admin/types-tab.css)、[admin_ui_spec.md](./docs/admin_ui_spec.md) §タブ2)
* 管理 UI (タブ1): 秘密欄が **未設定** のとき「空欄で維持」ガイドを出さず、検証・初回保存には入力が必要と明示 ([admin_ui_spec.md](./docs/admin_ui_spec.md) § API キー)
* 管理 UI (タブ1): 接続検証エラー後に送信チャネルが保存済み Mail のまま表示される問題 — 検証/保存 POST 直後は POST の channel / SaaS 製品を UI に反映
* 管理 UI (タブ2/3): `WP_List_Table` の bulk 用 `_wpnonce` が保存用 nonce を上書きし「リンクの期限切れ」になる問題 — 保存 nonce を `_wpnonce_s2j_*` に分離。Bulk 適用時は `bulk-*` nonce を検証
* 管理 UI: 保存 POST の `action` に `page` / `tab` クエリーを含め、**設定 > 一般** に戻ってしまう問題を修正
* 管理 UI: 保存成功メッセージが二重表示 — `options-head.php` と重複していた `settings_errors()` 呼び出しを削除
* 管理 UI (タブ3): 未マッピングフォーム行の HTML5 `required` により保存がブロックされる問題 — 必須判定は [FormOptions](../includes/Options/FormOptions.php) サーバー側のみ
* 管理 UI (タブ3): 狭幅で `data-colname` をコントロール上に縦積み — 長い翻訳ラベルが省略されない ([forms-tab.css](../assets/admin/forms-tab.css))

## 0.2.1 - 2026-10-30

### Changed

* docs: [status.md](./docs/status.md): 100% チェックリストを **非 HTTPS / HTTPS**、各 **自動 → 手動** に再構成。v0.2実装済み項目を反映

### Tests

* `RunnerMailHeadersTest` — D-015 (`From:` 非載せ、Reply-To / 無効メール時スキップ)。Unit **23** 件
* `tests/stubs/wordpress.php` — `wp_mail` 等スタブを追加

## 0.2.0 - 2026-10-27

### Added

* 管理 UI (タブ1): Mail チャネル時の **`Email Auth`** 導入案内 ([mail_deliverability_spec.md](./docs/mail_deliverability_spec.md)、D-014)

### Changed

* `Dispatch\Runner`: Mail 経路の `$headers` から **`From:` を削除** — Transport (`wp_mail_from` / `Email Auth`) に委譲 (D-015)
* Composer: `s2j/inquiry-destination-service` **^0.4.0** (Transport 境界、[Service transport_boundary_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/transport_boundary_spec.md))
* 管理 UI: Mail 認証案内の表示名を **`email-auth` の Plugin Name** から取得 (未インストール時のみフォールバック文言)

### Fixed

* PHPStan: `SettingsPage` の `require_once` 解決用に `phpstan/fixtures/wp-admin/includes/plugin.php` を追加 (`npm run lint:php`)

### Tests

* `ServiceIntegrationTest`: Mail 材料が Service v0.4の **4キーのみ** であることを assertion

## 0.1.1 - 2026-10-26

### Fixed

* `MailerHook`: Core の `wp_mail_succeeded` (引数1つの action) を filter の2引数で受けていたため、パスワードリセット等の通常 `wp_mail` で fatal になる問題 — 自動返信の失敗検知を `wp_mail_failed` (`onWpMailFailed`) に移行 (D-005)

## 0.1.0 - 2026-10-25

### Added

* PHPUnit: `SaasOptionsTest` (`resolveForConnectionTest`)、`SaasProductCatalogTest` (`choicePhrase`)、未知 `saas_product` の `DispatchOptionsTest` — Unit **21** 件

### Changed

* docs: [status.md](./docs/status.md) — 機能一覧・100% 完了条件 (非 HTTPS 自動 / 手動、HTTPS 外部結合)、[test-results.md](./docs/test-results.md) — GitHub Actions Pass 表

### Fixed

* Unit テスト: `PHPUnit\Framework\Assert` 静的 assertion (`DispatchOptionsTest` / `ServiceIntegrationTest` 等) — IDE 解析向け

## 0.0.1 - 2026-10-24

### Added

* SaaS dispatch: `DispatchOptions` (`channel`, `saas_product`)、製品別 `SaasOptions`、種別行の Backlog / Asana フィールド、`DestinationBuilder`、`Runner` の `wp_remote_request` + `map_dispatch_result` (D-011 / D-012)
* 管理 UI: タブ1の SaaS 接続、**接続を検証** (`SaasConnectionProbe` / `resolveForConnectionTest`)、**チャネル切替 JS** (`serviceTabScript`)、タブ2の SaaS 列と行追加 JS、D-010 (mail 時の接続保存ガード)
* `SaasProductCatalog::choicePhrase()` — 管理画面・エラー文の製品名リスト (ハードコード回避)
* PHPUnit: `DispatchOptionsTest`, `DestinationBuilderTest`, `SaasConnectionProbeTest`, `ServiceIntegrationTest`, `TypeOptionsTest`, `ServiceGateTest` 等 (`tests/Unit/`)

* docs: 管理 UI を3タブ + `WP_List_Table` (メディアライブラリ型) に再定義 ([admin_ui_spec.md](./docs/admin_ui_spec.md)、`D-007–D-010`)。実装追随 (タブ1の SaaS ガード、タブ2/3の CSS と列構成)
* 管理 UI 実装: 3タブ、`TypesListTable` / `FormsListTable`、`DispatchOptions` / `SaasOptions`
* プラグイン初版: 設定 UI (種別、宛先 / SMF フォームマッピング)、SMF フック、`Dispatch\Runner`、相関 ID ログ、自動返信のみ失敗時の完了維持
* `ServiceGate`: vendor に Service がない場合は送信のみ無効 (dist zip 同梱時は有効)
* dist CI: `npm run dist` + `.github/workflows/dist-zip.yml` (Alliance Manager / Slug Generater 同型、`vendor/` 同梱)
* PHPUnit: `ServiceGate`、Service 連携 smoke、問い合わせタイプ slug / `sanitizeFromPost` (`TypeOptionsTest`)、`channel=mail` 時の SaaS POST 無視 (`SaasOptionsTest`)
* タブ1: `channel=mail` 時に SaaS 接続欄を disabled、保存ガード (D-010)
* タブ2: 狭幅 Z 字レイアウト (`assets/admin/types-tab.css`)、slug 列警告、List Table 実装詳細
* タブ3: フィールド名列の入力を列幅内に収める (`assets/admin/forms-tab.css`、`FormsListTable` 表クラス)
* `.textlintignore` で `readme.txt` を docs linter 対象外に
* 開発: `phpstan/phpstan-phpunit` を追加し PHPStan の解析対象に `tests/` を含める (PHPUnit 型、assertion)
* GitHub Actions: `.github/workflows/phpunit.yml` (PHP v8.3、`composer test`)、`.github/workflows/docs-lint.yml` (`npm run lint:docs`)、`.github/workflows/dist-zip.yml` (タグ `v*` / 手動 dispatch · `vendor/` 同梱 zip)

### Changed

* Packagist `s2j/inquiry-destination-service` **^0.3.0** (Backlog / Asana)
* docs: [Inquiry Destination Service v0.3](https://github.com/stein2nd/s2j-inquiry-destination-service) に合わせ `docs/` を更新 — `saas_connection_spec`, `dispatch_execution_spec`, `data_dictionary`, `admin_ui_spec`, D-011– D-012, [status.md](./docs/status.md)

* **Requires PHP v8.3** 以降に引き上げ ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/) に合わせる)。`composer.json` / プラグインヘッダー / `readme.txt` / docs / CI (PHPUnit ・ dist zip: PHP v8.3)
* CI: PHPUnit ジョブは **Composer のみ** (`npm run phpunit:wp-env` 等は package.json 未定義のため呼ばない)
* Packagist `s2j/inquiry-destination-service` **^0.2.0** (可変 `inquiry_type` スラッグ)。リポジトリ内同梱 Service は削除
* 管理画面: 問い合わせタイプの行追加・削除、SMF フォーム一覧の絞り込み
* `Runner` を Service の `MailMaterial` 型に合わせて整理
* SMF 検証下限を **13.0.0** に固定 (README / [snow_monkey_forms_spec.md](./docs/snow_monkey_forms_spec.md))
* PHPCS: `includes/` は PSR-12、ブートストラップ PHP は WPCS ([phpcs.xml.dist](./phpcs.xml.dist) / [phpcs.wpcs.xml.dist](./phpcs.wpcs.xml.dist))
* [docs/status.md](./docs/status.md) / [docs/test-results.md](./docs/test-results.md) を実装状態に更新
* `npm run lint`: `src/` 未作成時も ESLint / Stylelint が失敗しないよう設定 ([eslint.config.js](./eslint.config.js)、`--no-error-on-unmatched-pattern` / `--allow-empty-input`)
* `npm run build:dev` / `build:production`: 初版 (PHP 設定 UI のみ) は Vite 連鎖の代わりに `tools/dist/ensure-dist-assets.mjs` で `dist/` placeholder を生成
* `.vscode/settings.json`: Intelephense が `vendor/` (PHPUnit 等) を参照するよう `includePaths` を設定

### Fixed

* `TypesListTable::column_slug` の multi-line `if` を PSR-12準拠に修正 (`npm run lint:php` / PHPCS)

## 0.0.1 - 2026-10-15

### Changed

* npm 依存モジュールを更新した (`ncu` / `ncu -u`)。`react` v19.3.1、`rollup` v4.64.7、`@s2j/docs-linter` v1.0.29、`@typescript-eslint/*` v8.71.2等
* textlint の許可パターンに `#status-*` アンカー ID を追加
* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘7件 (CVE-2026-93687) は残す。

## 0.0.1 - 2026-10-11

### Changed

* npm 依存モジュールを更新した (`ncu` / `ncu -u`)。`@wordpress/components` v42、`@wordpress/element` v9、Vite v8.3.4、`@s2j/docs-linter` v1.0.27等
* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘7件 (CVE-2026-93687) は残す。

## 0.0.1 - 2026-10-08

### Changed

* `docs_mod/specs.md` の条件と列挙の言い方をそろえた。条件は「場合」、列挙の案内は「下記」にした。

## 0.0.1 - 2026-10-05

### Changed

* `docs_mod/specs.md` を改訂した。種別1行の宛先は、同じ1通の To として複数書ける。表の上に、メーリングリストか共有受信箱を優先する文を常時出す。
* 送信失敗のあと、そのリクエストの送信値を入力画面の欄に戻す。再送の材料は戻した欄だけとし、ファイルは選び直す。
* 管理者宛の成功後に自動返信だけが失敗した場合は、完了画面に進む。完了文に、確認メールを送れなかったことを一文足す。
* ログは `error_log` だけに書く。`WP_DEBUG` が off の場合も書く。
* SaaS のエンドポイントと API キーはサイトに1組。環境変数か `wp-config.php` の定数を先に読み、ない場合だけオプションを使う。画面は「設定済み」と「未設定」だけを出す。
* Composer では `s2j/inquiry-destination-service` を Packagist のパッケージ名だけで require する。

## 0.0.1 - 2026-10-04

### Added

* プラグイン仕様ドラフトを `docs_mod/specs.md` に記載
* npm v12以降向けに `.npmrc` の `allow-git=all` と `package.json` の `allowScripts` を追加

### Changed

* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘7件 (CVE-2026-93687) は残す。
