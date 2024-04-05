## More Resources

When building Blueprints, you might run into issues. Here are some tips to help you debug them:

### Review Common gotchas

* Require wp-load: to run a WordPress PHP function using the runPHP step, you’d need to require [wp-load.php](https://github.com/WordPress/WordPress/blob/master/wp-load.php). So, the value of the code key should start with "<?php require_once('wordpress/wp-load.php'); REST_OF_YOUR_CODE".
* Load kitchen-sink: to edit assets in the Media Library or use the new Font Library, you’d need to [load PHP extensions](https://wordpress.github.io/wordpress-playground/blueprints-api/data-format/#php-extensions) (specifically openssl): "phpExtensionBundles": ["kitchen-sink"].
* Enable networking: to access wp.org assets (themes, plugins, blocks, or patterns), or load a stylesheet using [add_editor_style()](https://developer.wordpress.org/reference/functions/add_editor_style/) (say, when [creating a custom block style](https://developer.wordpress.org/news/2023/02/creating-custom-block-styles-in-wordpress-themes)), you’d need to enable the networking option: "features": {"networking": true}.

### Check for Browser errors

If your Blueprint isn’t running as expected, open the browser developer tools to see if there are any errors. Here's how to do it in popular web browsers:

#### Google Chrome

To open the developer tools in Chrome:

1. Press `Ctrl + Shift + I` on Windows/Linux or `Cmd + Option + I` on macOS.
2. Alternatively, right-click on the page and select "Inspect," or go to the menu (three dots in the upper right corner), select "More tools," and then "Developer tools."

For more detailed information, you can visit the [Chrome DevTools documentation](https://developer.chrome.com/docs/devtools/).

#### Mozilla Firefox

To access the developer tools in Firefox:

1. Press `Ctrl + Shift + I` on Windows/Linux or `Cmd + Option + I` on macOS.
2. You can also click on the menu (three horizontal lines in the upper right corner), select "Web Developer," and then "Toggle Tools."

For additional guidance, check out the [Firefox Developer Tools documentation](https://developer.mozilla.org/en-US/docs/Tools).

#### Safari

To use the developer tools in Safari:

1. First, enable the Develop menu by going to Safari > Preferences > Advanced and checking "Show Develop menu in menu bar."
2. Then press `Cmd + Option + I` to open the Web Inspector.

You can learn more by visiting the [Safari Developer Tools Guide](https://developer.apple.com/documentation/web_inspector).

#### Microsoft Edge

To open the developer tools in Edge:

1. Press `F12` or `Ctrl + Shift + I` on Windows.
2. Alternatively, click on the menu (three dots in the upper right corner), select "More tools," and then "Developer tools."

For further information, refer to the [Microsoft Edge DevTools documentation](https://learn.microsoft.com/en-us/microsoft-edge/devtools-guide-chromium/).

By using these developer tools, you can inspect network requests, view console logs, debug JavaScript, and examine the DOM and CSS styles applied to your webpage. This can be invaluable in diagnosing and fixing issues with your Blueprint.

### Ask for help

The community is here to help you! If you have questions or need assistance, start a new issue in this repository and we’ll do our best to help you out. Remember to include:

* The Blueprint you’re trying to run.
* The error message you’re seeing, if any.
* The full output from the browser developer tools.
* Any other relevant information that might help us understand the issue.

