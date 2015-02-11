Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of the bundle:

```bash
$ composer require seferov/deployer-bundle "~1"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your project:

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
            new Seferov\DeployerBundle\SeferovDeployerBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Configure
-----------------

Configuration example:

``` yaml
seferov_deployer:
    servers:
        production:
            connection:
                host: %production_host% # ip address or domain
                username: root
            git: %git_endpoint%
        staging:
            connection:
                host: %staging_host% # ip address or domain
                username: root
            git: %git_endpoint%
            commands:
                before_install:
                    - "apt-get install php5-curl"
```

Usage
=====

First install deployer on server by running:

```bash
$ app/console deployer:install production
```

Now you can deploy your app:

```bash
$ app/console deployer:push production
```
