import assert from 'node:assert/strict';
import test from 'node:test';

import {
	isAllowedBlueprintResourceUrl,
	isAllowedRawGitHubUrl,
	isBlueprintAttachmentPath,
	isThisRepositoryRawGitHubUrl,
	matchAllowedRawGitHubUrl,
	resolveVendoredIconPath,
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

test('accepts tagged and latest GitHub release assets from upstream repositories', () => {
	for (const releasePath of ['download/v0.3.0', 'latest/download']) {
		assert.equal(
			isAllowedBlueprintResourceUrl(
				`https://github.com/donnchawp/travel-steps/releases/${releasePath}/travel-steps.zip`,
				allowedSources,
				'blueprints/example',
				() => false
			),
			true
		);
	}
});

test('rejects release pages, archives, and noncanonical release asset URLs', () => {
	for (const url of [
		'https://github.com/donnchawp/travel-steps/releases/latest',
		'https://github.com/donnchawp/travel-steps/releases/tag/v0.3.0',
		'https://github.com/donnchawp/travel-steps/archive/refs/heads/master.zip',
		'https://github.com/donnchawp/travel-steps/releases/latest/download/',
		'https://github.com/donnchawp/travel-steps/releases/download/v0.3.0/file.zip/extra',
		'http://github.com/donnchawp/travel-steps/releases/latest/download/file.zip',
		'https://github.com.example.com/donnchawp/travel-steps/releases/latest/download/file.zip',
		'https://user@github.com/donnchawp/travel-steps/releases/latest/download/file.zip',
		'https://github.com:8443/donnchawp/travel-steps/releases/latest/download/file.zip',
		'https://github.com/donnchawp/travel-steps/releases/latest/download/file.zip?download=1',
		'https://github.com/donnchawp/travel-steps/releases/latest/download/file.zip#download',
	]) {
		assert.equal(
			isAllowedBlueprintResourceUrl(
				url,
				allowedSources,
				'blueprints/example',
				() => false
			),
			false,
			url
		);
	}
});

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

test('isThisRepositoryRawGitHubUrl only matches this repository', () => {
	assert.equal(
		isThisRepositoryRawGitHubUrl(
			'https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprints/example/icon.svg'
		),
		true
	);
	assert.equal(
		isThisRepositoryRawGitHubUrl(
			'https://raw.githubusercontent.com/example/blueprints/trunk/blueprints/example/icon.svg'
		),
		false
	);
	assert.equal(isThisRepositoryRawGitHubUrl('dashicons-book'), false);
});

test('resolveVendoredIconPath names the vendored file after the upstream file', () => {
	assert.equal(
		resolveVendoredIconPath(
			'https://raw.githubusercontent.com/example/my-plugin/main/assets/icon.svg',
			'blueprints/example'
		),
		'blueprints/example/icon.svg'
	);
});

test('resolveVendoredIconPath returns null for a Dashicon or emoji', () => {
	assert.equal(resolveVendoredIconPath('dashicons-book', 'blueprints/example'), null);
	assert.equal(resolveVendoredIconPath('📚', 'blueprints/example'), null);
	assert.equal(resolveVendoredIconPath(undefined, 'blueprints/example'), null);
});

test('resolveVendoredIconPath returns null for an icon already served from this repository', () => {
	assert.equal(
		resolveVendoredIconPath(
			'https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprints/example/icon.svg',
			'blueprints/example'
		),
		null
	);
});
