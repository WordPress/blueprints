import fs from 'node:fs';
import path from 'node:path';

import { getTouchedBlueprintDirectories } from './lib/changed-files.js';
import {
	ajvPath,
	createBlueprintValidator,
	readJson,
	reportError,
} from './lib/json-validation.js';
import { isAllowedBlueprintResourceUrl } from './lib/raw-github-url.js';

function getPullRequestSource() {
	const repository = process.env.PR_HEAD_REPOSITORY;
	const ref = process.env.PR_HEAD_REF;

	if (!repository || !ref) {
		throw new Error(
			'PR_HEAD_REPOSITORY and PR_HEAD_REF must be provided for URL validation.'
		);
	}

	return { repository, ref };
}

function isLocalFile(filePath) {
	try {
		return fs.statSync(filePath).isFile();
	} catch {
		return false;
	}
}

async function isUrlValid(url, allowedSources, blueprintDir) {
	if (!/^https?:\/\//i.test(url)) {
		return true;
	}

	if (
		!isAllowedBlueprintResourceUrl(
			url,
			allowedSources,
			blueprintDir,
			isLocalFile
		)
	) {
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

	const { repository, ref } = getPullRequestSource();
	const allowedUrlSources = [
		{ kind: 'head', repository, ref },
		{ kind: 'trunk', repository: 'wordpress/blueprints', ref: 'trunk' },
	];
	const allowedUrlPrefixes = allowedUrlSources.map(
		(source) =>
			`https://raw.githubusercontent.com/${source.repository}/${source.ref}/`
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
					(await isUrlValid(url, allowedUrlSources, blueprintDir))
						? null
						: url
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
						'URLs must be fetchable and use the pull request repository and ref, or upstream trunk.',
						'Pull request URLs must point to files inside the current Blueprint directory.',
						`Expected: ${allowedUrlPrefixes.join(' or ')}`,
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
