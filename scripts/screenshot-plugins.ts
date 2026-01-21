// scripts/screenshot-plugins.ts
// Takes screenshots of plugin blueprints using WordPress Playground
import { chromium, devices } from 'playwright';
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve( process.cwd() );
const PLUGINS_DIR = path.join( ROOT, 'plugins-user-contributed' );
const DATA_DIR = path.join( ROOT, 'plugins-data' );

type PluginEntry = {
	slug: string;
	source: 'user-contributed' | 'wporg';
	blueprintUrl: string;
};

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

async function listUserContributedPlugins(): Promise<PluginEntry[]> {
	const entries = await fs.readdir( PLUGINS_DIR );
	return entries
		.filter( ( e ) => e.endsWith( '.json' ) )
		.map( ( e ) => {
			const slug = e.replace( /\.json$/, '' );
			const blueprintPath = `plugins-user-contributed/${e}`;
			const rawUrl = `https://raw.githubusercontent.com/wordpress/blueprints/trunk/${blueprintPath}`;
			return {
				slug,
				source: 'user-contributed' as const,
				blueprintUrl: rawUrl,
			};
		} );
}

async function listWporgPlugins(): Promise<PluginEntry[]> {
	const jsonPath = path.join( DATA_DIR, 'wporg-official.json' );
	try {
		const content = await fs.readFile( jsonPath, 'utf8' );
		const data = JSON.parse( content ) as Record<string, { name: string; active_installs: number }>;
		return Object.keys( data ).map( ( slug ) => ( {
			slug,
			source: 'wporg' as const,
			blueprintUrl: `https://wordpress.org/plugins/wp-json/plugins/v1/plugin/${slug}/blueprint.json`,
		} ) );
	} catch {
		console.error( 'Could not read wporg-official.json' );
		return [];
	}
}

async function ensureDir( filePath: string ): Promise<void> {
	const dir = path.dirname( filePath );
	await fs.mkdir( dir, { recursive: true } );
}

async function takeScreenshot(
	page: Awaited<ReturnType<Awaited<ReturnType<typeof chromium.launch>>['newContext']>>['newPage'] extends () => Promise<infer P> ? P : never,
	slug: string,
	blueprintUrl: string
): Promise<boolean> {
	const url = `https://playground.wordpress.net/?mode=seamless&blueprint-url=${encodeURIComponent( blueprintUrl )}`;

	try {
		await page.goto( url, { waitUntil: 'load', timeout: 300_000 } );
		await page.emulateMedia( { reducedMotion: 'reduce' } );

		const playgroundFrame = page.locator( 'iframe.playground-viewport' );
		await playgroundFrame.waitFor( { state: 'visible', timeout: 300_000 } );

		const frameElement = await playgroundFrame.elementHandle();
		const frame = await frameElement?.contentFrame();
		if ( ! frame ) {
			console.error( `Failed to get frame content for ${slug}` );
			return false;
		}

		const progressBar = frame.locator( '.progress-bar' );
		try {
			await progressBar.waitFor( { state: 'detached', timeout: 600_000 } );
		} catch {
			console.log( `Progress bar wait timed out for ${slug}, continuing anyway` );
		}

		const wpFrame = frame.locator( 'iframe#wp' );
		await wpFrame.waitFor( { state: 'visible', timeout: 300_000 } );

		const wpFrameElement = await wpFrame.elementHandle();
		const wpContentFrame = await wpFrameElement?.contentFrame();

		if ( ! wpContentFrame ) {
			console.error( `Failed to get WordPress frame content for ${slug}` );
			return false;
		}

		try {
			await wpContentFrame.waitForFunction(
				() => {
					const canonical = document.querySelector( 'link[rel="canonical"]' );
					if ( canonical ) return true;

					const scripts = Array.from( document.querySelectorAll( 'script[src], link[href]' ) );
					const hasWpContent = scripts.some( ( el ) => {
						const src = ( el as HTMLScriptElement ).src || ( el as HTMLLinkElement ).href;
						return src && src.includes( '/wp-content/' );
					} );
					if ( hasWpContent ) return true;

					const body = document.body;
					return body && body.children.length > 0;
				},
				{ timeout: 120_000 }
			);
		} catch {
			console.log( `WordPress content detection timed out for ${slug}, taking screenshot anyway` );
		}

		await page.waitForTimeout( 2000 );

		try {
			await wpContentFrame.evaluate( () => {
				( document.body.style as any ).zoom = '150%';
			} );
			await page.waitForTimeout( 500 );
		} catch {
			console.log( `Failed to set zoom for ${slug}, continuing anyway` );
		}

		const out = getScreenshotPath( slug );
		await ensureDir( out );
		await wpFrame.screenshot( { path: out, type: 'jpeg', quality: 70 } );

		console.log( `Shot: ${slug} -> ${path.relative( ROOT, out )}` );
		return true;
	} catch ( e ) {
		console.error( `Error processing ${slug}:`, e );
		return false;
	}
}

async function main() {
	const args = process.argv.slice( 2 );
	const sourceArg = args.find( ( a ) => a.startsWith( '--source=' ) );
	const source = sourceArg?.split( '=' )[1] || 'all';
	const limitArg = args.find( ( a ) => a.startsWith( '--limit=' ) );
	const limit = limitArg ? parseInt( limitArg.split( '=' )[1], 10 ) : Infinity;
	const slugArg = args.find( ( a ) => a.startsWith( '--slug=' ) );
	const specificSlug = slugArg?.split( '=' )[1];

	let plugins: PluginEntry[] = [];

	if ( source === 'all' || source === 'user-contributed' ) {
		plugins = plugins.concat( await listUserContributedPlugins() );
	}
	if ( source === 'all' || source === 'wporg' ) {
		plugins = plugins.concat( await listWporgPlugins() );
	}

	if ( specificSlug ) {
		plugins = plugins.filter( ( p ) => p.slug === specificSlug );
	}

	const toShoot: PluginEntry[] = [];
	for ( const plugin of plugins ) {
		if ( ! ( await hasScreenshot( plugin.slug ) ) ) {
			toShoot.push( plugin );
		}
	}

	if ( toShoot.length === 0 ) {
		console.log( 'All plugins already have screenshots. Nothing to do.' );
		return;
	}

	const batch = toShoot.slice( 0, limit );
	console.log( `Taking screenshots for ${batch.length} plugins (${toShoot.length} total missing)` );

	const browser = await chromium.launch( { headless: true } );
	const context = await browser.newContext( {
		...devices['Desktop Chrome'],
		deviceScaleFactor: 1,
		viewport: { width: 1920, height: 1080 },
	} );

	for ( const plugin of batch ) {
		console.log( `Processing ${plugin.slug} (${plugin.source})...` );
		const page = await context.newPage();
		await takeScreenshot( page, plugin.slug, plugin.blueprintUrl );
		await page.close();
	}

	await browser.close();
	console.log( 'Done.' );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
