# Contribution Guidelines

We encourage you to contribute your own Blueprints to this repository!

## Build your first Blueprint

Not sure how? Check out the [Blueprints 101](./docs/index.md).

## Submit your Blueprint to this repository

To keep the submission process smooth, please follow these guidelines:

Submit [a Pull Request (PR)](https://github.com/adamziel/blueprints/pulls) with your Blueprint.

The PR should contain:

* A single `blueprint.json` file under the path `blueprints/your-blueprint-name/blueprint.json` (like [the examples here](https://github.com/adamziel/blueprints/tree/trunk/blueprints)).
* All the static files (WXR, ZIP, JPG, etc.) your Blueprint references. The static files must be loaded via the `https://raw.githubusercontent.com` URL pointing to your branch.

For example, if you want to load `a content-export.xml` file and your branch is called `woocommerce-subscriptions`, then your PR must contain a:

* A `blueprints/woocommerce-subscriptions/blueprint.json` file
* A `blueprints/woocommerce-subscription/content-export.xml` file the Blueprint should reference as follows:

```json
{
	"steps": [
		{
			"step": "importWxr",
			"file": {
				"resource": "url",
				"url": "https://raw.githubusercontent.com/adamziel/blueprints/woocommerce-subscriptions/blueprints/woocommerce-subscriptions/content-export.xml"
			}
		}
	]
}
```

By submitting a Blueprint, you agree to license it under [GPLv2 or later license](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).

Make sure to correctly indent your Blueprints using tabs using a code formatter like [Prettier](https://prettier.io/) – this repository ships a `.prettierrc` file you could use. This is mostly to help the reviewers understand your Blueprint better. Every accepted and merged Blueprint will automatically be re-formatted using the `.prettierrc` file.

## Blueprint metadata

Each Blueprint should include metadata within the top-level "meta" key of the `blueprint.json` file.

Here's what's required:

-   **Title:** a clear and concise name for your Blueprint.
-   **Author:** your GitHub username, to let others know who created the Blueprint.

Optionally, you can add:

-   **Description:** a brief explanation of what the Blueprint offers.
-   **Categories:** specify relevant categories to help users find your Blueprint in the future Blueprints section on WordPress.org.

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

## Need help?

If you have questions or comments, [open a new issue](https://github.com/adamziel/blueprints/issues) in this repository.
