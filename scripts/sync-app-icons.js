import fs from 'node:fs';
import path from 'node:path';

import {
	isBlueprintAttachmentPath,
	matchAllowedRawGitHubUrl,
	parseRawGitHubUrl,
} from './lib/raw-github-url.js';

const BLUEPRINTS_DIR = 'blueprints';
const TRUNK_SOURCE = {
	kind: 'trunk',
	repository: 'wordpress/blueprints',
	ref: 'trunk',
};
const MAX_ICON_BYTES = 512 * 1024;

function reportError(file, message) {
	console.error(`::error file=${file}::${message}`);
}

function getAppMetaPaths() {
	if (!fs.existsSync(BLUEPRINTS_DIR)) {
		return [];
	}

	return fs
		.readdirSync(BLUEPRINTS_DIR, { withFileTypes: true })
		.filter((entry) => entry.isDirectory())
		.map((entry) =>
			path.posix.join(BLUEPRINTS_DIR, entry.name, 'app-meta.json')
		)
		.filter((appMetaPath) => fs.existsSync(appMetaPath))
		.sort();
}

/**
 * Resolve the repository-relative file that an app-meta.json `icon` vendors.
 *
 * The icon must be served from this repository's trunk and live inside the
 * Blueprint's own directory, matching the rule validate-pr-blueprints.js
 * applies to Blueprint resource URLs.
 */
function resolveVendoredIconPath(icon, blueprintDir) {
	if (typeof icon !== 'string') {
		return null;
	}

	const match = matchAllowedRawGitHubUrl(icon, [TRUNK_SOURCE]);
	if (!match || !isBlueprintAttachmentPath(match.resourcePath, blueprintDir)) {
		return null;
	}

	return match.resourcePath;
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

	const { icon, iconSource } = appMeta;
	if (iconSource === undefined) {
		return {};
	}

	if (typeof iconSource !== 'string' || !parseRawGitHubUrl(iconSource)) {
		reportError(
			appMetaPath,
			'iconSource must be an https://raw.githubusercontent.com/ URL without a query string or fragment.'
		);
		return { failed: true };
	}

	const iconPath = resolveVendoredIconPath(icon, blueprintDir);
	if (!iconPath) {
		reportError(
			appMetaPath,
			[
				'iconSource requires icon to point at a vendored file in this Blueprint directory.',
				`Expected: https://raw.githubusercontent.com/wordpress/blueprints/trunk/${blueprintDir}/<file>`,
			].join('\n')
		);
		return { failed: true };
	}

	let upstream;
	try {
		upstream = await fetchIcon(iconSource);
	} catch (error) {
		reportError(
			appMetaPath,
			`Could not fetch iconSource ${iconSource}: ${error.message}`
		);
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
			`Out of sync with ${iconSource}. Run \`npm run sync:app-icons\` and commit the result.`
		);
		return { failed: true };
	}

	fs.writeFileSync(iconPath, upstream);
	console.log(`Updated: ${iconPath} from ${iconSource}`);
	return { updated: iconPath };
}

async function main() {
	const checkOnly = process.argv.includes('--check');
	const appMetaPaths = getAppMetaPaths();
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
