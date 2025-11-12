# Automated Screenshot Generation

This document explains the automated screenshot generation system for the Blueprint Gallery.

## Overview

The screenshot automation system uses GitHub Actions to automatically generate and inject screenshots into the GALLERY.md file for Blueprints that don't already have a `meta.screenshot` property pointing to an existing file in the repository.

## How It Works

### 1. Screenshot Generation (`scripts/screenshot-blueprints.ts`)

This script:
- Scans all blueprint folders in the `blueprints/` directory
- For each blueprint, checks if it has a `meta.screenshot` property in its `blueprint.json`
- If a screenshot property exists, verifies if the file exists in the repository
- For blueprints without existing screenshots, uses Playwright to:
  - Load the Blueprint via `?blueprint-url=` on playground.wordpress.net
  - Wait for the Playground to fully load and settle
  - Capture a screenshot of just the WordPress iframe (`#wp-playground`)
  - Save as JPEG at 70% quality to `docs/screenshots/<slug>.jpg`

### 2. Gallery Injection (`scripts/inject-screenshots.ts`)

This script:
- Reads all generated screenshot files from `docs/screenshots/`
- For each screenshot, checks if the corresponding blueprint has a repo-backed screenshot
- If not, finds the matching row in GALLERY.md and injects the screenshot image below it
- Preserves the existing table structure while adding visual previews

### 3. GitHub Actions Workflow (`.github/workflows/screenshots.yml`)

The workflow:
- Runs weekly on Mondays at 3 AM UTC
- Can be manually triggered via workflow_dispatch
- Installs dependencies and Playwright browsers
- Executes both scripts in sequence
- Creates a pull request with any changes

## Screenshot Resolution Logic

The system can resolve three types of screenshot paths in `meta.screenshot`:

1. **Raw GitHub URLs**: `https://raw.githubusercontent.com/WordPress/blueprints/trunk/path/to/image.jpg`
   - Mapped to local repository path
   
2. **Repository-absolute paths**: `/docs/assets/screenshot.jpg`
   - Resolved relative to repository root
   
3. **Blueprint-relative paths**: `./screenshot.jpg` or `screenshot.jpg`
   - Resolved relative to the blueprint's folder

If a screenshot path points to a non-existent file or is an external URL, the blueprint is considered as needing a screenshot.

## Usage

### Manual Trigger

1. Go to the repository's Actions tab
2. Select "Update Gallery Screenshots" workflow
3. Click "Run workflow"
4. Select the branch (usually `trunk`)
5. Click "Run workflow"

### Local Testing

To test the scripts locally:

```bash
# Install dependencies
npm install

# Install Playwright browsers
npx playwright install --with-deps

# Generate screenshots
npm run shots

# Inject screenshots into GALLERY.md
npm run inject
```

## Adding Screenshots to Your Blueprint

To prevent automatic screenshot generation for your blueprint, add a `meta.screenshot` property to your `blueprint.json`:

```json
{
  "meta": {
    "title": "My Blueprint",
    "screenshot": "./my-custom-screenshot.jpg"
  }
}
```

Make sure the screenshot file exists in your blueprint folder.

## Technical Details

- **Language**: TypeScript (executed via tsx)
- **Browser Automation**: Playwright
- **Screenshot Format**: JPEG (70% quality)
- **Screenshot Dimensions**: 1280x800 viewport
- **Timeout Settings**: 180s for page load, 120s for network idle
- **CI Runner**: ubuntu-latest with Node.js 20

## Troubleshooting

### Screenshots Not Generated

- Check if the blueprint has a `meta.screenshot` property
- Verify the screenshot file exists if a path is specified
- Check GitHub Actions logs for any errors

### Gallery Not Updated

- Ensure GALLERY.md contains a reference to the blueprint
- Check that the blueprint path matches the expected pattern
- Verify the regex pattern matches your table structure

## Future Enhancements

Potential improvements:
- Differential updates (only re-shoot changed blueprints)
- Multiple screenshot sizes/formats
- Screenshot comparison to detect visual regressions
- Support for video captures or interactive demos
