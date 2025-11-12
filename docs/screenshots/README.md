# Blueprint Screenshots

This directory is no longer used. Screenshots are now stored directly in each Blueprint's directory.

## How it works

The `screenshots.yml` GitHub Actions workflow automatically generates screenshots for Blueprints that don't already have a `meta.screenshot` property pointing to an existing file in the repository.

- Screenshots are taken using Playwright by loading each Blueprint via `?blueprint-url=` on playground.wordpress.net with `?mode=seamless`
- The workflow waits for the progress bar to disappear before capturing screenshots
- Only the WordPress iframe (`iframe#wp`) is captured, not the Playground UI
- Images are saved as JPEG files with 70% quality to balance file size and visual quality
- Screenshots are saved as `screenshot.jpg` in each Blueprint's directory
- The workflow runs automatically on pull requests that modify blueprint.json files
- The workflow also runs weekly on Mondays at 3 AM UTC, or can be triggered manually via workflow_dispatch
- For pull requests from the same repository, screenshots are committed directly to the PR branch
- Existing screenshots are never replaced

## Location

Screenshots are stored in each Blueprint directory:

```
blueprints/
├── my-blueprint/
│   ├── blueprint.json
│   └── screenshot.jpg  # Auto-generated screenshot
└── another-blueprint/
    ├── blueprint.json
    └── screenshot.jpg
```
