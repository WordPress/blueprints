# WordPress Blueprints Community Gallery

Welcome to the WordPress Blueprints Community Gallery!
This is a space for WordPress developers to share and explore pre-configured WordPress setups, also known as Blueprints.

Here you'll find a collection of Blueprints for various purposes, from pre-configured WooCommerce stores to custom development environments.

Ready to jump in?

* Browse Blueprints – See the current list of available Blueprints below.
* Create your first Blueprint – Get started by following the official guide on creating Blueprints.

## Available Blueprints

* Latest Gutenberg plugin – [Preview](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/v1-examples/latest-gutenberg/blueprint.json) | [Source](https://github.com/adamziel/blueprints/blob/trunk/v1-examples/latest-gutenberg/blueprint.json)


## Contributing your Blueprint

We encourage you to contribute your own Blueprints to this repository! To make the process smooth, follow these guidelines:

* Create a Pull Request: Submit a Pull Request to this repository.
* Blueprint Structure: Each Blueprint should reside in a new directory within the v1-examples folder. This directory must include a blueprint.json file.
* Include Static Files: Any static files your Blueprint uses (like WXR, ZIP, or JPG files) should be included within your submitted directory and referenced using the raw.githubusercontent.com domain.
* Licensing: By submitting a Blueprint, you agree to license it under the GPLv2 or later license.

See [getting started with Blueprints](https://w.org/@TODO) for more information on how to create one.

## Publishing guidelines

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

## Needing Help?

If you have questions or need assistance, feel free to start a new issue in this repository: @TODO.

## Let's Build the Blueprint Community Together!

This is a minimal version 1 (v1) to get the community space up and running quickly. We plan to build upon this foundation based on your feedback. So, explore, create, and share your Blueprints!
