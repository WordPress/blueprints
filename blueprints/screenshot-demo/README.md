# Screenshot Demo Blueprint

This blueprint demonstrates the automatic screenshot generation feature for WordPress Blueprints.

## What It Does

This blueprint:
1. Sets up a basic WordPress site with the Gutenberg plugin
2. Customizes the site name and description
3. Creates a welcome post explaining the screenshot feature
4. Sets the landing page to the homepage

## How Screenshots Are Generated

When this blueprint is added or modified in a PR:

1. The GitHub Actions workflow `take-screenshots.yml` automatically runs
2. It launches a WordPress Playground instance with this blueprint
3. Playwright captures screenshots:
   - `preview.png` - The full Playground interface
   - `wordpress.png` - Just the WordPress content
4. Screenshots are saved to the `screenshots/` directory
5. The workflow commits them back to the PR

## Testing Screenshot Generation

To test screenshot generation locally:

```bash
# From the repository root
./test-screenshot-locally.sh blueprints/screenshot-demo
```

Or using the Python script:

```bash
python generate_screenshots.py blueprints/screenshot-demo
```

## Expected Results

After running the screenshot generation, you should see:
- `blueprints/screenshot-demo/screenshots/preview.png` - Full Playground view
- `blueprints/screenshot-demo/screenshots/wordpress.png` - WordPress homepage

The screenshots will show the customized site with the welcome post.

## Purpose

This demo blueprint serves as:
- A reference example for blueprint authors
- A test case for the screenshot generation system
- Documentation of expected screenshot behavior

## See Also

- [Screenshot Generation Documentation](../../docs/screenshot-generation.md)
- [Blueprint Screenshots Guide](../../docs/blueprint-screenshots-guide.md)
- [Contributing Guidelines](../../CONTRIBUTING.md)
