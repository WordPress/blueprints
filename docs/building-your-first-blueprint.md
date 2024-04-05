## Building your first Blueprint

Let's build a simple Blueprint that:

1. Creates a new WordPress site
2. Sets the site title to "My first Blueprint"
3. Sets the theme to Adventurer
4. Installs the Hello Dolly plugin from the WordPress plugin directory
5. Installs a custom plugin
6. Changing site content

### Creating a new WordPress site

Let's start by creating a `blueprint.json` file with the following contents:

```json
{}
```

It may seem like nothing is happening, but this Blueprint already spins a WordPress site using the latest major version.

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#{})

#### Autocompletion

If you use an IDE like VSCode or PHPStorm, use the Blueprint JSON Schema for an autocompleted Blueprint development experience. Simply add the following line at the top of your blueprint.json file:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json"
}
```

Here's what it looks like in VSCode:

![Autocompletion visualized](./assets/schema-autocompletion.png)

### Set the site title to "My first Blueprint"

Blueprints consist of a series of steps that define how to build a WordPress site. Before we write our first step, let's declare an empty list of steps:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "steps": []
}
```

This Blueprint isn't very exciting just yet – it effectively does same as the empty Blueprint above `{}`. We're about to change that!

In WordPress, the site title is stored in the `blogname` option. Let's add our first step and set that option to "My first Blueprint":

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "steps": [
        {
            "step": "setSiteOptions",
            "options": {
                "blogname": "My first Blueprint"
            }
        }
    ]
}
```

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#https://playground.wordpress.net/#eyIkc2NoZW1hIjoiaHR0cHM6Ly9wbGF5Z3JvdW5kLndvcmRwcmVzcy5uZXQvYmx1ZXByaW50LXNjaGVtYS5qc29uIiwic3RlcHMiOlt7InN0ZXAiOiJzZXRTaXRlT3B0aW9ucyIsIm9wdGlvbnMiOnsiYmxvZ25hbWUiOiJNeSBmaXJzdCBCbHVlcHJpbnQifX1dfQ==)

The [setSiteOptions](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#SetSiteOptionsStep) step sets the site options in the WordPress database. The `options` object contains the key-value pairs to set. In this case, we're setting the `blogname` option to "My first Blueprint". You can read more about this and other steps in the [Blueprint Steps API Reference](https://wordpress.github.io/wordpress-playground/blueprints-api/steps).

#### Shorthands

Some steps can be specified using a shorthand syntax for convenience. For example, the `setSiteOptions` step can be also written as:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "siteOptions": {
        "blogname": "My first Blueprint"
    }
}
```

The shorthand syntax and the step syntax are equivalent. Every step specified with the shorthand syntax is automatically added
at the beginning of the `steps` array in an arbitrary order. Shorthands are great for brevity, steps are great if you need more
control over the order of execution.

### Setting the theme to Adventurer

Adventurer is an open-source source theme [available in the WordPress theme directory](https://wordpress.org/themes/adventurer/).
Let's install it on our site using the [installTheme](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#InstallThemeStep) step:

```json
{
    "siteOptions": {
        "blogname": "My first Blueprint"
    },
    "steps": [
        {
            "step": "installTheme",
            "themeZipFile": {
                "resource": "wordpress.org/themes",
                "slug": "adventurer"
            }
        }
    ]
}
```

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#eyIkc2NoZW1hIjoiaHR0cHM6Ly9wbGF5Z3JvdW5kLndvcmRwcmVzcy5uZXQvYmx1ZXByaW50LXNjaGVtYS5qc29uIiwib3B0aW9ucyI6eyJibG9nbmFtZSI6Ik15IGZpcnN0IEJsdWVwcmludCJ9LCJzdGVwcyI6W3sic3RlcCI6Imluc3RhbGxUaGVtZSIsInRoZW1lWmlwRmlsZSI6eyJyZXNvdXJjZSI6IndvcmRwcmVzcy5vcmcvdGhlbWVzIiwic2x1ZyI6ImFkdmVudHVyZXIifX1dfQ==)

The site now has the Adventurer theme installed and activated:

![Site with the adventurer theme](./assets/installed-adventurer-theme.png)

#### Resources

The `themeZipFile` defines a [Resource](https://wordpress.github.io/wordpress-playground/blueprints-api/resources/) – a reference to an external file required for the step. There are different types of resources, such as `url`, `wordpress.org/themes`, `wordpress.org/plugins`, `vfs`, or `literal`. In this case, we're using the `wordpress.org/themes` resource to install the theme with the "Adventurer" slug from the WordPress theme directory:

https://wordpress.org/themes/<slug>/ -> https://wordpress.org/themes/adventurer/

We'll use other resource types in the next steps, and you can learn more about resources in the [Blueprint Resources API Reference](https://wordpress.github.io/wordpress-playground/blueprints-api/resources/).

### Installing the Hello Dolly plugin

The Hello Dolly plugin is a classic WordPress plugin that displays a random lyric from the song "Hello, Dolly!" in the admin dashboard. Let's install it using the [installPlugin](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#InstallPluginStep) step:

```json
{
    "siteOptions": {
        "blogname": "My first Blueprint"
    },
    "steps": [
        {
            "step": "installTheme",
            "themeZipFile": {
                "resource": "wordpress.org/themes",
                "slug": "adventurer"
            }
        },
        {
            "step": "installPlugin",
            "pluginZipFile": {
                "resource": "wordpress.org/plugins",
                "slug": "hello-dolly"
            }
        }
    ]
}
```

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#eyJzaXRlT3B0aW9ucyI6eyJibG9nbmFtZSI6Ik15IGZpcnN0IEJsdWVwcmludCJ9LCJzdGVwcyI6W3sic3RlcCI6Imluc3RhbGxUaGVtZSIsInRoZW1lWmlwRmlsZSI6eyJyZXNvdXJjZSI6IndvcmRwcmVzcy5vcmcvdGhlbWVzIiwic2x1ZyI6ImFkdmVudHVyZXIifX0seyJzdGVwIjoiaW5zdGFsbFBsdWdpbiIsInBsdWdpblppcEZpbGUiOnsicmVzb3VyY2UiOiJ3b3JkcHJlc3Mub3JnL3BsdWdpbnMiLCJzbHVnIjoiaGVsbG8tZG9sbHkifX1dfQ==)

The Hello Dolly plugin is now installed and activated.

Similarly to the `themeZipFile`, the `pluginZipFile` defines a [Resource](https://wordpress.github.io/wordpress-playground/blueprints-api/resources/) – a reference to an external file required for the step. In this case, we're using the `wordpress.org/plugins` resource to install the plugin with the "hello-dolly" slug from the WordPress plugin directory.

### Installing a custom plugin

Let's install our very own custom WordPress plugin taht adds a custom message to the admin dashboard. Here's the plugin code:

```php
<?php
/*
Plugin Name: "Hello" on the Dashboard
Description: A custom plugin to showcase WordPress Blueprints
Version: 1.0
Author: WordPress Contributors
*/

function my_custom_plugin() {
    echo '<h1>Hello from My Custom Plugin!</h1>';
}

add_action('admin_notices', 'my_custom_plugin');
```

We will eventually use the [installPlugin](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#InstallPluginStep), but since that requires creating a ZIP file, let's start with something different just to see if our plugin works. We'll:

1. Create a `wp-content/plugins/hello-from-the-dashboard` directory using the [mkdir](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#MkdirStep) step
2. Write a `plugin.php` file there using the [writeFile](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#WriteFileStep) step, 
3. Activate our plugin using the [activatePlugin](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#ActivatePluginStep) step

Here's what that looks like in a Blueprint:

```js
{
    // ...
    "steps": [
        // ...
        {
            "step": "mkdir",
            "path": "/wordpress/wp-content/plugins/hello-from-the-dashboard"
        },
        {
            "step": "writeFile",
            "path": "/wordpress/wp-content/plugins/hello-from-the-dashboard/plugin.php",
            "data": "<?php\n/*\nPlugin Name: \"Hello\" on the Dashboard\nDescription: A custom plugin to showcase WordPress Blueprints\nVersion: 1.0\nAuthor: WordPress Contributors\n*/\n\nfunction my_custom_plugin() {\n    echo '<h1>Hello from My Custom Plugin!</h1>';\n}\n\nadd_action('admin_notices', 'my_custom_plugin');"
        },
        {
            "step": "activatePlugin",
            "pluginPath": "hello-from-the-dashboard/plugin.php"
        }
    ]
}
```

There's just one more thing – we will log the user in as an admin to make testing easier:

```js
{
    "login": true,
    "steps": {
        // ...
    }
}
```

Note that `"login": true` is a shorthand for the [login](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#LoginStep) step

Here's what the full Blueprint looks like:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "login": true,
    "siteOptions": {
        "blogname": "My first Blueprint"
    },
    "steps": [
        {
            "step": "installTheme",
            "themeZipFile": {
                "resource": "wordpress.org/themes",
                "slug": "adventurer"
            }
        },
        {
            "step": "installPlugin",
            "pluginZipFile": {
                "resource": "wordpress.org/plugins",
                "slug": "hello-dolly"
            }
        },
        {
            "step": "mkdir",
            "path": "/wordpress/wp-content/plugins/hello-from-the-dashboard"
        },
        {
            "step": "writeFile",
            "path": "/wordpress/wp-content/plugins/hello-from-the-dashboard/plugin.php",
            "data": "<?php\n/*\nPlugin Name: \"Hello\" on the Dashboard\nDescription: A custom plugin to showcase WordPress Blueprints\nVersion: 1.0\nAuthor: WordPress Contributors\n*/\n\nfunction my_custom_plugin() {\n    echo '<h1>Hello from My Custom Plugin!</h1>';\n}\n\nadd_action('admin_notices', 'my_custom_plugin');"
        },
        {
            "step": "activatePlugin",
            "pluginPath": "hello-from-the-dashboard/plugin.php"
        }
    ]
}
```

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#eyJsb2dpbiI6dHJ1ZSwic2l0ZU9wdGlvbnMiOnsiYmxvZ25hbWUiOiJNeSBmaXJzdCBCbHVlcHJpbnQifSwic3RlcHMiOlt7InN0ZXAiOiJpbnN0YWxsVGhlbWUiLCJ0aGVtZVppcEZpbGUiOnsicmVzb3VyY2UiOiJ3b3JkcHJlc3Mub3JnL3RoZW1lcyIsInNsdWciOiJhZHZlbnR1cmVyIn19LHsic3RlcCI6Imluc3RhbGxQbHVnaW4iLCJwbHVnaW5aaXBGaWxlIjp7InJlc291cmNlIjoid29yZHByZXNzLm9yZy9wbHVnaW5zIiwic2x1ZyI6ImhlbGxvLWRvbGx5In19LHsic3RlcCI6Im1rZGlyIiwicGF0aCI6Ii93b3JkcHJlc3Mvd3AtY29udGVudC9wbHVnaW5zL2hlbGxvLW9uLXRoZS1kYXNoYm9hcmQifSx7InN0ZXAiOiJ3cml0ZUZpbGUiLCJwYXRoIjoiL3dvcmRwcmVzcy93cC1jb250ZW50L3BsdWdpbnMvaGVsbG8tb24tdGhlLWRhc2hib2FyZC9wbHVnaW4ucGhwIiwiZGF0YSI6Ijw/cGhwXG4vKlxuUGx1Z2luIE5hbWU6IFwiSGVsbG9cIiBvbiB0aGUgRGFzaGJvYXJkXG5EZXNjcmlwdGlvbjogQSBjdXN0b20gcGx1Z2luIHRvIHNob3djYXNlIFdvcmRQcmVzcyBCbHVlcHJpbnRzXG5WZXJzaW9uOiAxLjBcbkF1dGhvcjogV29yZFByZXNzIENvbnRyaWJ1dG9yc1xuKi9cblxuZnVuY3Rpb24gbXlfY3VzdG9tX3BsdWdpbigpIHtcbiAgICBlY2hvICc8aDE+SGVsbG8gZnJvbSBNeSBDdXN0b20gUGx1Z2luITwvaDE+Jztcbn1cblxuYWRkX2FjdGlvbignYWRtaW5fbm90aWNlcycsICdteV9jdXN0b21fcGx1Z2luJyk7In0seyJzdGVwIjoiYWN0aXZhdGVQbHVnaW4iLCJwbHVnaW5QYXRoIjoiaGVsbG8tb24tdGhlLWRhc2hib2FyZC9wbHVnaW4ucGhwIn1dfQ==)

The plugin is now installed and activated:

![Site with the custom plugin](./assets/installed-custom-plugin.png)

Encoding PHP files as JSON can be useful for quick testing, but it's quite inconvenient and difficult to read. Instead, let's create a ZIP file with our plugin code and use the [installPlugin](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#InstallPluginStep) step to install it:


```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "login": true,
    "siteOptions": {
        "blogname": "My first Blueprint"
    },
    "steps": [
        {
            "step": "installTheme",
            "themeZipFile": {
                "resource": "wordpress.org/themes",
                "slug": "adventurer"
            }
        },
        {
            "step": "installPlugin",
            "pluginZipFile": {
                "resource": "wordpress.org/plugins",
                "slug": "hello-dolly"
            }
        },
        {
            "step": "installPlugin",
            "pluginZipFile": {
                "resource": "url",
                "url": "https://raw.githubusercontent.com/adamziel/blueprints/trunk/docs/assets/hello-from-the-dashboard.zip"
            }
        }
    ]
}
```

We can shorten that Blueprint even more using the shorthand syntax:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "login": true,
    "siteOptions": {
        "blogname": "My first Blueprint"
    },
    "plugins": [
        "hello-dolly",
        "https://raw.githubusercontent.com/adamziel/blueprints/trunk/docs/assets/hello-from-the-dashboard.zip"
    ],
    "steps": [
        {
            "step": "installTheme",
            "themeZipFile": {
                "resource": "wordpress.org/themes",
                "slug": "adventurer"
            }
        }
    ]
}
```

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#eyIkc2NoZW1hIjoiaHR0cHM6Ly9wbGF5Z3JvdW5kLndvcmRwcmVzcy5uZXQvYmx1ZXByaW50LXNjaGVtYS5qc29uIiwibG9naW4iOnRydWUsInNpdGVPcHRpb25zIjp7ImJsb2duYW1lIjoiTXkgZmlyc3QgQmx1ZXByaW50In0sInBsdWdpbnMiOlsiaGVsbG8tZG9sbHkiLCJodHRwczovL3Jhdy5naXRodWJ1c2VyY29udGVudC5jb20vYWRhbXppZWwvYmx1ZXByaW50cy90cnVuay9kb2NzL2hlbGxvLW9uLXRoZS1kYXNoYm9hcmQuemlwIl0sInN0ZXBzIjpbeyJzdGVwIjoiaW5zdGFsbFRoZW1lIiwidGhlbWVaaXBGaWxlIjp7InJlc291cmNlIjoid29yZHByZXNzLm9yZy90aGVtZXMiLCJzbHVnIjoiYWR2ZW50dXJlciJ9fV19)

### Changing site content

Finally, we'll delete the default content of the site and import a new one from a WordPress export file (WXR).

#### Deleting the old content

There is no Blueprint step to delete the default content. We'll have to do it by running a snippet of custom PHP code:

```php
<?php
require '/wordpress/wp-load.php';

// Delete all posts and pages
$posts = get_posts(array(
    'numberposts' => -1,
    'post_type' => array('post', 'page'),
    'post_status' => 'any'
));

foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}
```

To run that code during site setup, we'll use the [runPHP](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#RunPHPStep) step:


```js
{
    // ...
    "steps": [
        // ...
        {
            "step": "runPHP",
            "code": "<?php\nrequire '/wordpress/wp-load.php';\n\n$posts = get_posts(array(\n    'numberposts' => -1,\n    'post_type' => array('post', 'page'),\n    'post_status' => 'any'\n));\n\nforeach ($posts as $post) {\n    wp_delete_post($post->ID, true);\n}"
        }
    ]
}
```

#### Importing new content

We'll use the [importWxr](https://wordpress.github.io/wordpress-playground/blueprints-api/steps#ImportWXRStep) step to import a WordPress export (WXR) file used for testing WordPress themes. It lives in the [WordPress/theme-test-data](https://github.com/WordPress/theme-test-data) repository and can be accessed directly via its `raw.githubusercontent.com` address: [https://raw.githubusercontent.com/WordPress/theme-test-data/master/themeunittestdata.wordpress.xml](https://raw.githubusercontent.com/WordPress/theme-test-data/master/themeunittestdata.wordpress.xml).

Here's what the final Blueprint looks like:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "login": true,
    "siteOptions": {
        "blogname": "My first Blueprint"
    },
    "plugins": [
        "hello-dolly",
        "https://raw.githubusercontent.com/adamziel/blueprints/trunk/docs/assets/hello-from-the-dashboard.zip"
    ],
    "steps": [
        {
            "step": "installTheme",
            "themeZipFile": {
                "resource": "wordpress.org/themes",
                "slug": "adventurer"
            }
        },
        {
            "step": "runPHP",
            "code": "<?php\nrequire '/wordpress/wp-load.php';\n\n$posts = get_posts(array(\n    'numberposts' => -1,\n    'post_type' => array('post', 'page'),\n    'post_status' => 'any'\n));\n\nforeach ($posts as $post) {\n    wp_delete_post($post->ID, true);\n}"
        },
        {
            "step": "importWxr",
            "file": {
                "resource": "url",
                "url": "https://raw.githubusercontent.com/WordPress/theme-test-data/master/themeunittestdata.wordpress.xml"
            }
        }
    ]
}
```

[<kbd> <br>Run Blueprint<br> </kbd>](https://playground.wordpress.net/#eyIkc2NoZW1hIjoiaHR0cHM6Ly9wbGF5Z3JvdW5kLndvcmRwcmVzcy5uZXQvYmx1ZXByaW50LXNjaGVtYS5qc29uIiwibG9naW4iOnRydWUsInNpdGVPcHRpb25zIjp7ImJsb2duYW1lIjoiTXkgZmlyc3QgQmx1ZXByaW50In0sInBsdWdpbnMiOlsiaGVsbG8tZG9sbHkiLCJodHRwczovL3Jhdy5naXRodWJ1c2VyY29udGVudC5jb20vYWRhbXppZWwvYmx1ZXByaW50cy90cnVuay9kb2NzL2Fzc2V0cy9oZWxsby1mcm9tLXRoZS1kYXNoYm9hcmQuemlwIl0sInN0ZXBzIjpbeyJzdGVwIjoiaW5zdGFsbFRoZW1lIiwidGhlbWVaaXBGaWxlIjp7InJlc291cmNlIjoid29yZHByZXNzLm9yZy90aGVtZXMiLCJzbHVnIjoiYWR2ZW50dXJlciJ9fSx7InN0ZXAiOiJydW5QSFAiLCJjb2RlIjoiPD9waHBcbnJlcXVpcmUgJy93b3JkcHJlc3Mvd3AtbG9hZC5waHAnO1xuXG4kcG9zdHMgPSBnZXRfcG9zdHMoYXJyYXkoXG4gICAgJ251bWJlcnBvc3RzJyA9PiAtMSxcbiAgICAncG9zdF90eXBlJyA9PiBhcnJheSgncG9zdCcsICdwYWdlJyksXG4gICAgJ3Bvc3Rfc3RhdHVzJyA9PiAnYW55J1xuKSk7XG5cbmZvcmVhY2ggKCRwb3N0cyBhcyAkcG9zdCkge1xuICAgIHdwX2RlbGV0ZV9wb3N0KCRwb3N0LT5JRCwgdHJ1ZSk7XG59In0seyJzdGVwIjoiaW1wb3J0V3hyIiwiZmlsZSI6eyJyZXNvdXJjZSI6InVybCIsInVybCI6Imh0dHBzOi8vcmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbS9Xb3JkUHJlc3MvdGhlbWUtdGVzdC1kYXRhL21hc3Rlci90aGVtZXVuaXR0ZXN0ZGF0YS53b3JkcHJlc3MueG1sIn19XX0=)

And... that's it!

### Next steps


