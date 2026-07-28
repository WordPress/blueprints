## WooCommerce Store (Hello Elementor)

A WooCommerce store set up with a **common, real-world configuration** — a
lifelike environment for testing plugins, themes, and WooCommerce itself against
something closer to a production store than a bare install.

It pairs with [WooCommerce Store (Astra)](../woocommerce-test-store-astra/), which
uses a different theme and locale. Between the two they cover the most common
theme families and two locale profiles (comma-decimal EUR and period-decimal
USD).

### What it sets up

- **Theme:** Hello Elementor + Elementor (the most common page-builder stack on
  WooCommerce stores).
- **WooCommerce:** latest stable, plus a set of the most widely-used extensions:
  Yoast SEO, Contact Form 7, WP Mail SMTP, Site Kit by Google, Google Listings &
  Ads, Jetpack, WooCommerce PayPal Payments, WooCommerce Stripe Gateway,
  WooPayments, Code Snippets, Loco Translate, LiteSpeed Cache, WPForms Lite,
  Classic Editor.
- **Locale:** EUR currency with German formatting (`1.234,56 €`), store based in
  Germany — exercises comma-decimal formatting and a non-ASCII currency symbol.
- **Content:** three sample products (a simple product, a product on sale, and a
  virtual/downloadable product), plus the standard WooCommerce pages.
- Guest checkout enabled, pretty permalinks, onboarding wizard skipped.

### Notes

- **All extensions are active by default.** On a resource-constrained host the
  admin can feel slow with this many plugins active; deactivate a few for a
  snappier experience, e.g. `wp plugin deactivate litespeed-cache wpforms-lite
  classic-editor`, or from **Plugins** in wp-admin. Nothing depends on all of
  them being active.
- **Landing page is the shop** (`/shop/`), a server-rendered page that loads
  quickly. The WooCommerce Analytics dashboard is heavier and may take a moment.
- Plugins are installed first and activated at the end, so the sample content is
  created on a clean WooCommerce-only stack before the other extensions load.

### Changing the WooCommerce version

WooCommerce installs from the wordpress.org plugin directory, so it's always the
**latest stable** release. To target something else, replace the WooCommerce
step's `pluginData` with a `url` resource:

- **Pin a version:** `https://downloads.wordpress.org/plugin/woocommerce.<x.y.z>.zip`
- **Nightly / trunk:** `https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip`
