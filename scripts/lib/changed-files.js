import fs from 'node:fs';

export function getChangedFiles() {
	if (process.env.CHANGED_FILES) {
		return process.env.CHANGED_FILES.split(/\r?\n/).filter(Boolean);
	}

	if (process.env.CHANGED_FILES_PATH) {
		return fs
			.readFileSync(process.env.CHANGED_FILES_PATH, 'utf8')
			.split(/\r?\n/)
			.filter(Boolean);
	}

	throw new Error('CHANGED_FILES_PATH or CHANGED_FILES must be provided.');
}

export function getTouchedBlueprintDirectories() {
	const blueprintDirs = new Set();

	for (const changedFile of getChangedFiles()) {
		const match = changedFile.match(/^(blueprints\/[^/]+)(?:\/|$)/);
		// A directory that was deleted or renamed away has nothing left to validate.
		if (match && fs.existsSync(match[1])) {
			blueprintDirs.add(match[1]);
		}
	}

	return [...blueprintDirs].sort();
}
