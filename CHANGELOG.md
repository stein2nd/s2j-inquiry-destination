# S2J Inquiry Destination - CHANGELOG

## unreleased

## 0.0.1 - 2026-10-04

### Added

* プラグイン仕様ドラフトを `docs_mod/specs.md` に記載
* npm v12以降向けに `.npmrc` の `allow-git=all` と `package.json` の `allowScripts` を追加

### Changed

* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm) は修正版が未公開のため、深刻度 high の指摘7件 (CVE-2026-93687) は残す。
