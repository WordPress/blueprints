import fs from 'node:fs';
import path from 'node:path';

import Ajv from 'ajv';

export const BLUEPRINT_SCHEMA_URL =
	process.env.BLUEPRINT_SCHEMA_URL ||
	'https://raw.githubusercontent.com/WordPress/wordpress-playground/trunk/packages/playground/blueprints/public/blueprint-schema.json';

const SCHEMA_FETCH_TIMEOUT_MS = Number.parseInt(
	process.env.SCHEMA_FETCH_TIMEOUT_MS || '15000',
	10
);

export function listJsonFiles(dir, recursive = false) {
	if (!fs.existsSync(dir)) {
		return [];
	}

	const files = [];
	for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
		const entryPath = path.join(dir, entry.name);
		if (entry.isDirectory() && recursive) {
			files.push(...listJsonFiles(entryPath, true));
		} else if (entry.isFile() && entry.name.endsWith('.json')) {
			files.push(entryPath);
		}
	}
	return files;
}

export function readJson(filePath) {
	return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

export function reportError(filePath, message) {
	if (process.env.GITHUB_ACTIONS) {
		console.log(
			`::error file=${escapeWorkflowCommand(filePath)}::${escapeWorkflowCommand(message)}`
		);
		return;
	}

	console.error(`${filePath}: ${message}`);
}

function escapeWorkflowCommand(value) {
	return String(value)
		.replaceAll('%', '%25')
		.replaceAll('\r', '%0D')
		.replaceAll('\n', '%0A');
}

export function ajvPath(instancePath) {
	return instancePath ? instancePath.slice(1).replaceAll('/', '.') : '<root>';
}

export async function fetchJson(url) {
	let response;
	try {
		response = await fetch(url, {
			signal: AbortSignal.timeout(SCHEMA_FETCH_TIMEOUT_MS),
		});
	} catch (error) {
		if (error.name === 'TimeoutError' || error.name === 'AbortError') {
			throw new Error(
				`Timed out fetching ${url} after ${SCHEMA_FETCH_TIMEOUT_MS}ms`
			);
		}
		throw error;
	}

	if (!response.ok) {
		throw new Error(
			`Failed to fetch ${url}: ${response.status} ${response.statusText}`
		);
	}

	return response.json();
}

export async function createBlueprintValidator() {
	const blueprintSchema = await fetchJson(BLUEPRINT_SCHEMA_URL);
	const ajv = new Ajv({
		allErrors: true,
		strict: false,
		validateSchema: false,
	});

	return ajv.compile(blueprintSchema);
}

export async function validateBlueprintFiles(filePaths) {
	const validateBlueprint = await createBlueprintValidator();
	let failed = false;

	for (const filePath of filePaths) {
		const valid = validateBlueprint(readJson(filePath));
		if (valid) {
			console.log(`Valid Blueprint schema: ${filePath}`);
			continue;
		}

		failed = true;
		for (const error of validateBlueprint.errors || []) {
			reportError(filePath, `${ajvPath(error.instancePath)}: ${error.message}`);
		}
	}

	return !failed;
}
