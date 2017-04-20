# QPassDbBundle
[![Build Status](https://travis-ci.org/Querdos/QPassDbBundle.svg?branch=master)](https://travis-ci.org/Querdos/QPassDbBundle)  

A symfony bundle that allow you to create local encrypted database for passwords storage

## Behind the scene
The goal of this bundle is to provide you a simple way to create and manage passwords storage for your users (or other things...)

The logic in it is simple:
  * Creation of a database with a given name and password. The database is an SQLite3 one and the 
  main file is encrypted using GnuPG (symetric encryption)
  * There are two main linked entities, `QDatabase` and `QPassword`. After the database creation,
  an instance of a `QDatabase` is created. When adding a password, a `QPassword` instance is created, with a label and a pass_id.
  * Now, if you want to access the database, the process is simple:
    * With a given password, the database is unlocked
    * Either you want to retrieve all saved passwords
    * Or you can retrieve a password with the given `pass_id`
    * The database is locked again and saved to the `db_dir` directory
  * When adding a new password:
    * The database is unlocked (the decrypted file is placed in the `/tmp` directory of your system)
    * The password is added to the plain database
    * The original file will be overwritten by the updated database
  * For removal and edition, the process is the same

## Installation

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require querdos/qpass-db-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

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

## Initial configuration
For now, only one engine is supported:
  * [ORM](http://www.doctrine-project.org/projects/orm.html)
  
More support will come as soon as possible.  
You can now setup your configuration file and specify these following options:

```yaml
# app/config/config.yml

# QPassDbBundle configuration
q_pass_db:
    # Directory where databases will be stored
    db_dir: web/database_storage
```

## Usage
### Database creation
You can use the main service `qpdb.util.pass_db` to create a database:
```php
<?php
// your logic

// retrieve the container
$container = ...;
$container->get('qpdb.util.pass_db')->create_database($db_name, $password);
```

### Retrieve an existing database
You can use the QDatabaseManager service `qpdb.manager.qdatabase` to search an existing database:
```php
<?php
// your logic

// retrieve the container
$container  = ...;
$db_name    = 'db_name_test';
$qdatabase  = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);
```

The manager will return a null value if no database is found.

### Add a password to the existing dataabase
```php
<?php
// your logic

// retrieve the container
$container = ...;
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);

// add a new password
$pass_id = $container->get('qpdb.util.pass_db')->add_password($qdatabase, $password, $pass_to_add, $label);
```

### Retrieve a password from the database
You will need a `pass_id` in order to retrieve a password. To do so:
```php
<?php
// your logic

// retrieve the container
$container = ...;
$qdatabase = $container->get('qpdb.manager.qdatabase')->readByDatabaseName($db_name);
// If no QPassword match, the manager will return a null value
$qpassword = $container->get('qpdb.manager.qpassword')->readByPassId($pass_id);

// If you want to access the password:
$password_value = $container->get('qpdb.util.pass_db')->get_password($qdatabase, $password, $qpassword);
```

### Retrieve all passwords from the database
You can use the main service in order to perform this operation:
```php
<?php
// your logic

// retrieve the container
$container = ...;
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