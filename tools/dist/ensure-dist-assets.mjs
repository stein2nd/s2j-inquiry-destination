import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';

const repoRoot = resolve(process.cwd());
const distDir = join(repoRoot, 'dist');
const jsDir = join(distDir, 'js');
const cssDir = join(distDir, 'css');

if (process.env.FLUSH_DIST === 'true') {
	rmSync(distDir, { recursive: true, force: true });
}

for (const dir of [distDir, jsDir, cssDir]) {
	if (!existsSync(dir)) {
		mkdirSync(dir, { recursive: true });
	}
}

const targets = ['admin', 'gutenberg', 'classic', 'frontend'];
for (const target of targets) {
	writeFileSync(join(jsDir, `s2j-inquiry-destination-${target}.js`), '/* placeholder */\n', 'utf8');
	writeFileSync(join(cssDir, `s2j-inquiry-destination-${target}.css`), '/* placeholder */\n', 'utf8');
}

process.stdout.write('Prepared placeholder dist/ assets.\n');
