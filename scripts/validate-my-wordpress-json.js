import Ajv2020 from 'ajv/dist/2020.js';
import addFormats from 'ajv-formats';

import {
	ajvPath,
	fetchJson,
	listJsonFiles,
	readJson,
	reportError,
	validateBlueprintFiles,
} from './lib/json-validation.js';

const MY_APPS_SCHEMA_BASE_URL =
	process.env.MY_APPS_SCHEMA_BASE_URL ||
	'https://raw.githubusercontent.com/akirk/my-apps/main/schemas';

async function validateBlueprints() {
	const blueprintFiles = [
		'blueprints/my-wordpress/blueprint.json',
		...listJsonFiles('apps'),
	].sort();

	return validateBlueprintFiles(blueprintFiles);
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
