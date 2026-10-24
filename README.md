# S2J Inquiry Destination

問い合わせの送信先を差し替える WordPress プラグインです。フォームは [Snow Monkey Forms](https://wordpress.org/plugins/snow-monkey-forms/) で作ります。判断の正は [S2J Inquiry Destination Service](https://github.com/stein2nd/s2j-inquiry-destination-service) にあります。

仕様の正本は [docs/specs.md](docs/specs.md) です。改訂案の起草は [docs_mod/README.md](docs_mod/README.md) を参照してください。

Requires PHP: **8.3** 以降 ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/))。

Snow Monkey Forms の検証下限バージョン: **13.0.0** ([docs/snow_monkey_forms_spec.md](docs/snow_monkey_forms_spec.md) §前提と同値)。

## Composer (開発・配布)

計算規則は Packagist の [s2j/inquiry-destination-service](https://packagist.org/packages/s2j/inquiry-destination-service) **^0.3.0** (Mail + Backlog / Asana) です。接続・管理 UI は [docs/saas_connection_spec.md](docs/saas_connection_spec.md)、[docs/admin_ui_spec.md](docs/admin_ui_spec.md)。実装状況は [docs/status.md](docs/status.md)。

```bash
composer install
```

配布 zip は `npm run dist`(`vendor/` 同梱)。CI は [.github/workflows/dist-zip.yml](.github/workflows/dist-zip.yml) を参照してください。
