## Contributing your Blueprint

We encourage you to contribute your own Blueprints to this repository! To make the process smooth, please follow these guidelines:

To add a Blueprint to this repository, submit it as a Pull Request to this repository.

Your Pull Request must contain a single, new directory created in the `v1-examples` with a `blueprint.json` file in it.

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
