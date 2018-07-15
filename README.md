DotEnvDumpBundle
========================

The `DotEnvDumpBundle` parses`.env` files via [DotEnv](https://github.com/symfony/dotenv) and export environment variables in `.htaccess` (SetEnv directive) or `.php` (return array) formats.

The main purpose of this bundle to use it as a part of a deploy process for a shared hosting where you can't edit environment variables. 

During development, you'll use the `.env` file to configure your environment variables. On your production server, it is recommended to configure these at the web server level. 

So if you're using Apache, you can pass these variables via SetEnv directive in `.htaccess` file. 

Or you can just cache them into `.env.php` file and replace in your front controller (index.php):
```php
(new Dotenv())->load(__DIR__.'/../.env');
```
with something like
```php
if (file_exists(__DIR__.'/../.env.php')) {
   $variables = require_once __DIR__.'/../.env.php';
   (new Dotenv())->populate($variables);
}
```

Usage
------------
```bash
bin/console dotenv:dump [--htaccess] [--php] [path-to-output-file] [path-to-env-file]
```

Invoked with no parameters will export to `.htaccess` in the `%kernel.project_dir%`.

It's a safe to invoke command few times in a row.

Example
------------

`bin/console dotenv:dump --htaccess .htaccess
` will prepend (or replace if already exists) in the `.htaccess` following content:

```ApacheConf
###> .env ###
SetEnv "APP_ENV" "dev"
SetEnv "APP_SECRET" "6d15395b9c94f12f97fa31edc9c0c6f0"
###< .env ###
```

`bin/console dotenv:dump --php .env.php
` will rewrite `.env.php` file with the following content:
```php
<?php return array (
  'APP_ENV' => 'dev',
  'APP_SECRET' => '6d15395b9c94f12f97fa31edc9c0c6f0',
);
```

Installation
------------

Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require kimbee-team/dotenv-dump
```

###Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kimbee-team/dotenv-dump
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \KimbeeTeam\DotenvDump\KimbeeTeamDotenvDumpBundle(),
        );

        // ...
    }

    // ...
}
```

License
-------
This bundle is released under the [MIT license](LICENSE)