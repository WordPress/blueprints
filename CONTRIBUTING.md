## Contributing Guidelines

We encourage you to contribute your own Blueprints to this repository!

### Building your first Blueprint

See the [Blueprints Crash Course](./docs/index.md).

### Submitting your Blueprint to this repository

To keep the submission process smooth, please follow the following guidelines:

Submit your Blueprint as a Pull Request to this repository.

Your Pull Request must contain a single `blueprint.json` file under a path `blueprints/your-blueprint-name/blueprint.json`.

All static files (WXR, ZIP, JPG etc.) referenced by the Blueprint must be included in the submitted directory in your Pull Request and referenced via the `raw.githubusercontent.com` domain.

By submitting a Blueprint, you agree to license it under GPLv2 or later license.

### Blueprint Metadata

Each Blueprint should include some basic metadata within the top-level "meta" key in the blueprint.json file. Here's what's required:

* Title: A clear and concise name for your Blueprint.
* Author (GitHub Username): Let others know who created the Blueprint.

Optionally, you can also include:

* Description: Provide a brief explanation of what your Blueprint offers.
* Categories: Specify relevant categories to help users find your Blueprint in the future Blueprints section on WordPress.org.

Here's an example:

```json
{
    "meta": {
        "title": "WooCommerce Developer Environment",
        "description": "A local development environment for WooCommerce that includes WP-CLI.",
        "author": "zieladam",
        "categories": ["woocommerce", "developer environment"]
    }
}
```

### Needing Help?

If you have questions or need assistance, start a new issue in this repository.
