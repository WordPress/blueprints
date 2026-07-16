import assert from 'node:assert/strict';
import test from 'node:test';

import {
	isAllowedBlueprintResourceUrl,
	isAllowedRawGitHubUrl,
	isBlueprintAttachmentPath,
	matchAllowedRawGitHubUrl,
} from './raw-github-url.js';

const allowedSources = [
	{
		kind: 'head',
		repository: 'WordPress/blueprints',
		ref: 'feature/validate-urls',
	},
	{
		kind: 'trunk',
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

test('returns the matched source and decoded resource path', () => {
	const match = matchAllowedRawGitHubUrl(
		'https://raw.githubusercontent.com/WordPress/blueprints/feature/validate-urls/blueprints/example/file%20name.xml',
		allowedSources
	);

	assert.equal(match?.source.kind, 'head');
	assert.equal(match?.resourcePath, 'blueprints/example/file name.xml');
});

test('supports refs containing a blueprints path segment', () => {
	assert.equal(
		isAllowedRawGitHubUrl(
			'https://raw.githubusercontent.com/WordPress/blueprints/feature/blueprints/fix/blueprints/example/file.xml',
			[
				{
					kind: 'head',
					repository: 'WordPress/blueprints',
					ref: 'feature/blueprints/fix',
				},
			]
		),
		true
	);
});

test('rejects query strings and fragments', () => {
	for (const suffix of ['?cache=1', '#download']) {
		assert.equal(
			isAllowedRawGitHubUrl(
				`https://raw.githubusercontent.com/WordPress/blueprints/feature/validate-urls/blueprints/example/file.xml${suffix}`,
				allowedSources
			),
			false
		);
	}
});

test('only treats canonical files inside the current Blueprint as attachments', () => {
	assert.equal(
		isBlueprintAttachmentPath(
			'blueprints/example/file.xml',
			'blueprints/example'
		),
		true
	);
	assert.equal(
		isBlueprintAttachmentPath(
			'blueprints/other/file.xml',
			'blueprints/example'
		),
		false
	);
	assert.equal(
		isBlueprintAttachmentPath(
			'blueprints/example/../other/file.xml',
			'blueprints/example'
		),
		false
	);
});

test('only accepts existing same-Blueprint files from the pull request head', () => {
	const isLocalFile = (resourcePath) =>
		resourcePath === 'blueprints/example/file.xml';
	const headUrl =
		'https://raw.githubusercontent.com/WordPress/blueprints/feature/validate-urls/';

	assert.equal(
		isAllowedBlueprintResourceUrl(
			`${headUrl}blueprints/example/file.xml`,
			allowedSources,
			'blueprints/example',
			isLocalFile
		),
		true
	);
	assert.equal(
		isAllowedBlueprintResourceUrl(
			`${headUrl}blueprints/other/file.xml`,
			allowedSources,
			'blueprints/example',
			isLocalFile
		),
		false
	);
	assert.equal(
		isAllowedBlueprintResourceUrl(
			`${headUrl}blueprints/example/missing.xml`,
			allowedSources,
			'blueprints/example',
			isLocalFile
		),
		false
	);
});

test('does not require an upstream trunk URL to exist in the current Blueprint directory', () => {
	assert.equal(
		isAllowedBlueprintResourceUrl(
			'https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprints/other/file.xml',
			allowedSources,
			'blueprints/example',
			() => false
		),
		true
	);
});
