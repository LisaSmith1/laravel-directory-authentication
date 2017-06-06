# Laravel Directory Authentication
Composer package for Laravel 5.2 and above to allow for directory-based authentication.

This package adds the ability to perform LDAP-based authentication.

Once the user has been authenticated via the directory service a local database lookup is performed in order to resolve a user model instance that is accessible through `Auth::user()`.

## Table of Contents

* [Installation](#installation)
* [Required Environment Variables](#required-environment-variables)
* [Optional Environment Variables](#optional-environment-variables)
* [The MetaUser Class](#the-metauser-class)
* [Creating a Custom User Class](#creating-a-custom-user-class)
* [Authenticating](#authenticating)
* [Masquerading](#masquerading)

## Installation

To install from Composer, use the following command:

```
composer require csun-metalab/laravel-authentication
```

Now, add the following line(s) to your `.env` file:

```
LDAP_HOST=
LDAP_BASE_DN=
```

You may also elect to add the following optional line(s) to your `.env` file to customize the functionality further:

```
LDAP_ALLOW_NO_PASS=false
LDAP_DN=
LDAP_PASSWORD=

LDAP_SEARCH_USER_ID=employeeNumber
LDAP_SEARCH_USERNAME=uid
LDAP_SEARCH_MAIL=mail
LDAP_SEARCH_MAIL_ARRAY=mailLocalAddress

LDAP_DB_USER_ID_PREFIX=
```

Next, add the service provider to your `providers` array in `config/app.php` in Laravel as follows:

```
'providers' => [
   //...

   CSUNMetaLab\Authentication\Providers\AuthServiceProvider::class,

   //...
],
```

Next, add the full classname of the user model to use for database lookups in `config/auth.php` as follows:

```
'providers' => [
    'users' => [
        // LDAP authentication method
        'driver' => 'ldap',

        // this can be any subclass of the CSUNMetaLab\Authentication\MetaUser class
        // or the MetaUser class itself since it works out of the box
        'model' => CSUNMetaLab\Authentication\MetaUser::class,
    ],
],
```

Finally, run the following Artisan command to publish the configuration:

```
php artisan vendor:publish
```

## Required Environment Variables

You added two environment variables to your `.env` file that control the connection to the LDAP server as well as its searching subtree.

### LDAP_HOST

This is the hostname or IP address of the LDAP server.

### LDAP_BASE_DN

This is the base DN under which all people to be searched for reside. This may be something like the following:

`ou=People,ou=Auth,o=Organization`

Someone under this base DN may therefore exist with the following record:

`uid=person,ou=People,ou=Auth,o=Organization`

## Optional Environment Variables

There are several optional environment variables that may be added to customize the functionality of the package even further.

### LDAP_ALLOW_NO_PASS

True to turn off user password validation (and therefore use the admin DN and password for searching for people). When false, binding and searching is done with the username and password passed to `Auth::attempt()`.

Default is `false`.

### LDAP_DN

The admin DN to use when binding and searching for people. This is only used when user password validation is turned off (so searching can still happen).

### LDAP_PASSWORD

The admin password to use when binding and searching for people. This is only used when user password validation is turned off (so searching can still happen).

### LDAP_SEARCH_USER_ID

The field to use when looking-up a person in LDAP by their user ID; this is typically a numeric field.

Default is `employeeNumber`. This is the field value that will be used when checking for a user in the associated data model and database table/view.

This can also be the same as the `LDAP_SEARCH_USERNAME` value if you want to perform both the LDAP and database lookups with the same value.

### LDAP_SEARCH_USERNAME

The field to use when looking-up a person in LDAP by their username; this is typically the POSIX ID.

Default is `uid`. This is the value that will be used to perform the search operation as the username passed to the call to `Auth::attempt()`.

If password validation is turned on, this is also the username that will be used for the bind operation when combined with the base DN.

### LDAP_SEARCH_MAIL

The field to use when looking-up a person in LDAP by their email address.

Default is `mail`.

### LDAP_SEARCH_MAIL_ARRAY

The field to use when looking-up a person in LDAP by all valid email addresses and aliases; this is typically an array attribute.

Default is `mailLocalAddress`.

### LDAP_DB_USER_ID_PREFIX

Optional prefix before the value of the employee ID primary key in the associated database table/view.

Default is blank (no prefix).

For example, LDAP might store the employee ID as numeric (`XXXXXXXXX`) but your database stores it as a textual value prepended with `members:` (ex: `members:XXXXXXXXX`). You would then set this value to `members:` and your database lookups would work. 

## The MetaUser Class

This package comes with the `CSUNMetaLab\Authentication\MetaUser` class that is configured to work properly with the directory authentication methods. It also supports [masquerading as another user](#masquerading) right out of the box with zero additional configuration.

It provides baseline implementations of the methods to look-up a user by both an identifier in the database as well as the combination of identifier and "Remember Me" token. Only the `findForAuth()` method is invoked automatically upon successful authentication; the other `findForAuthToken()` method is provided for convenience if you are implementing "Remember Me" functionality in your application.

This class expects a local database table called `users` with a primary key of `user_id`. You are free to use this class directly as long as you meet those two requirements.

## Creating a Custom User Class

It is recommended that at the minimum you create a class that extends from `CSUNMetaLab\Authentication\MetaUser` since that will give you greater control over the authentication and database functionality.

### Simple Subclass

A simple subclass is the following:

```
<?php

namespace App\Models;

use CSUNMetaLab\Authentication\MetaUser;

class User extends MetaUser
{
  protected $fillable = ['user_id', 'first_name', 'last_name', 'display_name', 'email'];

  // this must be set for models that do not use an auto-incrementing PK
  public $incrementing = false;
}

?>
```

This class still uses the `users` table but also defines the primary key as non auto-incrementing. In addition, it defines a `fillable` array and allows `User` instances to be created via mass-assignment.

It still, however, relies on the implementations of `findForAuth()` and `findForAuthToken()` that are present in the `MetaUser` class.

### Comprehensive Subclass

A more comprehensive subclass could be the following:

```
<?php

namespace App\Models;

use CSUNMetaLab\Authentication\MetaUser;

class User extends MetaUser
{
  protected $fillable = ['user_id', 'first_name', 'last_name', 'display_name', 'email'];
  protected $primaryKey = "uid";
  protected $table = "people";

  // this must be set for models that do not use an auto-incrementing PK
  public $incrementing = false;

  // implements MetaAuthenticatableContract#findForAuth
  public static function findForAuth($identifier) {
    return self::where($this->primaryKey, '=', $identifier)
      ->where('status', 'Active')
      ->first();
  }

  // implements MetaAuthenticatableContract#findForAuthToken
  public static function findForAuthToken($identifier, $token) {
    return self::where($this->primaryKey, '=', $identifier)
      ->where('remember_token', '=', $token)
      ->where('status', 'Active')
      ->first();
  }
}

?>
```

A couple of additional things are happening in this subclass:

1. Both the table and primary keys have been changed to use custom values
2. The two post-authentication methods have been overridden to perform status checks to ensure only active users are allowed to use the application

## Authenticating

