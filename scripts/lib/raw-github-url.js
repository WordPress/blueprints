import path from 'node:path';

export function parseRawGitHubUrl(rawUrl) {
	let url;
	try {
		url = new URL(rawUrl);
	} catch {
		return null;
	}

	if (
		url.protocol !== 'https:' ||
		url.hostname.toLowerCase() !== 'raw.githubusercontent.com' ||
		url.username ||
		url.password ||
		url.port ||
		url.search ||
		url.hash
	) {
		return null;
	}

	let pathname;
	try {
		pathname = decodeURIComponent(url.pathname);
	} catch {
		return null;
	}

	const [, owner, repository] = pathname.split('/');
	if (!owner || !repository) {
		return null;
	}

	const repositoryPathLength = `/${owner}/${repository}/`.length;
	const refAndPath = pathname.slice(repositoryPathLength);
	if (!refAndPath) {
		return null;
	}

	return { owner, repository, refAndPath };
}

export function matchAllowedRawGitHubUrl(rawUrl, allowedSources) {
	const parsed = parseRawGitHubUrl(rawUrl);
	if (!parsed) {
		return null;
	}

	const { owner, repository, refAndPath } = parsed;

	for (const source of allowedSources) {
		const [allowedOwner, allowedRepository, ...extraParts] =
			source.repository.split('/');

		if (
			!allowedOwner ||
			!allowedRepository ||
			extraParts.length > 0 ||
			!source.ref
		) {
			continue;
		}

		if (
			owner.toLowerCase() === allowedOwner.toLowerCase() &&
			repository.toLowerCase() === allowedRepository.toLowerCase() &&
			refAndPath.startsWith(`${source.ref}/`)
		) {
			const resourcePath = refAndPath.slice(source.ref.length + 1);
			return resourcePath ? { source, resourcePath } : null;
		}
	}

	return null;
}

export function isAllowedRawGitHubUrl(rawUrl, allowedSources) {
	return matchAllowedRawGitHubUrl(rawUrl, allowedSources) !== null;
}

const THIS_REPOSITORY = 'wordpress/blueprints';

/**
 * Whether a raw.githubusercontent.com URL already points at this
 * repository (as opposed to an upstream plugin/theme repository).
 */
export function isThisRepositoryRawGitHubUrl(rawUrl) {
	const parsed = parseRawGitHubUrl(rawUrl);
	return Boolean(
		parsed &&
			`${parsed.owner}/${parsed.repository}`.toLowerCase() ===
				THIS_REPOSITORY
	);
}

/**
 * Resolve the file an upstream `icon` URL should be vendored to inside a
 * Blueprint's own directory, named after the upstream file itself.
 *
 * Returns null when there is nothing to vendor: `icon` isn't a URL at all
 * (a Dashicon name or emoji), or it already points at a file served from
 * this repository (authored here, with no declared upstream).
 */
export function resolveVendoredIconPath(icon, blueprintDir) {
	if (typeof icon !== 'string') {
		return null;
	}

	const parsed = parseRawGitHubUrl(icon);
	if (!parsed || isThisRepositoryRawGitHubUrl(icon)) {
		return null;
	}

	const basename = path.posix.basename(parsed.refAndPath);
	return basename ? path.posix.join(blueprintDir, basename) : null;
}

export function isBlueprintAttachmentPath(resourcePath, blueprintDir) {
	const normalizedBlueprintDir = blueprintDir
		.replaceAll('\\', '/')
		.replace(/\/+$/, '');
	if (
		!normalizedBlueprintDir ||
		path.posix.normalize(normalizedBlueprintDir) !==
			normalizedBlueprintDir ||
		resourcePath.includes('\0') ||
		path.posix.normalize(resourcePath) !== resourcePath
	) {
		return false;
	}

	return resourcePath.startsWith(`${normalizedBlueprintDir}/`);
}

export function isAllowedBlueprintResourceUrl(
	rawUrl,
	allowedSources,
	blueprintDir,
	isLocalFile
) {
	const match = matchAllowedRawGitHubUrl(rawUrl, allowedSources);
	if (!match) {
		return false;
	}

	return (
		match.source.kind !== 'head' ||
		(isBlueprintAttachmentPath(match.resourcePath, blueprintDir) &&
			isLocalFile(match.resourcePath))
	);
}
