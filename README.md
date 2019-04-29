# wp-siteurl

Fix Site Url Conflicts

## Install

### Install manually

Download and copy `siteurl.php` to `./wp-content/mu-plugins`.

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
        "version": "0.0.2",
        "type": "wordpress-muplugin",
        "source": {
          "type": "git",
          "url": "https://github.com/benignware-labs/wp-siteurl.git",
          "reference": "0.0.2"
        }
      }
    }
  ],
  "require": {
    "wordpress": "4.7.1",
    "wemakecustom/wp-mu-loader": "*",
    "wp-siteurl": "0.0.2"
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

## Development

Download [Docker CE](https://www.docker.com/get-docker) for your OS.

Point terminal to your project root and start up the container.

```cli
docker-compose up -d
```

Open your browser at [http://localhost:8000](http://localhost:8000).

Go through Wordpress installation and activate Swiper Shortcode wordpress plugin.

### Useful docker commands

#### Startup services

```cli
docker-compose up -d
```
You may omit the `-d`-flag for verbose output.

#### Shutdown services

In order to shutdown services, issue the following command

```cli
docker-compose down
```

#### List containers

```cli
docker-compose ps
```

#### Remove containers

```cli
docker-compose rm
```

#### Open bash

Open bash at wordpress directory

```cli
docker-compose exec wordpress bash
```

#### Update composer dependencies

If it's complaining about the composer.lock file, you probably need to update the dependencies.

```cli
docker-compose run composer update
```

###### List all globally running docker containers

```cli
docker ps
```

###### Globally stop all running docker containers

If you're working with multiple docker projects running on the same ports, you may want to stop all services globally.

```cli
docker stop $(docker ps -a -q)
```

###### Globally remove all containers

```cli
docker rm $(docker ps -a -q)
```

##### Remove all docker related stuff

```cli
docker system prune
```
