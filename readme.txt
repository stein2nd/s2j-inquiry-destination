=== S2J Inquiry Destination ===
Contributors: stein2nd
Tags: snow-monkey-forms, inquiry, mail
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 0.1.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Route Snow Monkey Forms administrator mail through inquiry-type destinations.

== Description ==

This plugin replaces Snow Monkey Forms administrator mail for mapped forms. Inquiry types, mail destinations, and field mapping are configured under Settings > Inquiry destinations.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the distribution zip.
2. Run `composer install --no-dev` in the plugin directory if you deploy from source without the bundled vendor tree.
3. Activate Snow Monkey Forms and this plugin.
4. Configure inquiry types and form mappings in Settings > Inquiry destinations.

== Frequently Asked Questions ==

= Which Snow Monkey Forms version is supported? =

See the plugin README on GitHub for the pinned Snow Monkey Forms semver used in integration testing.

== Changelog ==

= 0.1.1 =
* Fix wp_mail hook so password reset and other core mail no longer fatal; autoreply failure uses wp_mail_failed.

= 0.1.0 =
* SaaS dispatch (Backlog / Asana), tab1 connection verify and channel UI, expanded PHPUnit and docs status matrix.

= 0.0.1 =
* Initial implementation: settings UI, Snow Monkey Forms hooks, mail dispatch via s2j/inquiry-destination-service.
