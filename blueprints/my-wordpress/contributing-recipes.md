# Contributing Recipes for My WordPress

Recipes are guided workflows shown by My WordPress. They are not a general plugin or app catalog. A good recipe helps someone complete a real task, explains why each step matters, and points to the exact app, plugin, or admin screen needed next.

Recipe contributions live in [blueprints/my-wordpress/recipes.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/recipes.json). If a recipe depends on a new plugin or app, contribute that entry first or include it in the same pull request with a clear explanation. For app and plugin submissions, see [Submitting Plugins and Apps](./submitting-plugins-and-apps.md).

## When to Add a Recipe

Add or update a recipe when the workflow is broader than installing one app.

Good recipe candidates:

- They have a clear outcome, such as building a family blog, setting up a reading hub, or importing personal data.
- They combine multiple steps into a useful sequence.
- They include enough context for a user to decide whether the workflow fits their site.
- They leave users at a concrete next action, not a vague recommendation.

Avoid recipe changes when the contribution is only a single app listing, a plugin recommendation without a workflow, or a promotional description that does not help users act.

## Recipe Structure

Each top-level key in [recipes.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/recipes.json) is a stable recipe slug. A recipe should include:

- `title`: short action-oriented name.
- `tagline`: one-line promise shown with the recipe.
- `description`: user-facing summary of the workflow and why it matters.
- `icon`: short visual marker for the recipe card.
- `gradient`: CSS gradient used by the recipe card.
- `learn_more`: optional URL for deeper background.
- `steps`: ordered list of actions.

Example:

```json
{
	"example-workflow": {
		"title": "Build an Example Workflow",
		"tagline": "A short promise for the user",
		"description": "Explain what the workflow helps users do and why the steps belong together.",
		"icon": "E",
		"gradient": "linear-gradient(135deg, #2563eb 0%, #16a34a 100%)",
		"learn_more": "https://example.com/example-workflow",
		"steps": []
	}
}
```

## Step Types

Every step should have a `type`, `title`, and `description`. Use `optional: true` for useful additions that are not required for the core workflow. Use `context` when a step only applies in a specific environment, such as `self-hosted`.

Use a `note` step for instructions or admin screens:

```json
{
	"type": "note",
	"title": "Write the first post",
	"description": "Start with one post so the rest of the setup has something real to support.",
	"url": "/wp-admin/post-new.php",
	"url_label": "Open New Post"
}
```

Use a `plugin` step for an entry from [blueprints/my-wordpress/plugins.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/plugins.json):

```json
{
	"type": "plugin",
	"slug": "example-plugin",
	"title": "Install Example Plugin",
	"description": "Explain exactly why this plugin belongs in the workflow.",
	"optional": true
}
```

Use an `app` step for a Blueprint registered in [apps.json](https://github.com/WordPress/blueprints/blob/trunk/apps.json):

```json
{
	"type": "app",
	"path": "apps/example-app.json",
	"title": "Install Example App",
	"description": "Explain what this app unlocks for the workflow."
}
```

Use a `github` step for a plugin installed from a GitHub repository:

```json
{
	"type": "github",
	"repo": "example/example-plugin",
	"title": "Install Example Plugin",
	"description": "Explain why this GitHub-hosted plugin is part of the workflow.",
	"optional": true
}
```

## Writing Guidelines

Keep recipes practical and specific:

- Write for someone setting up their own WordPress, not for reviewers.
- Keep titles short and action-oriented.
- Explain the purpose of each step, not just what button it installs.
- Put required setup before optional enhancements.
- Use optional steps sparingly so the main path stays clear.
- Prefer existing plugin or app entries over duplicating install instructions.
- Keep external-service, paid-feature, privacy, or account requirements visible in the relevant step description.

## Test the Submission

Before opening a pull request:

1. Format [recipes.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/recipes.json) with the repository's Prettier settings if available:

	```bash
	npx prettier --write blueprints/my-wordpress/recipes.json
	```

2. Check every referenced dependency:

	- `plugin` steps use slugs that exist in [blueprints/my-wordpress/plugins.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/plugins.json).
	- `app` steps use paths that exist in [apps.json](https://github.com/WordPress/blueprints/blob/trunk/apps.json).
	- `github` steps use reachable `owner/repo` values.
	- `note` steps with URLs open a useful screen.

3. Read the recipe as a user flow from top to bottom. The sequence should still make sense if optional steps are skipped.

The pull request will run a GitHub Action that validates JSON syntax, checks app and My WordPress Blueprint files against the WordPress Playground Blueprint schema, and checks catalog files against the My Apps schemas. You can run the same check locally with `npm run validate:my-wordpress-json`.

## Open the Pull Request

[Open a pull request](https://github.com/WordPress/blueprints/compare) against the `wordpress/blueprints` repository.

Include:

- The workflow the recipe helps users complete.
- Any new plugin or app entries the recipe depends on.
- Notes about external services, API keys, paid features, tracking, or privacy-sensitive behavior.
- A short explanation of why each required step belongs in the sequence.

Keep the pull request focused. A recipe-only submission should usually touch [blueprints/my-wordpress/recipes.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/recipes.json) and, if needed, the plugin or app files required by the recipe.

## Review Checklist

Reviewers should be able to answer yes to these questions:

- Does the recipe help users complete a real workflow?
- Are the required steps ordered correctly?
- Are optional steps clearly optional?
- Do plugin, app, and GitHub references resolve?
- Are dependencies and external services disclosed?
- Is the writing clear enough for someone who has not seen the pull request?
- Is [recipes.json](https://github.com/WordPress/blueprints/blob/trunk/blueprints/my-wordpress/recipes.json) valid and consistently formatted?
