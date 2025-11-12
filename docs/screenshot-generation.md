# Screenshot Generation for Blueprints

This document describes the automated screenshot generation system for WordPress Blueprints in this repository.

## Overview

The screenshot generation system automatically captures visual previews of WordPress Playground instances running each blueprint. These screenshots help users quickly see what each blueprint creates without having to launch the Playground themselves.

## How It Works

### Automated Generation (GitHub Actions)

When a pull request is opened or updated that modifies any `blueprint.json` files, the `take-screenshots.yml` workflow automatically:

1. Detects which blueprints have been added or modified
2. Launches a WordPress Playground instance for each changed blueprint
3. Captures screenshots using Playwright
4. Commits the screenshots back to the PR
5. Adds a comment to the PR indicating screenshots were generated

The workflow generates two types of screenshots for each blueprint:

- **preview.png**: A screenshot of the entire Playground interface
- **wordpress.png**: A screenshot of just the WordPress content (inside the iframe)

Screenshots are stored in a `screenshots/` subdirectory within each blueprint's directory.

### Manual Generation

You can also generate screenshots manually using the `generate_screenshots.py` script:

```bash
# Install Playwright if not already installed
npm install -g playwright
npx playwright install chromium

# Generate screenshots for all blueprints
python generate_screenshots.py

# Generate screenshots for specific blueprints
python generate_screenshots.py blueprints/latest-gutenberg blueprints/wpcli-post-with-image
```

## Screenshot Storage

Screenshots are stored in the following structure:

```
blueprints/
├── my-blueprint/
│   ├── blueprint.json
│   ├── screenshots/
│   │   ├── preview.png      # Full Playground view
│   │   └── wordpress.png    # WordPress content only
│   └── other-files...
```

## Using Screenshots in the Gallery

Screenshots can be displayed in the GALLERY.md by modifying the table generation logic in `reindex_postprocess.py`. For example, you could add a screenshot column that displays the preview image:

```markdown
![Preview](blueprints/my-blueprint/screenshots/preview.png)
```

## Configuration

### Workflow Triggers

The screenshot generation workflow is triggered by:

- Pull requests that modify `blueprints/**/blueprint.json` files
- Manual workflow dispatch (can be triggered from the Actions tab)

### Screenshot Settings

Screenshots are captured with the following settings:

- **Viewport size**: 1280x720 pixels
- **Browser**: Chromium (via Playwright)
- **Wait time**: Up to 2 minutes for Playground to load
- **Additional delay**: 5 seconds after iframe loads for content to render

### Customization

To customize the screenshot capture process, modify:

- **Workflow file**: `.github/workflows/take-screenshots.yml`
- **Python script**: `generate_screenshots.py`

You can adjust:
- Viewport dimensions
- Wait times and timeouts
- Screenshot format and quality
- File naming conventions

## Troubleshooting

### Screenshot Generation Fails

If screenshot generation fails, check:

1. **Blueprint URL validity**: Ensure the blueprint uses valid `raw.githubusercontent.com` URLs
2. **Playground loading**: The blueprint may take too long to load (increase timeout)
3. **Network issues**: Playground.wordpress.net may be temporarily unavailable
4. **Browser compatibility**: Ensure Chromium is properly installed

### Screenshots Are Blank or Incomplete

If screenshots are captured but don't show expected content:

1. **Increase wait times**: Some blueprints need more time to load
2. **Check landing page**: Ensure the blueprint specifies a meaningful landing page
3. **Verify blueprint works**: Test the blueprint manually in Playground

### Manual Testing

To test screenshot generation locally:

```bash
# Test with a single blueprint
python generate_screenshots.py blueprints/latest-gutenberg

# Check the output
ls -la blueprints/latest-gutenberg/screenshots/
```

## Future Enhancements

Potential improvements to the screenshot system:

1. **Multiple screenshots**: Capture different pages (frontend, admin, editor)
2. **Thumbnail generation**: Create smaller thumbnail versions for the gallery
3. **Screenshot comparison**: Show before/after for blueprint updates
4. **Responsive screenshots**: Capture at different viewport sizes
5. **Video capture**: Record short video demos of blueprints in action
6. **Caching**: Skip regeneration if blueprint hasn't changed
7. **Parallel processing**: Generate multiple screenshots simultaneously

## Related Files

- `.github/workflows/take-screenshots.yml` - GitHub Actions workflow
- `generate_screenshots.py` - Python screenshot generation script
- `GALLERY.md` - Gallery page that could display screenshots
- `reindex_postprocess.py` - Script that generates the gallery table

## Resources

- [Playwright Documentation](https://playwright.dev/)
- [WordPress Playground](https://developer.wordpress.org/playground/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
