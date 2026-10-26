/**
 * 初版は管理 UI が PHP のみ。JS を追加する場合は dist/js/s2j-inquiry-destination-settings.js
 * (npm `build:*:admin` → Vite target `settings`)。wp-plugin-spec I18N §2.3。
 * 現状の dist placeholder は tools/dist/ensure-dist-assets.mjs。
 */
import { defineConfig } from 'vite';

export default defineConfig({});
