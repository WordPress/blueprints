import path from 'node:path';

export function matchAllowedRawGitHubUrl(rawUrl, allowedSources) {
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
