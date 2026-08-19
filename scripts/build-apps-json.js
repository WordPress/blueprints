import fs from 'node:fs';
import path from 'node:path';

import { listJsonFiles, readJson } from './lib/json-validation.js';

const BLUEPRINTS_DIR = 'blueprints';
const OUTPUT_PATH = 'apps.json';

// app-meta.json keys that only drive our own tooling (iconSource tells the
// icon sync workflow where a vendored icon comes from), and are of no use
// to a catalog consumer reading apps.json.
const APP_META_BUILD_ONLY_KEYS = new Set(['iconSource']);

function isAppBlueprint(meta) {
	return Array.isArray(meta?.categories) && meta.categories.includes('Apps');
}

function loadAppMeta(blueprintDir) {
	const appMetaPath = path.posix.join(blueprintDir, 'app-meta.json');
	return fs.existsSync(appMetaPath) ? readJson(appMetaPath) : {};
}

function appMetaForIndex(appMeta) {
	return Object.fromEntries(
		Object.entries(appMeta).filter(
			([key]) => !APP_META_BUILD_ONLY_KEYS.has(key)
		)
	);
}

function buildAppsIndex() {
	const blueprintPaths = listJsonFiles(BLUEPRINTS_DIR, true)
		.filter((file) => path.basename(file) === 'blueprint.json')
		.sort();

	const index = {};
	for (const blueprintPath of blueprintPaths) {
		const { meta = {} } = readJson(blueprintPath);
		if (!isAppBlueprint(meta)) {
			continue;
		}

		const blueprintDir = path.posix.dirname(blueprintPath);
		const appMeta = { ...meta, ...loadAppMeta(blueprintDir) };
		index[blueprintPath] = appMetaForIndex(appMeta);
	}

	const sortedEntries = Object.entries(index).sort(([, a], [, b]) => {
		const titleA = a.title || '';
		const titleB = b.title || '';
		return titleA < titleB ? -1 : titleA > titleB ? 1 : 0;
	});

	fs.writeFileSync(
		OUTPUT_PATH,
		JSON.stringify(Object.fromEntries(sortedEntries), null, 2) + '\n'
	);
	return sortedEntries.length;
}

const appCount = buildAppsIndex();
console.log(`Wrote ${OUTPUT_PATH} (${appCount} apps)`);
