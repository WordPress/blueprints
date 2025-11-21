// scripts/screenshot-plugins.ts
// Takes screenshots of plugin blueprints using WordPress Playground
import { chromium, devices } from 'playwright';
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve( process.cwd() );
const PLUGINS_DIR = path.join( ROOT, 'plugins-user-contributed' );

async function listPluginSlugs(): Promise<string[]> {
	const entries = await fs.readdir( PLUGINS_DIR );
	return entries
		.filter( ( e ) => e.endsWith( '.json' ) )
		.map( ( e ) => e.replace( /\.json$/, '' ) )
		.sort();
}

async function readBlueprint( slug: string ): Promise<object | null> {
	try {
		const content = await fs.readFile( path.join( PLUGINS_DIR, `${slug}.json` ), 'utf8' );
		return JSON.parse( content );
	} catch {
		return null;
	}
}

function blueprintDataUrl( blueprint: object ): string {
	const json = JSON.stringify( blueprint );
	const base64 = Buffer.from( json ).toString( 'base64' );
	return `data:application/json;base64,${base64}`;
}

async function hasScreenshot( slug: string ): Promise<boolean> {
	const screenshotPath = path.join( PLUGINS_DIR, `${slug}.jpg` );
	try {
		const st = await fs.stat( screenshotPath );
		return st.isFile();
	} catch {
		return false;
	}
}

async function main() {
	const slugs = await listPluginSlugs();

	const toShoot: string[] = [];
	for ( const slug of slugs ) {
		if ( ! ( await hasScreenshot( slug ) ) ) {
			toShoot.push( slug );
		}
	}

	if ( toShoot.length === 0 ) {
		console.log( 'All plugin blueprints already have screenshots. Nothing to do.' );
		return;
	}

	console.log( `Taking screenshots for ${toShoot.length} plugins: ${toShoot.join( ', ' )}` );

	const browser = await chromium.launch( { headless: true } );
	const context = await browser.newContext( {
		...devices['Desktop Chrome'],
		deviceScaleFactor: 1,
		viewport: { width: 1920, height: 1080 },
	} );

	for ( const slug of toShoot ) {
		console.log( `Processing ${slug}...` );
		const blueprint = await readBlueprint( slug );
		if ( ! blueprint ) {
			console.error( `Could not read blueprint for ${slug}` );
			continue;
		}

		const page = await context.newPage();
		const dataUrl = blueprintDataUrl( blueprint );
		const url = `https://playground.wordpress.net/?mode=seamless&blueprint-url=${encodeURIComponent( dataUrl )}`;
		console.log( `URL: ${url}` );

		try {
			await page.goto( url, { waitUntil: 'load', timeout: 300_000 } );
			await page.emulateMedia( { reducedMotion: 'reduce' } );

			const playgroundFrame = page.locator( 'iframe.playground-viewport' );
			await playgroundFrame.waitFor( { state: 'visible', timeout: 300_000 } );

			const frameElement = await playgroundFrame.elementHandle();
			const frame = await frameElement?.contentFrame();
			if ( ! frame ) {
				console.error( `Failed to get frame content for ${slug}` );
				await page.close();
				continue;
			}

			const progressBar = frame.locator( '.progress-bar' );
			try {
				await progressBar.waitFor( { state: 'detached', timeout: 600_000 } );
				console.log( `Progress bar disappeared for ${slug}` );
			} catch ( e ) {
				console.log( `Progress bar wait timed out for ${slug}, continuing anyway` );
			}

			const wpFrame = frame.locator( 'iframe#wp' );
			await wpFrame.waitFor( { state: 'visible', timeout: 300_000 } );

			const wpFrameElement = await wpFrame.elementHandle();
			const wpContentFrame = await wpFrameElement?.contentFrame();

			if ( ! wpContentFrame ) {
				console.error( `Failed to get WordPress frame content for ${slug}` );
				await page.close();
				continue;
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
				console.log( `WordPress content detected for ${slug}` );
			} catch ( e ) {
				console.log( `WordPress content detection timed out for ${slug}, taking screenshot anyway` );
			}

			await page.waitForTimeout( 2000 );

			try {
				await wpContentFrame.evaluate( () => {
					( document.body.style as any ).zoom = '150%';
				} );
				await page.waitForTimeout( 500 );
			} catch ( e ) {
				console.log( `Failed to set zoom for ${slug}, continuing anyway` );
			}

			const out = path.join( PLUGINS_DIR, `${slug}.jpg` );
			await wpFrame.screenshot( { path: out, type: 'jpeg', quality: 70 } );

			console.log( `Shot: ${slug} -> ${path.relative( ROOT, out )}` );
		} catch ( e ) {
			console.error( `Error processing ${slug}:`, e );
		}

		await page.close();
	}

	await browser.close();
	console.log( 'Done.' );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
