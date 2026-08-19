import fs from 'node:fs';
import path from 'node:path';

import { getTouchedBlueprintDirectories } from './lib/changed-files.js';
import { resolveVendoredIconPath } from './lib/raw-github-url.js';

const BLUEPRINTS_DIR = 'blueprints';
const MAX_ICON_BYTES = 512 * 1024;

function reportError(file, message) {
	console.error(`::error file=${file}::${message}`);
}

/**
 * List the app-meta.json files to sync.
 *
 * On a pull request we only look at the Blueprints the branch actually
 * touched, so that an unrelated upstream change is never smuggled into
 * somebody else's pull request.
 */
function getAppMetaPaths({ changedOnly }) {
	if (!fs.existsSync(BLUEPRINTS_DIR)) {
		return [];
	}

	const blueprintDirs = changedOnly
		? getTouchedBlueprintDirectories()
		: fs
				.readdirSync(BLUEPRINTS_DIR, { withFileTypes: true })
				.filter((entry) => entry.isDirectory())
				.map((entry) => path.posix.join(BLUEPRINTS_DIR, entry.name));

	return blueprintDirs
		.map((blueprintDir) => path.posix.join(blueprintDir, 'app-meta.json'))
		.filter((appMetaPath) => fs.existsSync(appMetaPath))
		.sort();
}

async function fetchIcon(iconSource) {
	const response = await fetch(iconSource, {
		signal: AbortSignal.timeout(30_000),
	});
	if (!response.ok) {
		throw new Error(`HTTP ${response.status}`);
	}

	const contents = Buffer.from(await response.arrayBuffer());
	if (contents.length === 0) {
		throw new Error('the upstream icon is empty');
	}
	if (contents.length > MAX_ICON_BYTES) {
		throw new Error(
			`the upstream icon is ${contents.length} bytes, over the ${MAX_ICON_BYTES} byte limit`
		);
	}

	return contents;
}

async function syncBlueprint(appMetaPath, { checkOnly }) {
	const blueprintDir = path.posix.dirname(appMetaPath);

	let appMeta;
	try {
		appMeta = JSON.parse(fs.readFileSync(appMetaPath, 'utf8'));
	} catch (error) {
		reportError(appMetaPath, `Invalid JSON: ${error.message}`);
		return { failed: true };
	}

	const { icon } = appMeta;
	const iconPath = resolveVendoredIconPath(icon, blueprintDir);
	if (!iconPath) {
		// A Dashicon/emoji, or a file already vendored with no declared
		// upstream (authored here) — nothing to sync.
		return {};
	}

	let upstream;
	try {
		upstream = await fetchIcon(icon);
	} catch (error) {
		reportError(appMetaPath, `Could not fetch icon ${icon}: ${error.message}`);
		return { failed: true };
	}

	const vendored = fs.existsSync(iconPath)
		? fs.readFileSync(iconPath)
		: null;
	if (vendored && vendored.equals(upstream)) {
		console.log(`Up to date: ${iconPath}`);
		return {};
	}

	if (checkOnly) {
		reportError(
			iconPath,
			`Out of sync with ${icon}. Run \`npm run sync:app-icons\` and commit the result.`
		);
		return { failed: true };
	}

	fs.writeFileSync(iconPath, upstream);
	console.log(`Updated: ${iconPath} from ${icon}`);
	return { updated: iconPath };
}

async function main() {
	const checkOnly = process.argv.includes('--check');
	const changedOnly = process.argv.includes('--changed-only');
	const appMetaPaths = getAppMetaPaths({ changedOnly });
	const updated = [];
	let failed = false;

	for (const appMetaPath of appMetaPaths) {
		const result = await syncBlueprint(appMetaPath, { checkOnly });
		failed = failed || Boolean(result.failed);
		if (result.updated) {
			updated.push(result.updated);
		}
	}

	if (updated.length > 0) {
		console.log(`\nUpdated ${updated.length} icon(s):`);
		for (const iconPath of updated) {
			console.log(`  ${iconPath}`);
		}
	}

	if (failed) {
		process.exit(1);
	}
}

await main();
