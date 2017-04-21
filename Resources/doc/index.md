# Installation

## Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require querdos/qpass-db-bundle "^1.0"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Step 2: Enable the Bundle

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

            new \Querdos\QPassDbBundle\QPassDbBundle(),
        );

        // ...
    }

    // ...
}
```

# Initial configuration
For now, only one engine is supported:
  * [ORM](http://www.doctrine-project.org/projects/orm.html)

More support will come as soon as possible.  

First of all, update your database schema:
```bash
$ bin/console doctrine:schema:update --force
```

Or if you use the `DoctrineMigrationBundle`, run the following commands:
```bash
$ bin/console doctrine:migration:diff && bin/console doctrine:migration:migrate
```

You can now setup your configuration file and specify these following options:

```yaml
# app/config/config.yml

# QPassDbBundle configuration
q_pass_db:
    # Directory where databases will be stored
    db_dir: web/database_storage
```

# Usage
## Database creation
You can use the main service `qpdb.util.pass_db` to create a database:
```php
<?php
// your logic

// retrieve the container
$container = ...;

// create and retrieve the database informations
$qdatabase = $container->get('qpdb.util.pass_db')->create_database($db_name, $password);
```

## Retrieve an existing database by its name
You can use the `QDatabaseManager` service (`qpdb.manager.qdatabase`) to search an existing database:
```php
<?php
// your logic

// retrieve the container
$container  = ...;
$db_name    = 'db_name_test';

// retrieve a database
$qdatabase  = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);
```

The manager will return a null value if no database is found.

## Add a password to the existing database
To do so, you can call the `add_password` method from the main service by specifying a `QDatabase`, its associated
password, the password to add and finally a label:
```php
<?php
// your logic

// retrieve the container
$container = ...;

// retrieve a database
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);

// add a new password
$qpassword = $container->get('qpdb.util.pass_db')->add_password($qdatabase, $password, $pass_to_add, $label);
```

## Retrieve a password from the database
You will need a `pass_id` in order to retrieve a password. To do so:
```php
<?php
// your logic

// retrieve the container
$container = ...;

// retrieve a database
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);

// If no QPassword match, the manager will return a null value
$qpassword = $container->get('qpdb.manager.qpassword')->readByPassId($pass_id);

// If you want to access the password:
$password_value = $container->get('qpdb.util.pass_db')->get_password($qdatabase, $password, $qpassword);
```

## Retrieve all passwords from the database
You can use the main service in order to perform this operation:
```php
<?php
// your logic

// retrieve the container
$container = ...;

// retrieve a database
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);

// retrieve all passwords
$passwords = $container->get('qpdb.util.pass_db')->get_all_password($qdatabase, $password);

/*
 * The result will be an array with the list of passwords with their associated pass_id
 * array => [
 *      'pass_id_1' => 'password_1',
 *      'pass_id_2' => 'password_2'
 * ]
 */
```

## Remove a password from a given database
```php
<?php
// your logic

// retrieve the container
$container = ...;

// retrieve a database
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);

// retrieve a password
$qpassword = $container->get('qpdb.manager.qpassword')->readByPassId($pass_id);

// finally remove it
$container->get('qpdb.util.pass_db')->remove_password($qdatabase, $password, $qpassword);
```

## Remove a database
```php
<?php
// your logic

// retrieve the container
$container = ...;

// retrieve a database
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);

// finally remove it
$container->get('qpdb.util.pass_db')->remove_database($qdatabase, $password);
