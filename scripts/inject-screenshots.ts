// scripts/inject-screenshots.ts
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(process.cwd());
const GALLERY = path.join(ROOT, 'GALLERY.md');
const BLUEPRINTS_DIR = path.join(ROOT, 'blueprints');

type BlueprintJson = {
  meta?: {
    title?: string;
    screenshot?: string;
  };
};

async function fileExists(p: string): Promise<boolean> {
  try {
    const st = await fs.stat(p);
    return st.isFile();
  } catch {
    return false;
  }
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

function escapeRegExp(s: string): string {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeHtmlAttribute(s: string): string {
  return s
    .replaceAll('&', '&amp;')
    .replaceAll('"', '&quot;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');
}

function injectIntoHtmlGallery(
  md: string,
  slug: string,
  title: string,
  screenshotRelPath: string
): string {
  const blueprintNeedle = `blueprints/${slug}/blueprint.json`;
  const idx = md.indexOf(blueprintNeedle);
  if (idx === -1) return md;

  const blockStart = md.lastIndexOf('<h2', idx);
  if (blockStart === -1) return md;

  const blockEndMarker = '<br clear="all">';
  let blockEnd = md.indexOf(blockEndMarker, idx);
  if (blockEnd === -1) {
    const nextH2 = md.indexOf('<h2', idx + blueprintNeedle.length);
    blockEnd = nextH2 === -1 ? md.length : nextH2;
  } else {
    blockEnd += blockEndMarker.length;
  }

  const block = md.slice(blockStart, blockEnd);

  // If a screenshot is already present (either our default or a meta.screenshot URL), don't add another one.
  if (block.includes('<p align="left"><img')) return md;

  const screenshotHtml = `<p align="left"><img src="${screenshotRelPath}" alt="${escapeHtmlAttribute(
    title
  )} screenshot" width="400"></p>`;

  const placeholderRe =
    /<p align="left"><em>No screenshot yet for[^<]*<\/em><\/p>/;

  const updatedBlock = placeholderRe.test(block)
    ? block.replace(placeholderRe, screenshotHtml)
    : block.includes(blockEndMarker)
      ? block.replace(blockEndMarker, `${screenshotHtml}\n${blockEndMarker}`)
      : block;

  if (updatedBlock === block) return md;
  return md.slice(0, blockStart) + updatedBlock + md.slice(blockEnd);
}

function injectIntoMarkdownTable(
  md: string,
  slug: string,
  title: string,
  screenshotRelPath: string
): string {
  if (md.includes(`(${screenshotRelPath})`)) return md;

  const re = new RegExp(
    `(^\\|.*blueprints\\/${escapeRegExp(slug)}\\/blueprint\\.json.*\\|)$`,
    'm'
  );
  if (!re.test(md)) return md;

  return md.replace(re, `$1\n\n![${title}](${screenshotRelPath})`);
}

async function main() {
  const slugs = await listBlueprintSlugs();
  let md = await fs.readFile(GALLERY, 'utf8');
  const original = md;

  for (const slug of slugs) {
    const screenshotPath = path.join(BLUEPRINTS_DIR, slug, 'screenshot.jpg');
    if (!(await fileExists(screenshotPath))) continue;

    const blueprint = await readBlueprint(slug);
    const title = blueprint?.meta?.title ?? slug;
    const screenshotRelPath = `blueprints/${slug}/screenshot.jpg`;

    // Prefer the HTML gallery format, but support older markdown-table format as a fallback.
    const next = injectIntoHtmlGallery(md, slug, title, screenshotRelPath);
    md =
      next !== md ? next : injectIntoMarkdownTable(md, slug, title, screenshotRelPath);
  }

  if (md !== original) {
    await fs.writeFile(GALLERY, md);
    console.log('Updated GALLERY.md');
  } else {
    console.log('No changes to GALLERY.md');
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
