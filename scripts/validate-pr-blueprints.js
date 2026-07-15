import fs from 'node:fs';
import path from 'node:path';

import {
	ajvPath,
	createBlueprintValidator,
	readJson,
	reportError,
} from './lib/json-validation.js';

function getCurrentBranch() {
	const currentBranch = process.env.GITHUB_BRANCH || process.env.GITHUB_HEAD_REF;

	if (!currentBranch) {
		throw new Error('Could not determine the current branch for URL validation.');
	}

	return currentBranch;
}

function getChangedFiles() {
	if (process.env.CHANGED_FILES) {
		return process.env.CHANGED_FILES.split(/\r?\n/).filter(Boolean);
	}

	if (process.env.CHANGED_FILES_PATH) {
		return fs
			.readFileSync(process.env.CHANGED_FILES_PATH, 'utf8')
			.split(/\r?\n/)
			.filter(Boolean);
	}

	throw new Error('CHANGED_FILES_PATH or CHANGED_FILES must be provided.');
}

function getTouchedBlueprintDirectories() {
	const blueprintDirs = new Set();

	for (const changedFile of getChangedFiles()) {
		const match = changedFile.match(/^(blueprints\/[^/]+)(?:\/|$)/);
		if (match) {
			blueprintDirs.add(match[1]);
		}
	}

	return [...blueprintDirs].sort();
}

async function isUrlValid(url, allowedPrefixes) {
	if (!url.startsWith('https://') && !url.startsWith('http://')) {
		return true;
	}

	if (!allowedPrefixes.some((prefix) => url.startsWith(prefix))) {
		return false;
	}

	try {
		const response = await fetch(url, {
			method: 'HEAD',
			signal: AbortSignal.timeout(10_000),
		});
		return response.ok;
	} catch {
		return false;
	}
}

function findUrlsRequiringBranchPrefix(value) {
	if (Array.isArray(value)) {
		return value.flatMap(findUrlsRequiringBranchPrefix);
	}

	if (!value || typeof value !== 'object') {
		return [];
	}

	const urls = [];
	const validatesOwnUrl = value.resource !== 'git:directory';

	for (const [key, child] of Object.entries(value)) {
		if (key === 'url' && typeof child === 'string' && validatesOwnUrl) {
			urls.push(child);
		}

		urls.push(...findUrlsRequiringBranchPrefix(child));
	}

	return urls;
}

async function main() {
	const blueprintDirs = getTouchedBlueprintDirectories();
	if (blueprintDirs.length === 0) {
		console.log('No changed blueprint directories found.');
		return;
	}

	const currentBranch = getCurrentBranch();
	const allowedUrlPrefixes = [currentBranch, 'trunk'].map(
		(branch) =>
			`https://raw.githubusercontent.com/wordpress/blueprints/${branch}/`
	);
	let validateBlueprint;
	let failed = false;

	for (const blueprintDir of blueprintDirs) {
		const blueprintJsonPath = path.join(blueprintDir, 'blueprint.json');

		if (!fs.existsSync(blueprintJsonPath)) {
			failed = true;
			reportError(blueprintJsonPath, 'Blueprint directory must contain a blueprint.json file.');
			continue;
		}

		let blueprint;
		try {
			blueprint = readJson(blueprintJsonPath);
		} catch (error) {
			failed = true;
			reportError(blueprintJsonPath, `Invalid JSON: ${error.message}`);
			continue;
		}

		validateBlueprint ??= await createBlueprintValidator();
		if (!validateBlueprint(blueprint)) {
			failed = true;
			for (const error of validateBlueprint.errors || []) {
				reportError(blueprintJsonPath, `${ajvPath(error.instancePath)}: ${error.message}`);
			}
			continue;
		}

		const invalidUrls = (
			await Promise.all(
				findUrlsRequiringBranchPrefix(blueprint).map(async (url) =>
					(await isUrlValid(url, allowedUrlPrefixes)) ? null : url
				)
			)
		).filter(Boolean);

		if (invalidUrls.length > 0) {
			failed = true;
			for (const url of invalidUrls) {
				reportError(
					blueprintJsonPath,
					[
						`URL is not allowed or could not be fetched: ${url}`,
						'URLs in blueprint.json must use the current branch or trunk.',
					].join('\n')
				);
			}
			continue;
		}

		console.log(`Valid Blueprint: ${blueprintJsonPath}`);
	}

	if (failed) {
		process.exit(1);
	}
}

main().catch((error) => {
	console.error(error);
	process.exit(1);
});
