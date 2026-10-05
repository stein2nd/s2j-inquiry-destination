# S2J Inquiry Destination - CHANGELOG

## unreleased

## 0.0.1 - 2026-10-05

### Changed

* `docs_mod/specs.md` を改訂した。種別1行の宛先は、同じ1通の To として複数書ける。表の上に、メーリングリストか共有受信箱を優先する文を常時出す。
* 送信失敗のあと、そのリクエストの送信値を入力画面の欄に戻す。再送の材料は戻した欄だけとし、ファイルは選び直す。
* 管理者宛の成功後に自動返信だけが失敗したときは、完了画面に進む。完了文に、確認メールを送れなかったことを一文足す。
* ログは `error_log` だけに書く。`WP_DEBUG` が off のときも書く。
* SaaS のエンドポイントと API キーはサイトに1組。環境変数か `wp-config.php` の定数を先に読み、ない場合だけオプションを使う。画面は「設定済み」と「未設定」だけを出す。
* Composer では `s2j/inquiry-destination-service` を Packagist のパッケージ名だけで require する。

## 0.0.1 - 2026-10-04

### Added

* プラグイン仕様ドラフトを `docs_mod/specs.md` に記載
* npm v12以降向けに `.npmrc` の `allow-git=all` と `package.json` の `allowScripts` を追加

### Changed

* 開発依存の `braces` v3.0.3 (GHSA-vfj7-8cjw-p6xm: 深くネストしたパターンで Node.js プロセスが終了する) は修正版が未公開のため、深刻度 high の指摘7件 (CVE-2026-93687) は残す。
