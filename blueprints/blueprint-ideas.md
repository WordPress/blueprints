## Proof of concepts for Blueprints
Support for [10 Initial Blueprints](https://github.com/adamziel/blueprints/issues/1) for the Community Gallery

Here are a list of ideas of what you could conceivably do with Blueprints.

### Blueprint Ideas

---

#### Design & Development
Create a new custom block
- scaffold a new block via the `create-block` package
    - include steps for build setup
    - uses CLI input to bring variables to create block ingredients

Create a new block theme
- `create-block-theme` 

Create a new block plugin
- `create-block-plugin` 
    - scaffold a plugin via `WP-CLI`
    - or try using the [Better Plugin Boilerplate](https://github.com/TukuToi/better-wp-plugin-boilerplate)

Create a block library
- Block Library - create a block library
    - a blueprint that creates a block library
    - or create an interface to create multiple blocks

Work with patterns
- Pattern UI
    - create a pattern based on `WP-CLI` input
    - [all about Patterns](https://github.com/WordPress/Documentation-Issue-Tracker/issues/1520)
    - after registration, create pages based on pattern slug and navigate to all patterns

Hooks, Actions, and Filters (oh my!)
- Work with `PHP` in WordPress
    - [example](https://plugins.svn.wordpress.org/webtoffee-product-feed/assets/blueprints/blueprint.json) for using `add_filter` with a `mu-pugin`  
        - Needs fix per [Slack convo](https://wordpress.slack.com/archives/C04EWKGDJ0K/p1712656197432919)
    - Another example 

#### Create content
- Generate content using Fakerpress (plugin demo, content), navigate to Pages
- Markdown to WordPress - Create WordPress posts from a Markdown file
 - read file via `wp-cli`
 - create a new post
 - how to handle taxonomy and meta, etc.
 - sets the stage for migrating all Markdown related docs pages to WordPress
 - also sets the stage to standardize Gutenberg for documentation in other Open Source Software projects!!

#### Working with content
Working with the datastore
- Get data from WordPress in JavaScript (`use` hooks)
- `useSelect` example
- Working with a custom datastore


#### Testing, Debugging
Testing
- Theme Unit Test - [must fix(https://github.com/WordPress/wordpress-playground/issues/718)] [Add CORS headers]
- WordPress Coding Standards (`WPCS`) - install Plugin Review Check and run on a plugin
- Theme Check - install `Theme Check` [plugin](https://wordpress.org/plugins/theme-check/) and run on a theme

Debugging 
- Using the [Debug Bar](https://wordpress.org/plugins/debug-bar/) in your Playground

#### Demos, Sharing, Collaboration, Teaching
- Demo a plugin with a blueprint that imports relevant content
- Share a blueprint with a colleague
- Collaborate on a Playground with a colleague
    - create a new user account via `wp-cli` 
    - share access (export/import)
- Teach a class how to build a plugin
    - use `create-plugin` utility (needs dev)
    - install Playground Plugin
    - open a page with an `Interactive Code` block on it

#### Deep Linking
Stylebook - customize theme styles and jump to the stylebook
- add custom styles to theme.json via the CLI
- or define your custom styles in the root directory, write to `theme.json` file
- Jump to the stylebook to view the custom styles

#### Maintenance, Optimization, Configuration
Start with a "completely fresh" WordPress install
- [Site Cleanup plugin](https://github.com/janw-me/default-featured-image/blob/main/.wordpress-org/blueprints/blueprint.json) (start completely fresh)

[Speculative Loading](https://make.wordpress.org/core/2024/04/09/speculative-loading-in-wordpress/) - preload assets to make the site faster

Optimization - run [Performance Lab](https://wordpress.org/plugins/performance-lab/) on a site

[Using WordPress configuration variables](https://wordpress.github.io/wordpress-playground/blueprints-api/steps/#DefineWpConfigConstsStep) - `DefineWPConfigConstsStep`
- Define custom config variables via `wp-config` to use with `WP-CLI` in blueprints - see [Slack thread](https://wordpress.slack.com/archives/C02RP4T41/p1712283654650719)


---

### Blueprint utilities
Automatically add CORS support if needed
- CORS - automatically add CORS support if needed to support the blueprint
- scan blueprint for need, if so, add CORS support

Automatic networking detection
- Network detection is turned off by default
- scan blueprint for need, if so, turn on networking

Automatically add blueprint keywords (categories)
- Helpful when submitting to Blueprints Community
- Add categories to the blueprint for searchability
- scan the blueprint for keywords common to the `plugins` directory
- add keywords to the blueprint `metadata` as `categories`
- ["hot" keywords API](https://api.wordpress.org/plugins/info/1.2/?action=hot_tags) - test it out! 