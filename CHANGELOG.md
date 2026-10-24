# S2J Inquiry Destination - CHANGELOG

## unreleased

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
* プラグイン初版: 設定 UI (種別、宛先 / SMF フォーム写像)、SMF フック、`Dispatch\Runner`、相関 ID ログ、自動返信のみ失敗時の完了維持
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
