// scripts/inject-screenshots.ts
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(process.cwd());
const GALLERY = path.resolve('GALLERY.md');
const SHOTS_DIR = 'docs/screenshots';
const BLUEPRINTS_DIR = path.join(ROOT, 'blueprints');

type BlueprintJson = { meta?: { screenshot?: string } };

async function fileExists(p: string | null): Promise<boolean> {
  if (!p) return false;
  try {
    const st = await fs.stat(p);
    return st.isFile();
  } catch {
    return false;
  }
}

function resolveScreenshotLocalPath(screenshot: string, slug: string): string | null {
  const m = screenshot.match(
    /^https?:\/\/raw\.githubusercontent\.com\/WordPress\/blueprints\/([^/]+)\/(.+)$/i
  );
  if (m) return path.resolve(ROOT, m[2]);
  if (screenshot.startsWith('/')) return path.resolve(ROOT, screenshot.slice(1));
  if (!/^[a-z]+:\/\//i.test(screenshot))
    return path.resolve(ROOT, 'blueprints', slug, screenshot);
  return null; // external
}

async function hasRepoScreenshot(slug: string): Promise<boolean> {
  try {
    const txt = await fs.readFile(
      path.join(BLUEPRINTS_DIR, slug, 'blueprint.json'),
      'utf8'
    );
    const j = JSON.parse(txt) as BlueprintJson;
    const scr = j?.meta?.screenshot;
    if (!scr || typeof scr !== 'string') return false;
    const local = resolveScreenshotLocalPath(scr, slug);
    return fileExists(local);
  } catch {
    return false;
  }
}

async function main() {
  let md = await fs.readFile(GALLERY, 'utf8');
  const shots = (await fs.readdir(SHOTS_DIR)).filter((n) => n.endsWith('.jpg'));

  for (const file of shots) {
    const slug = file.replace(/\.jpg$/, '');

    // Skip injecting if blueprint declares a repo-backed screenshot.
    if (await hasRepoScreenshot(slug)) continue;

    // Match the entire table row that contains a link to this blueprint
    const re = new RegExp(`(^\\|.*blueprints\\/${slug}\\/blueprint\\.json.*\\|)$`, 'm');
    if (re.test(md) && !md.includes(`(${SHOTS_DIR}/${slug}.jpg)`)) {
      md = md.replace(re, `$1\n\n![${slug}](${SHOTS_DIR}/${slug}.jpg)`);
    }
  }

  await fs.writeFile(GALLERY, md);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
