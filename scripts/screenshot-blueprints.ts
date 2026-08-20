// scripts/screenshot-blueprints.ts
import { chromium, devices } from 'playwright';
import { promises as fs, readFileSync } from 'node:fs';
import path from 'node:path';
import { getChangedFiles } from './lib/changed-files.js';

const REPO = 'WordPress/blueprints';
const REF = 'trunk';
const ROOT = path.resolve(process.cwd());
const BLUEPRINTS_DIR = path.join(ROOT, 'blueprints');

type BlueprintJson = {
  meta?: {
    title?: string;
    screenshot?: string; // may be relative path, repo-absolute, or raw.githubusercontent URL
  };
};

type BlueprintSource = {
  repo: string;
  ref: string;
};

function readPullRequestBlueprintSource(): BlueprintSource | null {
  if (!process.env.GITHUB_EVENT_PATH) {
    return null;
  }

  try {
    const event = JSON.parse(readFileSync(process.env.GITHUB_EVENT_PATH, 'utf8'));
    const head = event?.pull_request?.head;
    const repo = head?.repo?.full_name;
    const ref = head?.sha;

    if (typeof repo === 'string' && typeof ref === 'string') {
      return { repo, ref };
    }
  } catch {
    // Fall back to the configured defaults outside GitHub pull request events.
  }

  return null;
}

const pullRequestBlueprintSource = readPullRequestBlueprintSource();
const RAW_REPO =
  process.env.BLUEPRINTS_RAW_REPO || pullRequestBlueprintSource?.repo || REPO;
const RAW_REF =
  process.env.BLUEPRINTS_RAW_REF || pullRequestBlueprintSource?.ref || REF;

async function ensureDir(p: string) {
  await fs.mkdir(p, { recursive: true });
}

async function listBlueprintSlugs(): Promise<string[]> {
  const entries = await fs.readdir(BLUEPRINTS_DIR, { withFileTypes: true });
  const dirs = entries.filter((e) => e.isDirectory());
  const slugs: string[] = [];
  for (const d of dirs) {
    const bpPath = path.join(BLUEPRINTS_DIR, d.name, 'blueprint.json');
    try {
      await fs.access(bpPath);
      slugs.push(d.name);
    } catch {}
  }
  return slugs.sort();
}

async function readBlueprint(slug: string): Promise<BlueprintJson | null> {
  try {
    const txt = await fs.readFile(
      path.join(BLUEPRINTS_DIR, slug, 'blueprint.json'),
      'utf8'
    );
    return JSON.parse(txt) as BlueprintJson;
  } catch {
    return null;
  }
}

function rawFileUrl(slug: string, filename: string) {
  return `https://raw.githubusercontent.com/${RAW_REPO}/${RAW_REF}/blueprints/${slug}/${filename}`;
}

function rawBlueprintUrl(slug: string) {
  return rawFileUrl(slug, 'blueprint.json');
}

async function hasDemoJson(slug: string): Promise<boolean> {
  try {
    await fs.access(path.join(BLUEPRINTS_DIR, slug, 'demo.json'));
    return true;
  } catch {
    return false;
  }
}

// In CI, force a re-shoot for any Blueprint whose blueprint.json or demo.json
// was touched by the current pull request, even if it already has a
// screenshot.jpg. Outside CI (no CHANGED_FILES/CHANGED_FILES_PATH set), only
// Blueprints missing a screenshot get shot.
function getForceRegenSlugs(): Set<string> {
  let changedFiles: string[];
  try {
    changedFiles = getChangedFiles();
  } catch {
    return new Set();
  }

  const slugs = new Set<string>();
  for (const file of changedFiles) {
    const m = file.match(/^blueprints\/([^/]+)\/(blueprint\.json|demo\.json)$/);
    if (m) slugs.add(m[1]);
  }
  return slugs;
}

function resolveScreenshotLocalPath(screenshot: string, slug: string): string | null {
  // Case 1: http(s) raw link to *this* repo -> map to local file
  const m = screenshot.match(
    /^https?:\/\/raw\.githubusercontent\.com\/WordPress\/blueprints\/([^/]+)\/(.+)$/i
  );
  if (m) {
    // m[1] = branch (we ignore and use local checkout), m[2] = repo path
    return path.resolve(ROOT, m[2]);
  }

  // Case 2: repo-absolute path like "/docs/foo.png"
  if (screenshot.startsWith('/')) {
    return path.resolve(ROOT, screenshot.slice(1));
  }

  // Case 3: relative to blueprint folder
  if (!/^[a-z]+:\/\//i.test(screenshot)) {
    return path.resolve(ROOT, 'blueprints', slug, screenshot);
  }

  // External URL → not a repo file; can't resolve to a local path.
  return null;
}

async function fileExists(p: string | null): Promise<boolean> {
  if (!p) return false;
  try {
    const st = await fs.stat(p);
    return st.isFile();
  } catch {
    return false;
  }
}

async function hasScreenshot(slug: string): Promise<boolean> {
  // Default gallery behavior: if meta.screenshot isn't set, it expects `screenshot.jpg`
  // next to `blueprint.json`.
  const defaultScreenshot = path.join(BLUEPRINTS_DIR, slug, 'screenshot.jpg');
  if (await fileExists(defaultScreenshot)) return true;

  const bp = await readBlueprint(slug);
  const scr = bp?.meta?.screenshot;
  if (!scr || typeof scr !== 'string') return false;

  // Any URL counts as "has a screenshot" (even if it's not stored in-repo).
  if (/^[a-z]+:\/\//i.test(scr)) {
    const local = resolveScreenshotLocalPath(scr, slug);
    return local ? fileExists(local) : true;
  }

  const local = resolveScreenshotLocalPath(scr, slug);
  return fileExists(local);
}

async function readTitle(slug: string) {
  const bp = await readBlueprint(slug);
  return bp?.meta?.title ?? slug;
}

async function main() {
  const slugs = await listBlueprintSlugs();
  console.log(`Using Blueprint source: ${RAW_REPO}@${RAW_REF}`);

  // Filter: those without any screenshot yet, plus any this PR touched the
  // blueprint.json/demo.json of (their existing screenshot may be stale).
  const forceRegenSlugs = getForceRegenSlugs();
  const toShoot: string[] = [];
  for (const slug of slugs) {
    if (!(await hasScreenshot(slug)) || forceRegenSlugs.has(slug)) {
      toShoot.push(slug);
    }
  }
  if (toShoot.length === 0) {
    console.log('All Blueprints already have screenshots. Nothing to do.');
    return;
  }

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ...devices['Desktop Chrome'],
    deviceScaleFactor: 1,
    viewport: { width: 1920, height: 1080 },
  });

  for (const slug of toShoot) {
    const page = await context.newPage();
    // Prefer demo.json when present: it's a blueprint that also seeds sample
    // content, so the screenshot shows the app in use rather than freshly installed.
    const useDemo = await hasDemoJson(slug);
    const sourceUrl = useDemo ? rawFileUrl(slug, 'demo.json') : rawBlueprintUrl(slug);
    if (useDemo) {
      console.log(`Using demo.json for ${slug} screenshot`);
    }
    const url = `https://playground.wordpress.net/?mode=seamless&blueprint-url=${encodeURIComponent(
      sourceUrl
    )}`;

    // Wait for full load, not just domcontentloaded
    await page.goto(url, { waitUntil: 'load', timeout: 180_000 });
    await page.emulateMedia({ reducedMotion: 'reduce' });

    // Wait for the top-level Playground iframe
    const playgroundFrame = page.locator('iframe.playground-viewport');
    await playgroundFrame.waitFor({ state: 'visible', timeout: 120_000 });

    // Get the frame content
    const frameElement = await playgroundFrame.elementHandle();
    const frame = await frameElement?.contentFrame();
    if (!frame) {
      console.error(`Failed to get frame content for ${slug}`);
      await page.close();
      continue;
    }

    // Wait for the progress bar to NOT exist (not just be hidden) - 5 minute timeout
    const progressBar = frame.locator('.progress-bar');
    try {
      await progressBar.waitFor({ state: 'detached', timeout: 300_000 });
      console.log(`Progress bar disappeared for ${slug}`);
    } catch (e) {
      console.log(`Progress bar wait timed out for ${slug}, continuing anyway`);
    }

    // Wait for the WordPress iframe inside
    const wpFrame = frame.locator('iframe#wp');
    await wpFrame.waitFor({ state: 'visible', timeout: 120_000 });

    // Get the WordPress iframe's content frame
    const wpFrameElement = await wpFrame.elementHandle();
    const wpContentFrame = await wpFrameElement?.contentFrame();
    
    if (!wpContentFrame) {
      console.error(`Failed to get WordPress frame content for ${slug}`);
      await page.close();
      continue;
    }

    // Wait for WordPress content to be loaded by checking for WordPress-specific indicators
    // Check for canonical link, wp-content in scripts/styles, or give it time to load
    try {
      await wpContentFrame.waitForFunction(
        () => {
          // Check for canonical URL
          const canonical = document.querySelector('link[rel="canonical"]');
          if (canonical) return true;
          
          // Check for wp-content in any script or link tags
          const scripts = Array.from(document.querySelectorAll('script[src], link[href]'));
          const hasWpContent = scripts.some(el => {
            const src = (el as HTMLScriptElement).src || (el as HTMLLinkElement).href;
            return src && src.includes('/wp-content/');
          });
          if (hasWpContent) return true;
          
          // Check if body has meaningful content
          const body = document.body;
          return body && body.children.length > 0;
        },
        { timeout: 60_000 }
      );
      console.log(`WordPress content detected for ${slug}`);
    } catch (e) {
      console.log(`WordPress content detection timed out for ${slug}, taking screenshot anyway`);
    }

    // Additional wait to ensure visual rendering is complete
    await page.waitForTimeout(2000);

    // Set zoom to 150% on the WordPress content frame
    try {
      await wpContentFrame.evaluate(() => {
        (document.body.style as any).zoom = '150%';
      });
      // Wait a bit for zoom to apply
      await page.waitForTimeout(500);
    } catch (e) {
      console.log(`Failed to set zoom for ${slug}, continuing anyway`);
    }

    // The mouse defaults to (0,0), which hovers the WordPress logo in the admin
    // bar and pops open its dropdown. Move it away and close any open menu.
    try {
      await page.mouse.move(1900, 1070);
      await wpContentFrame.evaluate(() => {
        document
          .querySelectorAll('#wpadminbar .hover, #wpadminbar .menupop:focus-within')
          .forEach((el) => el.classList.remove('hover'));
        (document.activeElement as HTMLElement | null)?.blur?.();
      });
      await page.waitForTimeout(300);
    } catch (e) {
      console.log(`Failed to dismiss admin bar menu for ${slug}, continuing anyway`);
    }

    // Screenshot the WordPress iframe
    const blueprintDir = path.join(BLUEPRINTS_DIR, slug);
    const out = path.join(blueprintDir, 'screenshot.jpg');
    await wpFrame.screenshot({ path: out, type: 'jpeg', quality: 70 });

    console.log(`Shot: ${slug} -> ${path.relative(ROOT, out)}`);
    await page.close();
  }

  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
