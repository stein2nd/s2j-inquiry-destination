# S2J Inquiry Destination - ドキュメンテーション・ガバナンス

本ドキュメントは、README と仕様の **整合ルール** を定義します。

共通の要約は [wp-plugin-spec / SPECS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md) §4です。ひな型は [DOCUMENTATION_GOVERNANCE_TEMPLATE.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/DOCUMENTATION_GOVERNANCE_TEMPLATE.md) です。

## 1. Source of Truth

矛盾時は **Service の core/contracts** を正とし、本プラグイン docs、README を追随させます。画面専用の決定 (メニュー名、option キー) は本プラグイン docs が正です。

| 対象 | 正本 |
| --- | --- |
| 検証、Mail 材料、結果写像、不足コード | Inquiry Destination **Service** の `docs/core/` と `docs/contracts/` |
| 公開 PHP API | Service の `docs/interfaces/php_api_spec.md` |
| option キー、Snow Monkey Forms フック、画面文言、From/Reply-To | 本 `docs/` (`data_dictionary.md` / `admin_ui_spec.md` 等) |
| 最短手順 | ルート `README.md` |
| 統合見取り図 | `docs/plugin_spec.md` (要約) |
| 索引 | `docs/specs.md` |

## 2. 用語

* **種別** = `inquiry_type` (登録名 `sales` / `recruit` 等)。画面の表示名 = option `label`。
* **統一ペイロード** / **宛先レコード** / **材料 (material)** — [specs.md](../specs.md) §用語。
* **Mail / mail_to** — Service docs と索引見出しは **Mail** (例: Mail 材料)。プラグインの option、UI、コード識別子は **`mail_to`**。
* **相関 ID** = 1 **試行 (attempt)** (= 管理者宛1回) の `correlation_id`。ログと訪問者向け Notice に出す非 PII (個人を識別できる情報) 識別子。
* **対応フォーム** = `s2j_inquiry_destination_forms` に載る Snow Monkey Forms 投稿。**未対応フォーム** = 載らない投稿 (SMF デフォルト管理者宛のまま)。
* **Runner** = `Dispatch\Runner` のみが Service 公開4関数を呼ぶ ([decisions.md](../decisions.md) D-001)。
* **不足 (deficiency)** = Service `validate_inquiry_dispatch` のコード。訪問者画面にコード文字列を出さない。ログの `failure_code` は [logging_spec.md](../logging_spec.md) (複数時は先頭1件)。
* **systemerror** = SMF の画面種別名 (内部識別子)。日本語説明: **システムエラー画面**。初出以降は `systemerror` 可。
* SMF = Snow Monkey Forms。初出で展開してよい。
* FOP = Functional Object-Oriented Programming (初出で展開)。
* Composer の repository 種別は `path` / `VCS` と書く (日本語「パス」と混同しない)。
* `inquiry_type` の新値は **Service minor 先行**。プラグイン UI だけ先に増やさない ([Service data_dictionary](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/contracts/data_dictionary.md#inquiry_type))。

## 3. Lint

* `@s2j/docs-linter` を SoT とする。
* ローカルと CI で同じ `npm run lint:docs` を使う。
* 対象: `README.md`、`CHANGELOG.md`、`docs/` および `docs_mod/` 配下の Markdown。

## 4. 分割ルール

* 新規仕様は [specs.md](../specs.md) の Why / What / How に分類する。
* 不足コード表、Mail 本文行順など Service 既存 spec の **複製** を避け、リンクする。deficiency コード一覧の正本は [Service validation_spec](https://github.com/stein2nd/s2j-inquiry-destination-service/blob/main/docs/core/validation_spec.md) のみ。
* 実装固定 (Runner 呼び出し、UI 保存等) は [decisions.md](../decisions.md) に集約し、各 spec から参照する。
* [test-results.md](../test-results.md) の各ケースは **正本 spec** にリンクする (Webinar 同型)。

## 5. 改訂フロー (`docs_mod` → `docs`)

* 合意前の起草: **`docs_mod/`**
* 確定後の正本: **`docs/`** (Webinar / Survey 同型)
* 大きな改訂は `docs_mod/` → レビュー → `docs/` に反映
* Service リンクは GitHub 上の `docs/` を指す (作業ツリーと同期している前提)

## 6. archive

[archive/README.md](../archive/README.md) を参照。初版実装イニシアチブ完了時に三点セットをフリーズします。
