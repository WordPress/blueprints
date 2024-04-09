# How to load and run Blueprints

## URL fragment

A first step to learn using Blueprints is to paste one into the URL "fragment" of a WordPress Playground website. Just add a `#` after the `.net/`.

Let's say you want to create a Playground with specific versions of WordPress and PHP using the following Blueprint:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "preferredVersions": {
        "php": "7.4",
        "wp": "5.9"
    }
}
```

To run it, go to `https://playground.wordpress.net/#{"preferredVersions": {"php":"7.4", "wp":"5.9"}}`. You can also use the button below:

[<kbd> <br>Run the Blueprint<br> </kbd>](https://playground.wordpress.net/#{"preferredVersions":{"php":"7.4","wp":"5.9"}})

Use this method to run the example code in the next chapter, [**Build your first Blueprint**](./build-your-first-blueprint.md).

### Base64 encoded Blueprints

Some tools, including GitHub, might not format the Blueprint correctly when pasted into the URL. In such cases, [encode your Blueprint in Base64](https://www.base64encode.org) and append it to the URL. For example, that's the above Blueprint in Base64 format: `eyJwcmVmZXJyZWRWZXJzaW9ucyI6IHsicGhwIjoiNy40IiwgIndwIjoiNS45In19`.

To run it, go to [https://playground.wordpress.net/#eyJwcmVmZXJyZWRWZXJzaW9ucyI6IHsicGhwIjoiNy40IiwgIndwIjoiNS45In19](https://playground.wordpress.net/#eyJwcmVmZXJyZWRWZXJzaW9ucyI6IHsicGhwIjoiNy40IiwgIndwIjoiNS45In19)

### Load Blueprint from a URL

When your Blueprint gets too wieldy, you can load it via the `?blueprint-url` query parameter in the URL, like this:

[https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/blueprints/latest-gutenberg/blueprint.json](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/blueprints/latest-gutenberg/blueprint.json)

Note that the Blueprint must be publicly accessible and served with [the correct `Access-Control-Allow-Origin` header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin):

```
Access-Control-Allow-Origin: *
```

***

**Table of contents**
1. [What are Blueprints, and what can you do with them?](./what-are-blueprints-what-you-can-do-with-them.md)
2. How to load and run Blueprints?
3. [Build your first Blueprint](./build-your-first-blueprint.md)
4. [Troubleshoot and debug Blueprints](./troubleshoot-debug-blueprints.md)
