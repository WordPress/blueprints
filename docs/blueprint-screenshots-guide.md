# Blueprint Screenshots Guide

## For Blueprint Authors

When you submit a blueprint to this repository, screenshots are automatically generated to showcase your work in the gallery. Here's what you need to know:

### How Screenshots Are Generated

1. **Automatic Generation**: When you open a pull request with a new or updated blueprint, the GitHub Actions workflow automatically generates screenshots.

2. **What Gets Captured**: The workflow captures two screenshots:
   - **Full Playground View** (`preview.png`): Shows the entire Playground interface
   - **WordPress Content** (`wordpress.png`): Shows only the WordPress site content

3. **Storage Location**: Screenshots are saved in `blueprints/your-blueprint-name/screenshots/`

### Screenshot Requirements

Your blueprint should be designed for good screenshots:

- **Specify a landing page**: Use the `landingPage` property to show the best view of your blueprint
- **Use visual content**: Blueprints with content, themes, or plugins show better than minimal setups
- **Consider timing**: Ensure your blueprint loads reasonably quickly (< 2 minutes)

### Example Blueprint Configuration for Best Screenshots

```json
{
    "meta": {
        "title": "My Awesome Blueprint",
        "description": "A beautiful WordPress site with custom theme",
        "author": "yourusername"
    },
    "plugins": ["gutenberg"],
    "themes": ["twentytwentyfour"],
    "landingPage": "/",  // Show the homepage for better screenshot
    "steps": [
        {
            "step": "importWxr",
            "file": {
                "resource": "url",
                "url": "https://raw.githubusercontent.com/wordpress/blueprints/your-branch/blueprints/your-blueprint/content.xml"
            }
        }
    ]
}
```

### Customizing Screenshots

If the automatic screenshot doesn't capture the best view of your blueprint:

1. **Adjust the landing page**: Change the `landingPage` property to show a different page
2. **Add delay steps**: If content needs time to load, add a delay step
3. **Pre-populate content**: Include starter content or imports so there's something to see

### Troubleshooting Screenshots

**Screenshot is blank or shows an error:**
- Ensure your blueprint URLs are correct and accessible
- Test your blueprint manually in Playground first
- Check that external resources (WXR files, images, etc.) are publicly accessible

**Screenshot shows loading screen:**
- Your blueprint may take too long to load
- Consider simplifying or optimizing your blueprint
- Check if external dependencies are slow to download

**Screenshot doesn't show the best view:**
- Adjust the `landingPage` property
- Consider what page best represents your blueprint
- For admin features, use `landingPage: "/wp-admin/..."`

### Manual Screenshot Generation

If you want to generate screenshots locally before submitting:

```bash
# Install Playwright
npm install -g playwright
npx playwright install chromium

# Generate screenshot for your blueprint
python generate_screenshots.py blueprints/your-blueprint-name

# Or use the test script
./test-screenshot-locally.sh blueprints/your-blueprint-name
```

### Screenshot Display in Gallery

Screenshots are displayed in the gallery table alongside your blueprint description. They help users:
- Quickly see what your blueprint creates
- Understand the visual style and layout
- Decide if they want to try your blueprint

### Best Practices

1. **Use descriptive landing pages**: Choose a page that best represents your blueprint
2. **Include visual content**: Themes, custom layouts, and media make better screenshots
3. **Test before submitting**: Generate screenshots locally to preview how they'll look
4. **Keep blueprints simple**: Complex blueprints may timeout during screenshot generation
5. **Use stable resources**: Ensure external URLs are reliable and fast

### What Makes a Good Screenshot?

✅ **Good Screenshot Examples:**
- Homepage with a custom theme and sample content
- Admin dashboard showing installed plugins and configuration
- Post editor with custom blocks or features
- Store page with products displayed

❌ **Poor Screenshot Examples:**
- Default WordPress installation with no content
- Error or loading screens
- Blank admin pages
- Generic login screens

### Getting Help

If you have issues with screenshot generation:

1. Check the [screenshot generation documentation](./screenshot-generation.md)
2. Review the workflow logs in your PR's "Actions" tab
3. Ask for help in the PR comments
4. Test locally using the provided scripts

### Technical Details

- **Viewport**: 1280x720 pixels
- **Browser**: Chromium (latest)
- **Timeout**: 2 minutes for Playground to load
- **Wait time**: 5 seconds after iframe appears
- **Format**: PNG
- **Location**: `blueprints/{name}/screenshots/`

## For Reviewers

When reviewing PRs with blueprints:

1. Check that screenshots were generated automatically
2. Verify screenshots accurately represent the blueprint
3. Ensure screenshots show appropriate content (no NSFW, etc.)
4. Confirm screenshot file sizes are reasonable (< 1MB preferred)

## Future Enhancements

Planned improvements to screenshot generation:

- [ ] Multiple screenshots per blueprint (frontend, admin, editor)
- [ ] Video demos/GIF animations
- [ ] Responsive screenshots (mobile, tablet, desktop)
- [ ] Before/after comparisons for updates
- [ ] Interactive previews
- [ ] Screenshot optimization/compression
- [ ] Caching to avoid regenerating unchanged blueprints
