Buuum - FtpGitSync package for your app
=======================================

[![Packagist](https://poser.pugx.org/buuum/ftpgitsync/v/stable)](https://packagist.org/packages/buuum/ftpgitsync)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg?maxAge=2592000)](#license)

## Simple and extremely flexible PHP event class

## Getting started

You need PHP >= 5.5 to use Buuum.

- [Install Buuum FtpGitSync](#install)
- [Initialize Config](#initialize-config)
- [Start Project](#start-project)
- [Sync poejct](#sync)
- [Get diff](#get-diff)
- [Update project](#update)
- [Update Vendor](#update-vendor)

## Install

### System Requirements

You need PHP >= 5.5.0 to use Buuum\FtpGitSync but the latest stable version of PHP is recommended.

### Composer

Buuum is available on Packagist and can be installed using Composer:

```
composer require buuum/ftpgitsync
```

### Manually

You may use your own autoloader as long as it follows PSR-0 or PSR-4 standards. Just put src directory contents in your vendor directory.

## Initialize Config

```php
php vendor/bin/fgsync init
```
file generated fgsync.yml
```yaml
paths:
  temp: temp
  public_folder: httpdocs
ignore:
  files: [
    ".gitignore",
    "README.md"
    ]
  folders: [
    "temp",
    "log"
    ]
environments:
  local.dev:
    host: localhost
  dev.local.com:
    url: https://dev.local.com
    host: host.com
    connection: ssl
    port: 21
    timeout: 90
    passive: false
    user:
    password:
    remotePath: /
    public_folder: public_html
  local.com:
    url: https://local.com
    host: host.com
    connection: ftp
    port: 21
    timeout: 90
    passive: false
    user:
    password:
    remotePath: /
    public_folder: httpdocs
```

## Start Project

```php
php vendor/bin/fgsync start
```

## Sync

```php
php vendor/bin/fgsync sync
```

## Get diff

```php
php vendor/bin/fgsync diff
```

## Update

```php
php vendor/bin/fgsync update
```


## Update Vendor

```php
php vendor/bin/fgsync vendor
```

## LICENSE

The MIT License (MIT)

Copyright (c) 2017 alfonsmartinez

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.