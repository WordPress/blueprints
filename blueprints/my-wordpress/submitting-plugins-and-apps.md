# Submitting Plugins and Apps for My WordPress

This guide explains how to test and propose a plugin or app for the My WordPress experience in this repository.

My WordPress uses the [My Apps](https://github.com/akirk/my-apps) plugin for its launcher and app store. There are three related paths:

- **Temporary app:** a complete WordPress Playground `blueprint.json` pasted into My Apps for local testing.
- **Plugin entry:** a single WordPress plugin listed in `blueprints/my-wordpress/plugins.json` for the curated app store.
- **Permanent app:** a complete Playground Blueprint in `apps/*.json`, usually installing one or more plugins and opening a useful landing page after setup.

If you want to submit a full Blueprint to the public Blueprints gallery instead, follow the repository-wide [Contribution Guidelines](../../CONTRIBUTING.md).

## First: Test in My Apps

Before opening a pull request, test the app through the My Apps flow for [temporarily adding an app from a Blueprint](https://github.com/akirk/my-apps#temporarily-adding-an-app-from-a-blueprint).

Create a complete WordPress Playground `blueprint.json` with `meta.title`, `meta.description`, and `meta.author`. My Apps reads those fields to create the app-store entry.

If you do not want to write the Blueprint by hand, use the [WordPress Playground Step Library](https://akirk.github.io/playground-step-library/) to build it interactively. The Step Library can launch the Blueprint in Playground and copy the generated Blueprint JSON to your clipboard, ready to paste into My Apps.

```json
{
	"$schema": "https://playground.wordpress.net/blueprint-schema.json",
	"meta": {
		"title": "My Custom App",
		"description": "Installs my custom WordPress app.",
		"author": "Your Name"
	},
	"landingPage": "/wp-admin/",
	"steps": [
		{
			"step": "installPlugin",
			"pluginData": {
				"resource": "wordpress.org/plugins",
				"slug": "gutenberg"
			},
			"options": {
				"activate": true
			}
		}
	]
}
```

To install it temporarily:

1. Open My Apps at `/my-apps/`.
2. Choose **Add**.
3. Paste the complete Blueprint anywhere in the App Store. On mobile, focus the Search field and paste it there.
4. Install the temporary app from the app-store entry My Apps creates.

If your Blueprint's title matches an existing app, My Apps can temporarily override that app with the pasted Blueprint. Custom and modified entries are stored only in the current browser and can be removed or reverted from their badge in the App Store.

## Before You Submit

Make sure the plugin or app is ready for a fresh WordPress Playground site:

- It installs without manual file uploads.
- It activates cleanly on a new site.
- It does not require secrets, private API keys, or credentials to run its basic flow.
- Any paid service, external account, tracking, or network dependency is clearly disclosed.
- The code is GPL-compatible if it is submitted to this repository or distributed through WordPress.org.
- The first screen after installation gives users a clear next step.
- The pasted My Apps test flow works before you ask for permanent inclusion.

If the plugin should be installed from the WordPress.org Plugin Directory, submit it there first. WordPress.org expects a complete plugin ZIP, a valid WordPress.org account, a checked email address, and compliance with the Plugin Directory guidelines. Once the plugin has an approved directory slug, My WordPress can install it with the `wordpress.org/plugins` resource.

## Option 1: Submit a Plugin Entry

Use this path when one existing plugin is enough and no custom setup Blueprint is needed. If the plugin itself should create a launcher icon after installation, it can register with My Apps by filtering `my_apps_plugins` in the plugin code.

Add an object to `blueprints/my-wordpress/plugins.json` using the plugin slug as the key:

```json
{
	"example-plugin": {
		"title": "Example Plugin",
		"author": "Example Author",
		"note": "A short, user-facing explanation of what this adds to My WordPress.",
		"categories": ["Productivity"],
		"landing_page": "/wp-admin/admin.php?page=example-plugin"
	}
}
```

Common fields:

- `note`: required in practice; keep it short and explain the user benefit.
- `categories`: one or more labels used for browsing.
- `landing_page`: optional, but recommended when the plugin has a settings page, dashboard, or frontend route.
- `title` and `author`: recommended when the display name is not obvious from the slug.
- `github`: optional GitHub repository in `owner/repo` form when the plugin should be fetched from GitHub.
- `branch`: optional branch name when the installable build lives outside the default branch, for example `dist/main`.
- `url`: optional direct ZIP URL, commonly a GitHub release asset.

Prefer a WordPress.org slug for broadly distributed plugins. Use `github`, `branch`, or `url` only when the plugin is not in the Plugin Directory or needs a specific build.

## Option 2: Submit an App

Use this path when the My Apps pasted-Blueprint test works and the experience needs permanent inclusion in the curated app store. This is most useful for companion plugins, setup code, imported starter data, custom options, or a specific landing page.

Create a new Blueprint file in `apps/`, for example `apps/example-app.json`:

```json
{
	"$schema": "https://playground.wordpress.net/blueprint-schema.json",
	"meta": {
		"title": "Example App",
		"description": "A short description of what the app lets users do.",
		"author": "Example Author",
		"categories": ["Apps", "Productivity"]
	},
	"landingPage": "/wp-admin/admin.php?page=example-app",
	"steps": [
		{
			"step": "installPlugin",
			"pluginData": {
				"resource": "wordpress.org/plugins",
				"slug": "example-plugin"
			},
			"options": {
				"activate": true
			}
		}
	]
}
```

Then register the app in `apps.json`:

```json
{
	"apps/example-app.json": {
		"title": "Example App",
		"description": "A short description of what the app lets users do.",
		"author": "Example Author",
		"categories": ["Apps", "Productivity"]
	}
}
```

App Blueprints can use normal Playground steps, including `installPlugin`, `setSiteOptions`, `runPHP`, `importWxr`, and file operations. Keep setup steps idempotent where possible so the app does not damage existing content if installed more than once.

The `meta.title`, `meta.description`, and `meta.author` fields should match the temporary Blueprint you tested in My Apps. If you intentionally replace or update an existing app, use the same title during temporary testing so reviewers can verify the override behavior.

For plugin installs, use one of these resource patterns:

```json
{
	"resource": "wordpress.org/plugins",
	"slug": "friends"
}
```

```json
{
	"resource": "url",
	"url": "https://github.com/example/example-plugin/releases/latest/download/example-plugin.zip"
}
```

```json
{
	"resource": "git:directory",
	"url": "https://github.com/example/example-plugin",
	"ref": "main",
	"refType": "branch"
}
```

If the GitHub repository contains extra build files or the plugin folder name should be stable, set `targetFolderName` in the install options:

```json
{
	"options": {
		"activate": true,
		"targetFolderName": "example-plugin"
	}
}
```

## Option 3: Add It to a Recipe

Recipes are guided workflows shown by My WordPress. If the plugin or app fits an existing workflow, add a step to `blueprints/my-wordpress/recipes.json`.

For a plugin:

```json
{
	"type": "plugin",
	"slug": "example-plugin",
	"title": "Install Example Plugin",
	"description": "Explain why this belongs in the workflow.",
	"optional": true
}
```

For an app:

```json
{
	"type": "app",
	"path": "apps/example-app.json",
	"title": "Install Example App",
	"description": "Explain what this app unlocks for the workflow.",
	"optional": true
}
```

Only add a recipe entry when the plugin or app has a clear role in that recipe. Do not use recipes as a general catalog.

## Test the Submission

Before opening a pull request:

1. Validate every edited JSON file:

	```bash
	python -m json.tool blueprints/my-wordpress/plugins.json >/dev/null
	python -m json.tool blueprints/my-wordpress/recipes.json >/dev/null
	python -m json.tool apps/example-app.json >/dev/null
	python -m json.tool apps.json >/dev/null
	```

2. Format JSON with the repository's Prettier settings if available:

	```bash
	npx prettier --write blueprints/my-wordpress/plugins.json blueprints/my-wordpress/recipes.json apps/example-app.json apps.json
	```

3. Try the app Blueprint in WordPress Playground. For a branch on GitHub, use a URL like:

	```text
	https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wordpress/blueprints/YOUR-BRANCH/apps/example-app.json
	```

4. Confirm the install flow:

	- Pasting the complete Blueprint into My Apps creates the expected temporary app-store entry.
	- The plugin or app installs without errors.
	- The expected plugins are active.
	- `landingPage` opens a useful screen.
	- Any optional external service requirement is visible to the user.
	- The uninstall/deactivate path does not leave the site broken.

## Open the Pull Request

Submit a pull request against the `wordpress/blueprints` repository.

Include:

- The plugin slug, GitHub repository, or ZIP URL being installed.
- A short explanation of why it belongs in My WordPress.
- Screenshots or a short screen recording of the install result.
- The complete Blueprint used for the My Apps paste test, or a link to it.
- A Playground test URL for permanent app Blueprints.
- Notes about external services, API keys, paid features, tracking, or privacy-sensitive behavior.
- Confirmation that the code is GPL-compatible or already approved in the WordPress.org Plugin Directory.

Keep the pull request focused. A plugin-only submission should usually touch `blueprints/my-wordpress/plugins.json` and, if appropriate, `blueprints/my-wordpress/recipes.json`. An app submission should usually add one file under `apps/`, update `apps.json`, and optionally update `recipes.json`.

## Review Checklist

Reviewers should be able to answer yes to these questions:

- Is the value clear from the title, description, and note?
- Does the temporary My Apps paste flow create the expected app-store entry?
- Does the install work in a clean Playground instance?
- Is the landing page useful immediately after installation?
- Are dependencies and external services disclosed?
- Is the submitted app broader than a single plugin entry, or should it be simplified?
- Does the recipe placement help users complete a real workflow?
- Are JSON files valid and consistently formatted?

If something is uncertain, open an issue or draft pull request first and describe the intended user flow.
