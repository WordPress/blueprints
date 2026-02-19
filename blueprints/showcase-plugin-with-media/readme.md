# Showcase custom plugin from own server with media files and custom theme

➡ Custom theme is customized child theme of twentytwentyfour

### Installing theme

Custom child theme is zipped and installed as [URLReference](https://wordpress.github.io/wordpress-playground/blueprints/steps/resources#urlreference) with
[InstallTheme](https://wordpress.github.io/wordpress-playground/blueprints/steps#InstallThemeStep) step.

### Installing plugin

Plugin is installed from _WordPress.org_ repository. If your plugin is not (yet) there, you could self-host it and install it as [URLReference](https://wordpress.github.io/wordpress-playground/blueprints/steps/resources#urlreference) resource.

### Importing media files into file system

Here, the [unzip](https://wordpress.github.io/wordpress-playground/blueprints/steps#UnzipStep) step is used:
zip all the folders in upload folder and in unzip step unpack them to respective path `/site-slug/wp-content/uploads`, where slug
is the custom slug if given, e.g. for `site-slug=mysite` in URL query, path would be `/site-mysite/wp-content/uploads`. If custom slug is not given, replace site-slug
with `wordpress`. In this example, all media content from 2024 year is packed as 2024 folder in _.zip_ file, just as it appears in WordPress content directory.

### Setting defaults

`setSiteOptions` step is used to set home page and other default WP options, and `landingPage` json property is used to start on front page instead of admin.
In your example, you would replace `page_on_front` option with page ID which is front page (if you have front page and not blog list as homepage).

### Transfering content

> For transferring content, WXR file is used. The same could be probably achieved with sql dump and runsql step.

After exporting WXR from standard WordPress installation, URL-s in file have to be adjusted. For example, do search-replace to replace
`https://myserver.com/` into `/`, which is the url root in playground.

If sql export-import is used, I propose similarly to use wp-cli search-replace command with export option to do same url string replacement.
