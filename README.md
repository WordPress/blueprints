# WordPress Blueprints Community Gallery

> [!IMPORTANT]  
> Skip to the Blueprints Gallery to explore a variety of WordPress sites. Keep reading to learn more about Blueprints and how to contribute your own:
> 
> [<kbd> <br> Browse the Blueprints Gallery <br> </kbd>](./GALLERY.md)

## What are Blueprints?

Blueprints are WordPress setup scripts that you can preview live in [WordPress Playground](https://w.org/playground). Blueprints contain all the installation instructions needed to setup WordPress, including plugins, themes, site options, starter content to import, and more.

The basic example below will load a Playground instance with the Hello Dolly plugin preinstalled and that opens in wp-admin plugins screen after it automattically logs in. 

```json
{
    "plugins": ["hello-dolly", "gutenberg"],
    "login": true,
    "landingPage": "/wp-admin/plugins.php"
}
```
[<kbd> <br> Preview in WordPress Playground <br> </kbd>](https://playground.wordpress.net/#%7B%22plugins%22:%5B%22hello-dolly%22,%22gutenberg%22%5D,%20%22login%22:%20true,%20%22landingPage%22:%20%22/wp-admin/plugins.php%22%20%7D)

Check out [Blueprints 101](./docs/index.md) to get started creating blueprints. 


## Why use Blueprints?

Blueprints can help you

- **Explore different setups:** try out different themes and plugins without the risk of breaking your site. It's a safe environment to see what works best for your needs.
- **Save time**: instead of manually setting up your site, choosing themes, and installing plugins one by one, Blueprints do all of the work for you.
- **Learn WordPress:** Blueprints are a fantastic way to play with a variety of WordPress configurations.

## Ready to jump in?

This community space allows you to

* [Browse the Blueprints Gallery](./GALLERY.md) and explore diverse WordPress sites and different configurations. 
* [Submit your own Blueprint](./CONTRIBUTING.md) and share your WordPress setup with the community.

## How to contribute your Blueprint

We encourage you to contribute your Blueprints to this repository! We accepet new submissions as Pull Requests. Read the [Contributing Guidelines](./CONTRIBUTING.md) for more details. 

## Need help?

If you have questions or comments, [open a new issue](https://github.com/wordpress/blueprints/issues) in this repository.

## Let's build the Blueprint Community together!

This is a minimal version 1 (v1) to get the community space up and running. We plan to build upon this foundation, expand, and improve it—with your feedback. So, explore, create, and share your Blueprints!
