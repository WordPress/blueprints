# Blueprint Screenshots

This directory contains automatically generated screenshots for Blueprints in the gallery.

## How it works

The `screenshots.yml` GitHub Actions workflow automatically generates screenshots for Blueprints that don't already have a `meta.screenshot` property pointing to an existing file in the repository.

- Screenshots are taken using Playwright by loading each Blueprint via `?blueprint-url=` on playground.wordpress.net
- Only the WordPress iframe (`#wp-playground`) is captured, not the entire Playground UI
- Images are saved as JPEG files with 70% quality to balance file size and visual quality
- The workflow runs weekly on Mondays at 3 AM UTC, or can be triggered manually via workflow_dispatch

## Directory structure

```
docs/screenshots/
├── README.md
└── <blueprint-slug>.jpg  # One screenshot per blueprint
```

Each screenshot filename corresponds to the blueprint folder name (slug) in the `blueprints/` directory.
