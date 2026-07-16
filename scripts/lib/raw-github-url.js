export function isAllowedRawGitHubUrl(rawUrl, allowedSources) {
	let url;
	try {
		url = new URL(rawUrl);
	} catch {
		return false;
	}

	if (
		url.protocol !== 'https:' ||
		url.hostname.toLowerCase() !== 'raw.githubusercontent.com' ||
		url.username ||
		url.password ||
		url.port
	) {
		return false;
	}

	const [, owner, repository] = url.pathname.split('/');
	if (!owner || !repository) {
		return false;
	}

	const repositoryPathLength = `/${owner}/${repository}/`.length;
	const refAndPath = url.pathname.slice(repositoryPathLength);

	return allowedSources.some((source) => {
		const [allowedOwner, allowedRepository, ...extraParts] =
			source.repository.split('/');

		if (!allowedOwner || !allowedRepository || extraParts.length > 0) {
			return false;
		}

		return (
			owner.toLowerCase() === allowedOwner.toLowerCase() &&
			repository.toLowerCase() === allowedRepository.toLowerCase() &&
			refAndPath.startsWith(`${source.ref}/`)
		);
	});
}
