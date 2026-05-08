import fs from 'node:fs';
import path from 'node:path';

import Ajv from 'ajv';
import Ajv2020 from 'ajv/dist/2020.js';
import addFormats from 'ajv-formats';

const BLUEPRINT_SCHEMA_URL =
	process.env.BLUEPRINT_SCHEMA_URL ||
	'https://raw.githubusercontent.com/WordPress/wordpress-playground/trunk/packages/playground/blueprints/public/blueprint-schema.json';
const MY_APPS_SCHEMA_BASE_URL =
	process.env.MY_APPS_SCHEMA_BASE_URL ||
	'https://raw.githubusercontent.com/akirk/my-apps/main/schemas';
const SCHEMA_FETCH_TIMEOUT_MS = Number.parseInt(
	process.env.SCHEMA_FETCH_TIMEOUT_MS || '15000',
	10
);

function listJsonFiles(dir, recursive = false) {
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

function readJson(filePath) {
	return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function reportError(filePath, message) {
	if (process.env.GITHUB_ACTIONS) {
		console.log(`::error file=${filePath}::${message}`);
		return;
	}

	console.error(`${filePath}: ${message}`);
}

function ajvPath(instancePath) {
	return instancePath ? instancePath.slice(1).replaceAll('/', '.') : '<root>';
}

async function fetchJson(url) {
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

async function validateBlueprints() {
	const blueprintSchema = await fetchJson(BLUEPRINT_SCHEMA_URL);
	const blueprintAjv = new Ajv({
		allErrors: true,
		strict: false,
		validateSchema: false,
	});
	const validateBlueprint = blueprintAjv.compile(blueprintSchema);
	const blueprintFiles = [
		'blueprints/my-wordpress/blueprint.json',
		...listJsonFiles('apps'),
	].sort();

	let failed = false;
	for (const filePath of blueprintFiles) {
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

async function validateCatalogs() {
	const catalogAjv = new Ajv2020({ allErrors: true, strict: false });
	addFormats(catalogAjv);

	const catalogChecks = {
		'apps.json': `${MY_APPS_SCHEMA_BASE_URL}/apps.schema.json`,
		'blueprints/my-wordpress/plugins.json': `${MY_APPS_SCHEMA_BASE_URL}/plugins.schema.json`,
		'blueprints/my-wordpress/recipes.json': `${MY_APPS_SCHEMA_BASE_URL}/recipes.schema.json`,
	};

	let failed = false;
	for (const [filePath, schemaUrl] of Object.entries(catalogChecks)) {
		const schema = await fetchJson(schemaUrl);
		const validateCatalog = catalogAjv.compile(schema);
		const valid = validateCatalog(readJson(filePath));
		if (valid) {
			console.log(`Valid My Apps catalog schema: ${filePath}`);
			continue;
		}

		failed = true;
		for (const error of validateCatalog.errors || []) {
			reportError(filePath, `${ajvPath(error.instancePath)}: ${error.message}`);
		}
	}

	return !failed;
}

function validateJsonSyntax() {
	const jsonFiles = [
		'apps.json',
		...listJsonFiles('apps'),
		...listJsonFiles('blueprints/my-wordpress', true),
	].sort();

	let failed = false;
	for (const filePath of jsonFiles) {
		try {
			readJson(filePath);
			console.log(`Valid JSON: ${filePath}`);
		} catch (error) {
			failed = true;
			reportError(filePath, `Invalid JSON: ${error.message}`);
		}
	}

	return !failed;
}

async function main() {
	if (!validateJsonSyntax()) {
		process.exit(1);
	}

	const blueprintsValid = await validateBlueprints();
	const catalogsValid = await validateCatalogs();

	if (!blueprintsValid || !catalogsValid) {
		process.exit(1);
	}
}

main().catch((error) => {
	console.error(error);
	process.exit(1);
});
