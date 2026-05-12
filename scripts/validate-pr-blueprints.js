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

		const invalidUrls = findUrlsRequiringBranchPrefix(blueprint).filter(
			(url) =>
				(url.startsWith('https://') || url.startsWith('http://')) &&
				!url.startsWith(
					`https://raw.githubusercontent.com/wordpress/blueprints/${currentBranch}/`
				)
		);

		if (invalidUrls.length > 0) {
			failed = true;
			for (const url of invalidUrls) {
				reportError(
					blueprintJsonPath,
					[
						`URL is not allowed: ${url}`,
						`URLs in blueprint.json must start with https://raw.githubusercontent.com/wordpress/blueprints/${currentBranch}/`,
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
