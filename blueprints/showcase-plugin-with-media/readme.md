# Showcase custom plugin from own server with media files and custom theme

➡ Custom theme is customized child theme of twentytwentyfour

### Transfering content

> For transferring content, WXR file is used. The same could be probably achieved with sql dump and runsql step.

After exporting WXR from standard WordPress installation, URL-s in file have to be adjusted. For example, do search-replace to replace
`https://myserver.com/` into `/`, which is the url root in playground.

If sql export-import is used, I propose similarly to use wp-cli search-replace command with export option to do same url string replacement.

### Importing media files into file system

Here, the unzip step is used: zip all the folders in upload folder and in unzip step unpack them to respective path `/site-slug/wp-content/uploads`, where slug
is the custom slug if given, e.g. for `site-slug=mysite` in URL query, path would be `/site-mysite/wp-content/uploads`. If custom slug is not given, replace site-slug
with `wordpress`
