# Contribution Guidelines

We encourage you to contribute your own Blueprints to this repository!

## Build your first Blueprint

Not sure how? Check out the [Blueprints 101](./docs/index.md).

## Submit your Blueprint to this repository

To keep the submission process smooth, please follow these guidelines:

1. Submit [a Pull Request (PR)](https://github.com/adamziel/blueprints/pulls) with your Blueprint.
2. The PR must contain a single `blueprint.json` file under the path `blueprints/your-blueprint-name/blueprint.json` (like [the examples here](https://github.com/adamziel/blueprints/tree/trunk/blueprints)).
3. Include all static files (WXR, ZIP, JPG, etc.) listed in the Blueprint in the submitted directory of your PR, and reference them via the `https://raw.githubusercontent.com` domain.
4. By submitting a Blueprint, you agree to license it under [GPLv2 or later license](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).

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
