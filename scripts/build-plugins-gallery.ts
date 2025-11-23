// scripts/build-plugins-gallery.ts
// Builds the plugin gallery index and embeds it into plugins.html
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve( process.cwd() );
const PLUGINS_DIR = path.join( ROOT, 'plugins-user-contributed' );
const PLUGINS_HTML = path.join( ROOT, 'plugins.html' );

type BlueprintMeta = {
	title?: string;
	description?: string;
	author?: string;
	icon_url?: string;
};

type Blueprint = {
	meta?: BlueprintMeta;
	[key: string]: unknown;
};

type IndexEntry = {
	title: string;
	author: string;
	screenshot_url: string;
};

const REPO = 'WordPress/blueprints';
const BRANCH = 'trunk';

async function hasScreenshot( slug: string ): Promise<boolean> {
	const screenshotPath = path.join( PLUGINS_DIR, `${slug}.jpg` );
	try {
		const st = await fs.stat( screenshotPath );
		return st.isFile();
	} catch {
		return false;
	}
}

function buildScreenshotUrl( slug: string ): string {
	return `plugins-user-contributed/${slug}.jpg`;
}

async function readBlueprint( filepath: string ): Promise<Blueprint | null> {
	try {
		const content = await fs.readFile( filepath, 'utf8' );
		return JSON.parse( content ) as Blueprint;
	} catch {
		return null;
	}
}

async function buildIndex(): Promise<Record<string, IndexEntry>> {
	const entries = await fs.readdir( PLUGINS_DIR );
	const jsonFiles = entries.filter( ( e ) => e.endsWith( '.json' ) );

	const unsortedEntries: Array<{ path: string; entry: IndexEntry }> = [];

	for ( const file of jsonFiles ) {
		const filepath = path.join( PLUGINS_DIR, file );
		const blueprint = await readBlueprint( filepath );

		if ( ! blueprint ) {
			console.warn( `Skipping ${file}: could not parse` );
			continue;
		}

		const slug = file.replace( /\.json$/, '' );
		const relativePath = `plugins-user-contributed/${file}`;
		const meta = blueprint.meta || {};
		const screenshotExists = await hasScreenshot( slug );

		unsortedEntries.push( {
			path: relativePath,
			entry: {
				title: meta.title || slug,
				author: meta.author || '',
				screenshot_url: screenshotExists ? buildScreenshotUrl( slug ) : '',
			},
		} );
	}

	unsortedEntries.sort( ( a, b ) =>
		a.entry.title.localeCompare( b.entry.title, undefined, { sensitivity: 'base' } )
	);

	const index: Record<string, IndexEntry> = {};
	for ( const { path, entry } of unsortedEntries ) {
		index[path] = entry;
	}

	return index;
}

async function injectIndexIntoHtml( index: Record<string, IndexEntry> ): Promise<void> {
	const html = await fs.readFile( PLUGINS_HTML, 'utf8' );

	const indexJson = JSON.stringify( index );
	const scriptTagPattern = /<script id="blueprint-data" type="application\/json">.*?<\/script>/s;

	if ( ! scriptTagPattern.test( html ) ) {
		console.error( 'Could not find blueprint-data script tag in plugins.html' );
		process.exit( 1 );
	}

	const updatedHtml = html.replace(
		scriptTagPattern,
		`<script id="blueprint-data" type="application/json">${indexJson}</script>`
	);

	await fs.writeFile( PLUGINS_HTML, updatedHtml );
	console.log( `Injected ${Object.keys( index ).length} plugins into plugins.html` );
}

async function main() {
	console.log( 'Building plugin gallery index...' );

	const index = await buildIndex();

	if ( Object.keys( index ).length === 0 ) {
		console.log( 'No plugin blueprints found in plugins-user-contributed/' );
		return;
	}

	await injectIndexIntoHtml( index );

	console.log( 'Done.' );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
