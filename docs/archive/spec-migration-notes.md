# 仕様移行メモ (archive)

分割前の単一 `docs_mod/specs.md` (2026-10-04〜05) から **現行 SoT** に移った記録です。実装の正本は [`docs/`](../) の分割 spec です (2026-10-19に `docs/` に移行)。

## 入力復元 (2026-10-05から2026-10-19)

| 旧ドラフト (2026-10-05) | 現行 |
| --- | --- |
| 本プラグインが送信値を入力画面の欄に戻す | **SMF** が systemerror 時に responser 値を保持。プラグインは DB に本文を保存しない |
| エラー文と相関 ID を欄の上に出す | [dispatch_execution_spec.md](../dispatch_execution_spec.md) — 汎用エラー + 相関 ID |

Git 履歴: `docs_mod/specs.md` の2026-10-05改訂履歴を参照。
