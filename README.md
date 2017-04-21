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
  
## Documentation
For usage documentation, please see:
[Resources/doc/index.md](Resources/doc/index.md)
