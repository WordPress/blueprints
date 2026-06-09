# Symfony Package Radar Blueprint

This directory contains a PHP-only Symfony demo for WordPress Playground.

- `blueprint.json` loads the demo without downloading WordPress (`preferredVersions.wp: false`).
- `symfony-package-radar.zip` is the bundled app used by the Blueprint.
- `source/wordpress/` contains the reviewable source used to build the ZIP.

To rebuild the ZIP:

```bash
rm -rf .context/symfony-package-radar-build
mkdir -p .context/symfony-package-radar-build
cp -R blueprints/symfony-package-radar/source/wordpress/. \
  .context/symfony-package-radar-build/
(
  cd .context/symfony-package-radar-build/symfony-package-radar
  composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
)
rm -f blueprints/symfony-package-radar/symfony-package-radar.zip
(
  cd .context/symfony-package-radar-build
  zip -X -qr ../../blueprints/symfony-package-radar/symfony-package-radar.zip \
    index.php symfony-package-radar \
    -x 'symfony-package-radar/var/cache/*' 'symfony-package-radar/var/log/*'
)
```
