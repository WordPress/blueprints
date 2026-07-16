import assert from 'node:assert/strict';
import test from 'node:test';

import { isAllowedRawGitHubUrl } from './raw-github-url.js';

const allowedSources = [
	{
		repository: 'WordPress/blueprints',
		ref: 'feature/validate-urls',
	},
	{
		repository: 'wordpress/blueprints',
		ref: 'trunk',
	},
];

test('accepts repository names with different capitalization', () => {
	assert.equal(
		isAllowedRawGitHubUrl(
			'HTTPS://RAW.GITHUBUSERCONTENT.COM/wordpress/BLUEPRINTS/feature/validate-urls/blueprints/example/file.xml',
			allowedSources
		),
		true
	);
});

test('keeps refs case-sensitive', () => {
	assert.equal(
		isAllowedRawGitHubUrl(
			'https://raw.githubusercontent.com/WordPress/blueprints/Feature/validate-urls/blueprints/example/file.xml',
			allowedSources
		),
		false
	);
});

test('rejects other repositories and near-matching refs', () => {
	assert.equal(
		isAllowedRawGitHubUrl(
			'https://raw.githubusercontent.com/example/blueprints/feature/validate-urls/blueprints/example/file.xml',
			allowedSources
		),
		false
	);
	assert.equal(
		isAllowedRawGitHubUrl(
			'https://raw.githubusercontent.com/WordPress/blueprints/feature/validate-urls-extended/blueprints/example/file.xml',
			allowedSources
		),
		false
	);
});
