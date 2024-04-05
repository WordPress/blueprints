## How to Run Blueprints

### URL Fragment

The easiest way to start using Blueprints is to paste one into the URL "fragment" on the WordPress Playground website.

For example, to create a Playground with specific versions of WordPress and PHP you would use the following Blueprint:

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "preferredVersions": {
        "php": "7.4",
        "wp": "5.9"
    }
}
```

And then you would go to `https://playground.wordpress.net/#{"preferredVersions": {"php":"7.4", "wp":"5.9"}}` – you can also use the "Run the Blueprint" button below to do the same:

[<kbd> <br>Run the Blueprint<br> </kbd>](https://playground.wordpress.net/#{"preferredVersions":{"php":"7.4","wp":"5.9"}})

#### Base64 Encoded Blueprints

Some tools, including GitHub, might not format the Blueprint correctly when pasted into the URL. In such cases, you can encode your Blueprint in Base64 and append it to the URL. For example, here's the above Blueprint in the Base64 format:

```
eyJwcmVmZXJyZWRWZXJzaW9ucyI6IHsicGhwIjoiNy40IiwgIndwIjoiNS45In19
```

And you could run it by going to 

[https://playground.wordpress.net/#eyJwcmVmZXJyZWRWZXJzaW9ucyI6IHsicGhwIjoiNy40IiwgIndwIjoiNS45In19](https://playground.wordpress.net/#eyJwcmVmZXJyZWRWZXJzaW9ucyI6IHsicGhwIjoiNy40IiwgIndwIjoiNS45In19)

### Load Blueprint from a URL

When your Blueprint gets too wieldy, you load it via the `?blueprint-url` query parameter in the URL, like this:

[https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/blueprints/latest-gutenberg/blueprint.json](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/blueprints/latest-gutenberg/blueprint.json)

Note that the Blueprint must be publicly accessible and served with [the correct Access-Control-Allow-Origin header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin):

```
Access-Control-Allow-Origin: *
```

