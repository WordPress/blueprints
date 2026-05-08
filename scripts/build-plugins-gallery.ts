// scripts/build-plugins-gallery.ts
// Builds the plugin gallery JSON files
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve( process.cwd() );
const PLUGINS_DIR = path.join( ROOT, 'plugins-user-contributed' );
const DATA_DIR = path.join( ROOT, 'plugins-data' );

type BlueprintMeta = {
	title?: string;
	description?: string;
	author?: string;
};

type Blueprint = {
	meta?: BlueprintMeta;
	[key: string]: unknown;
};

type UserContributedEntry = {
	title: string;
	author: string;
	screenshot_url: string;
};

type WporgEntry = {
	name: string;
	active_installs: number;
	preview_url: string;
	screenshot_url?: string;
};

const USER_CONTRIBUTED_OUTPUT = path.join( DATA_DIR, 'user-contributed.json' );
const WPORG_INPUT = path.join( DATA_DIR, 'wporg-official.json' );
const WPORG_OUTPUT = path.join( DATA_DIR, 'wporg-official.json' );

function getScreenshotPath( slug: string ): string {
	const letter = slug.charAt( 0 ).toLowerCase();
	return path.join( DATA_DIR, letter, `${slug}.jpg` );
}

function getScreenshotUrl( slug: string ): string {
	const letter = slug.charAt( 0 ).toLowerCase();
	return `plugins-data/${letter}/${slug}.jpg`;
}

async function hasScreenshot( slug: string ): Promise<boolean> {
	try {
		const st = await fs.stat( getScreenshotPath( slug ) );
		return st.isFile();
	} catch {
		return false;
	}
}

function getLegacyScreenshotPath( slug: string ): string {
	return path.join( PLUGINS_DIR, `${slug}.jpg` );
}

function getLegacyScreenshotUrl( slug: string ): string {
	return `plugins-user-contributed/${slug}.jpg`;
}

async function hasLegacyScreenshot( slug: string ): Promise<boolean> {
	try {
		const st = await fs.stat( getLegacyScreenshotPath( slug ) );
		return st.isFile();
	} catch {
		return false;
	}
}

async function readBlueprint( filepath: string ): Promise<Blueprint | null> {
	try {
		const content = await fs.readFile( filepath, 'utf8' );
		return JSON.parse( content ) as Blueprint;
	} catch {
		return null;
	}
}

async function buildUserContributedIndex(): Promise<Record<string, UserContributedEntry>> {
	const entries = await fs.readdir( PLUGINS_DIR );
	const jsonFiles = entries.filter( ( e ) => e.endsWith( '.json' ) );

	const unsortedEntries: Array<{ path: string; entry: UserContributedEntry }> = [];

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

		let screenshotUrl = '';
		if ( await hasScreenshot( slug ) ) {
			screenshotUrl = getScreenshotUrl( slug );
		} else if ( await hasLegacyScreenshot( slug ) ) {
			screenshotUrl = getLegacyScreenshotUrl( slug );
		}

		unsortedEntries.push( {
			path: relativePath,
			entry: {
				title: meta.title || slug,
				author: meta.author || '',
				screenshot_url: screenshotUrl,
			},
		} );
	}

	unsortedEntries.sort( ( a, b ) =>
		a.entry.title.localeCompare( b.entry.title, undefined, { sensitivity: 'base' } )
	);

	const index: Record<string, UserContributedEntry> = {};
	for ( const { path, entry } of unsortedEntries ) {
		index[path] = entry;
	}

	return index;
}

async function enrichWporgIndex(): Promise<void> {
	let data: Record<string, WporgEntry>;
	try {
		const content = await fs.readFile( WPORG_INPUT, 'utf8' );
		data = JSON.parse( content );
	} catch {
		console.log( 'No wporg-official.json found, skipping enrichment' );
		return;
	}

	let enriched = 0;
	for ( const slug of Object.keys( data ) ) {
		const screenshotExists = await hasScreenshot( slug );
		if ( screenshotExists ) {
			data[slug].screenshot_url = getScreenshotUrl( slug );
			enriched++;
		} else if ( data[slug].screenshot_url ) {
			delete data[slug].screenshot_url;
		}
	}

	await fs.writeFile( WPORG_OUTPUT, JSON.stringify( data, null, '\t' ) );
	console.log( `Enriched ${enriched} wporg plugins with screenshot URLs` );
}

async function main() {
	console.log( 'Building plugin gallery index...' );

	await fs.mkdir( DATA_DIR, { recursive: true } );

	const userContributedIndex = await buildUserContributedIndex();

	if ( Object.keys( userContributedIndex ).length === 0 ) {
		console.log( 'No plugin blueprints found in plugins-user-contributed/' );
	} else {
		await fs.writeFile( USER_CONTRIBUTED_OUTPUT, JSON.stringify( userContributedIndex, null, '\t' ) );
		console.log( `Wrote ${Object.keys( userContributedIndex ).length} user-contributed plugins` );
	}

	await enrichWporgIndex();

	console.log( 'Done.' );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
