// scripts/screenshot-blueprints.ts
import { chromium, devices } from 'playwright';
import { promises as fs } from 'node:fs';
import path from 'node:path';

const REPO = 'WordPress/blueprints';
const BRANCH = 'trunk';
const ROOT = path.resolve(process.cwd());
const BLUEPRINTS_DIR = path.join(ROOT, 'blueprints');

type BlueprintJson = {
  meta?: {
    title?: string;
    screenshot?: string; // may be relative path, repo-absolute, or raw.githubusercontent URL
  };
};

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

function rawBlueprintUrl(slug: string) {
  return `https://raw.githubusercontent.com/${REPO}/${BRANCH}/blueprints/${slug}/blueprint.json`;
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

  // External URL → not a repo file; treat as "missing" so we'll generate one.
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

async function hasRepoScreenshot(slug: string): Promise<boolean> {
  const bp = await readBlueprint(slug);
  const scr = bp?.meta?.screenshot;
  if (!scr || typeof scr !== 'string') return false;
  const local = resolveScreenshotLocalPath(scr, slug);
  return fileExists(local);
}

async function readTitle(slug: string) {
  const bp = await readBlueprint(slug);
  return bp?.meta?.title ?? slug;
}

async function main() {
  const slugs = await listBlueprintSlugs();

  // Filter: only those without a repo-backed screenshot
  const toShoot: string[] = [];
  for (const slug of slugs) {
    if (!(await hasRepoScreenshot(slug))) {
      toShoot.push(slug);
    }
  }
  if (toShoot.length === 0) {
    console.log('All Blueprints already have repo screenshots. Nothing to do.');
    return;
  }

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ...devices['Desktop Chrome'],
    deviceScaleFactor: 1,
    viewport: { width: 1280, height: 800 },
  });

  for (const slug of toShoot) {
    const page = await context.newPage();
    const url = `https://playground.wordpress.net/?mode=seamless&blueprint-url=${encodeURIComponent(
      rawBlueprintUrl(slug)
    )}`;
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 180_000 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.waitForLoadState('networkidle', { timeout: 120_000 });

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

    // Wait for the progress bar to disappear
    const progressBar = frame.locator('.progress-bar');
    try {
      await progressBar.waitFor({ state: 'hidden', timeout: 120_000 });
    } catch (e) {
      console.log(`Progress bar wait timed out for ${slug}, continuing anyway`);
    }

    // Wait for the WordPress iframe inside
    const wpFrame = frame.locator('iframe#wp');
    await wpFrame.waitFor({ state: 'visible', timeout: 120_000 });

    // Additional wait to ensure content is loaded in the WordPress iframe
    await page.waitForTimeout(3000);

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
