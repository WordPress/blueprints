# Contribution Guidelines

We encourage you to contribute your own Blueprints to this repository!

## Build your first Blueprint

Not sure how? Check out the [Blueprints 101](./docs/index.md).

## Submit your Blueprint to this repository

To keep the submission process smooth, please follow these guidelines:

Submit [a Pull Request (PR)](https://github.com/adamziel/blueprints/pulls) with your Blueprint. Consult this page [Creating a pull request](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request) if you need a refresher on the process.

The PR should contain:

-   A single `blueprint.json` file under the path `blueprints/your-blueprint-name/blueprint.json` (like [the examples here](https://github.com/wordpress/blueprints/tree/trunk/blueprints)).
-   All the static files (WXR, ZIP, JPG, etc.) your Blueprint references. Load each file from the pull request's repository and branch with a URL following the `https://raw.githubusercontent.com/${user}/${repo}/${branch}/${path}` pattern. For a pull request from a fork, `${user}/${repo}` is the fork—not `WordPress/blueprints`—because the branch and file do not exist upstream until the pull request is merged. Use `raw.githubusercontent.com`, not a `github.com/.../blob/...` page, which returns HTML instead of the file.

For example, to load `content-export.xml`, create a `blueprints/woocommerce-subscriptions` directory containing:

-   A `blueprints/woocommerce-subscriptions/blueprint.json` file
-   A `blueprints/woocommerce-subscriptions/content-export.xml` file

If the pull request comes from `example-contributor/blueprints` on the `feature/woo-subscription` branch, reference the file as follows:

```json
{
	"steps": [
		{
			"step": "importWxr",
			"file": {
				"resource": "url",
				"url": "https://raw.githubusercontent.com/example-contributor/blueprints/feature/woo-subscription/blueprints/woocommerce-subscriptions/content-export.xml"
			}
		}
	]
}
```

Pull request validation checks that raw URLs use the exact head repository and branch, or upstream `trunk`, and can be fetched. URLs from the pull request branch must point to files inside the same Blueprint directory. After a pull request is merged, repository automation rewrites fork and feature-branch attachment URLs to the upstream `trunk` form.

By submitting a Blueprint, you agree to license it under [GPLv2 or later license](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).

Make sure to correctly indent your Blueprints using tabs using a code formatter like [Prettier](https://prettier.io/). This repository ships a `.prettierrc` file you could use. This is mostly to help the reviewers understand your Blueprint better. Every accepted and merged Blueprint will automatically be re-formatted using the `.prettierrc` file.

## Blueprint metadata

Each Blueprint should include metadata within the top-level "meta" key of the `blueprint.json` file. Note that metadata is not required for all Blueprints, only for Blueprints submitted to this gallery.

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

## App icons

A Blueprint in the `Apps` category can declare an icon for the My Apps App Store in an `app-meta.json` file next to `blueprint.json`. The `icon` is either a Dashicon name (`"dashicons-book"`), a short emoji, or a URL.

When the icon really lives in the plugin's own repository, point `icon` at it there — you don't need to vendor the file yourself:

```json
{
	"icon": "https://raw.githubusercontent.com/my-org/my-plugin/main/assets/icon.svg"
}
```

When your pull request touches a Blueprint whose `icon` names an upstream repository, a CI job fetches it and commits a local copy to your branch, the same way missing screenshots are filled in. Only the Blueprints your branch touches are synced. The catalog (`apps.json`) always serves that local copy, never the upstream URL directly, so the App Store isn't exposed to an upstream rename or deletion.

After that, a weekly workflow refetches every such `icon` and opens a pull request when the vendored copy has fallen behind, so the plugin repository stays the source of truth while this repository keeps serving the file. Run `npm run sync:app-icons` to do the same locally, or `npm run sync:app-icons -- --check` to report drift without writing.

If the icon has no upstream copy — it was drawn just for this repository — vendor it yourself and point `icon` directly at your own Blueprint directory on this repository's trunk instead:

```json
{
	"icon": "https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprints/my-app/icon.svg"
}
```

Design the icon with its own padding. Both the launcher and the App Store draw it at the full size of a rounded tile, and the App Store crops with `object-fit: cover`, so artwork that reaches the edge of its canvas loses its corners.

## Blueprint screenshots

To help your Blueprint stand out in the gallery, include a screenshot alongside `blueprint.json`:

-   Add a JPEG named `screenshot.jpg` inside your Blueprint directory (for example, `blueprints/my-blueprint/screenshot.jpg`). JPEG keeps file sizes small and matches the automation that builds the site.
-   Shoot in a landscape aspect ratio (≈16:9) at about 1600–2000px wide so the preview stays sharp on Retina displays, and try to keep it under ~500 KB.
-   If you don't provide a screenshot, a CI job will generate one for you automatically.

## Need help?

If you have questions or comments, [open a new issue](https://github.com/wordpress/blueprints/issues) in this repository.
