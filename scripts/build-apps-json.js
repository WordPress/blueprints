import fs from 'node:fs';
import path from 'node:path';

import { listJsonFiles, readJson } from './lib/json-validation.js';
import { resolveVendoredIconPath } from './lib/raw-github-url.js';

const BLUEPRINTS_DIR = 'blueprints';
const OUTPUT_PATH = 'apps.json';
const THIS_REPO_TRUNK_RAW_URL =
	'https://raw.githubusercontent.com/wordpress/blueprints/trunk';

function isAppBlueprint(meta) {
	return Array.isArray(meta?.categories) && meta.categories.includes('Apps');
}

function loadAppMeta(blueprintDir) {
	const appMetaPath = path.posix.join(blueprintDir, 'app-meta.json');
	return fs.existsSync(appMetaPath) ? readJson(appMetaPath) : {};
}

/**
 * A catalog consumer reads apps.json, not app-meta.json — so any `icon`
 * that names an upstream plugin/theme repository is resolved to the local
 * vendored copy the icon sync workflow keeps in this Blueprint's directory.
 */
function resolveCatalogIcon(appMeta, blueprintDir) {
	const vendoredPath = resolveVendoredIconPath(appMeta.icon, blueprintDir);
	if (!vendoredPath) {
		return appMeta;
	}

	return { ...appMeta, icon: `${THIS_REPO_TRUNK_RAW_URL}/${vendoredPath}` };
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
		index[blueprintPath] = resolveCatalogIcon(appMeta, blueprintDir);
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
