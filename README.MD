<p align="center"><img width="447" src="images/logo.png"></p>

## Introduction

Valet Pro Max is a development environment for macOS. No Vagrant, no Docker, no `/etc/hosts` file. This project uses 
[laravel/valet](https://github.com/laravel/valet) as a dependency.


## Installation

> :warning: Valet Pro Max requires macOS and [Homebrew](https://brew.sh/). Before installation, you should make sure that no 
> other programs such as Apache or Nginx are binding to your local machine's port 80.

> :warning: Valet Pro Max requires [Composer](https://getcomposer.org/) to be installed. It's NOT recommended to install Composer via Homebrew. 

To get started, you first need to ensure that Homebrew is up-to-date using the update command:
```shell
brew update
```

Next, you should use Homebrew to install PHP 8.1 (using `shivammathur/php`):
```shell
brew tap shivammathur/php
brew install shivammathur/php/php@8.1
brew link php@8.1 --force
brew services restart php@8.1
```

> :warning: If you're switching from Valet+ 2.x use the extra following steps.
> - Uninstall Valet+ with `valet uninstall` first.
> - Remove Valet+ package with `composer global remove weprovide/valet-plus`.

Install Valet Pro Max 1.x-dev with composer:
```shell
composer global require nntoan/valet-pro-max:1.x-dev
```

Finally, you may execute the Valet Pro Max `install` command. This will configure and install Valet Pro Max, Nginx, DnsMasq and other
services. In addition, use `--help` to see all installation options. 
```shell
valet-pro install
```

The daemons Valet Pro Max depends on will be configured to launch when your system starts. Once Valet Pro MAx is installed, try pinging
any `*.test` domain on your terminal using a command such as `ping foobar.test`. If Valet Pro Max is installed correctly you 
should see this domain responding on `127.0.0.1`.

## Valet Pro Max features

Here are a few key differences compared to the original Valet:

- Nginx config optimization
- PHP extensions (~~mcrypt~~, ~~intl~~, ~~opcache~~, yaml, apcu, ds, imagick)
- MySQL (mysql@5.7, mysql@8.0, mysql@8.1, mariadb)
  - DB commands (list, create, drop, reset, (re)import, export, ~~open~~)
- Mailhog (on/off mode)
- Varnish (on/off mode)
- Redis (on/off mode)
- Rabbitmq (on/off mode)
- Xdebug (on/off mode)
- Memcache (on/off mode)
- Elasticsearch v6, v7, v8 (on/off mode) using Docker 
- Opensearch (on/off mode)
- Ioncube (on/off mode)
- Rewrite/unrewrite public domain to local environment
- ~~DevTools~~
- Binaries (magerun, magerun2, drush, wp-cli, shopware-cli, shopify-cli)

### Changes vs Valet+ 2

- Use command `valet-pro` instead of `valet`.
- Rename `.env.valet` to `.valet-env.php`.
- Use command `valet-pro elasticsearch|es use <version>` instead of `valet use elasticsearch|es <version>`.
- Use `127.0.0.1` as Redis host instead of `/tmp/redis.sock`.
- Choose which binaries to install (default all) and self-update on `valet-pro install` command.
- Adds dependency on Docker for Elasticsearch, see https://docs.docker.com/desktop/install/mac-install/
- Adds support for OpenSearch, you can install use all base commands of ES such as: `valet-pro opensearch install`, `valet-pro opensearch|os use 1`

## Credits

This project is a fork of [weprovide/valet-plus](https://github.com/weprovide/valet-plus). Thanks to all of the contributors, especially the original authors:

- Tim Neutkens ([@timneutkens](https://github.com/timneutkens))
- Sam Granger ([@samgranger](https://github.com/samgranger))
- Lou van der Laarse ([@Neodork](https://github.com/Neodork))
- Sander Pott (@Sander Valet+ slack channel)
- Mischa Braam ([@mischabraam](https://github.com/mischabraam))