# wp-siteurl

> Fix Site URL Conflicts 

## Install

### Install manually

Download and copy `siteurl.php` to `./wp-content/muplugins`.

### Install via composer

See an example how to require siteurl as muplugin in `composer.json`:

```json
{
  "name": "wp-siteurl-composer-example",
  "description": "Siteurl Composer Example",
  "version": "0.0.1",
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "vendor-dir": "vendor/lib"
  },
  "repositories": [
    {
      "type": "composer",
      "url": "https://wpackagist.org"
    },
    {
      "type": "package",
      "package": {
        "name": "wordpress",
        "type": "webroot",
        "version": "4.7.1",
        "dist": {
          "type": "zip",
          "url": "https://github.com/WordPress/WordPress/archive/4.7.1.zip"
        },
        "require": {
          "fancyguy/webroot-installer": "1.1.0"
        }
      }
    },
    {
      "type": "package",
      "package": {
        "name": "wp-siteurl",
        "version": "dev-master",
        "type": "wordpress-muplugin",
        "source": {
          "type": "git",
          "url": "https://github.com/benignware-labs/wp-siteurl.git",
          "reference": "master"
        }
      }
    }
  ],
  "require": {
    "wordpress": "4.7.1",
    "wemakecustom/wp-mu-loader": "*",
    "wp-siteurl": "master-dev"
  },
  "scripts": {
    "post-autoload-dump": [
      "php -r \"copy('wp-content/mu-plugins/mu-loader/mu-require.php', 'wp-content/mu-plugins/mu-require.php');\""
    ]
  },
  "extra": {
    "webroot-dir": "wp-core",
    "webroot-package": "wordpress",
    "installer-paths": {
      "wp-content/plugins/{$name}/": [
        "type:wordpress-plugin"
      ],
      "wp-content/mu-plugins/{$name}/": [
        "type:wordpress-muplugin"
      ],
      "wp-content/themes/{$name}/": [
        "type:wordpress-theme"
      ]
    }
  }
}
```
