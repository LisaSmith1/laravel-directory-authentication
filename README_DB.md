# Laravel Directory Authentication (Database Only)
Composer package for Laravel 5.0 and above to allow for directory-based authentication.

This package adds the ability to perform both local database and LDAP-based authentication.

If you wish to use full directory authentication (not just local-database auth) instead, then please look at the [Laravel Directory Authentication](https://github.com/csun-metalab/laravel-directory-authentication/blob/dev/README.md) Readme.

## Table of Contents

* [Installation](#installation)
* [Required Environment Variables](#required-environment-variables)
* [Optional Environment Variables](#optional-environment-variables)
* [The MetaUser Class](#the-metauser-class)
* [Creating a Custom User Class](#creating-a-custom-user-class)
* [Authenticating](#authenticating)
* [Masquerading](#masquerading)

## Installation

**NOTE:** There are different configuration entries to change further down based on the Laravel version you are using.

### Composer, Environment, and Service Provider

#### Composer

To install from Composer, use the following command:

```
composer require csun-metalab/laravel-directory-authentication
```

#### Service Provider

Next, add the service provider to your `providers` array in `config/app.php` in Laravel as follows:

```
'providers' => [
   //...

   CSUNMetaLab\Authentication\Providers\AuthServiceProvider::class,

   // You can also use this based on Laravel convention:
   // 'CSUNMetaLab\Authentication\Providers\AuthServiceProvider',

   //...
],
```

### Configuration (Laravel 5.2 and Above)

Next, change the `driver` to `dbauth` and add the full classname of the user model to use for database lookups in the `users` array within `config/auth.php` as follows:

```
'providers' => [
    'users' => [
        // LDAP authentication method
        'driver' => 'dbauth',

        // this can be any subclass of the CSUNMetaLab\Authentication\MetaUser class
        // or the MetaUser class itself since it works out of the box
        'model' => CSUNMetaLab\Authentication\MetaUser::class,
    ],
],
```

### Configuration (Laravel 5.0 and 5.1)

Next, change the `driver` to `dbauth` and change the `model` attribute to be the full classname of the user model to use for database lookups within `config/auth.php` as follows:

```
// LDAP authentication method
'driver' => 'dbauth',

// this can be any subclass of the CSUNMetaLab\Authentication\MetaUser class
// or the MetaUser class itself since it works out of the box 
'model' => 'CSUNMetaLab\Authentication\MetaUser',
```

### Publish Configuration

Finally, run the following Artisan command to publish the configuration:

```
php artisan vendor:publish
```

## Required Environment Variables

There are no required environment variables but there are [optional environment variables](#optional-environment-variables). The defaults should be fine in most cases but you will want to look at them just to be sure they fit into your application.

## Optional Environment Variables

There are several optional environment variables that may be added to customize the functionality of the package even further.

### DBAUTH_ALLOW_NO_PASS

Set this to `true` to turn off password validation when retrieving a user record in the database. When `false`, searching is done only with the username passed to `Auth::attempt()`.

Default is `false`.

### DBAUTH_USERNAME

This is the value that will be used to perform the search operation as the username passed to the call to `Auth::attempt()`.

Default is `email`.

### DBAUTH_PASSWORD

This is the value that will be used to perform the password validation as the password passed to the call to Auth::attempt().

Default is `password`.

## The `MetaUser` Class

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
  protected $primaryKey = "username";
  protected $table = "people";

  // this must be set for models that do not use an auto-incrementing PK
  public $incrementing = false;

  // implements MetaAuthenticatableContract#findForAuth
  public static function findForAuth($identifier) {
    return self::where('username', '=', $identifier)
      ->where('status', 'Active')
      ->first();
  }

  // implements MetaAuthenticatableContract#findForAuthToken
  public static function findForAuthToken($identifier, $token) {
    return self::where('username', '=', $identifier)
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

Authenticating with either the `MetaUser` or your custom subclass works just like regular database authentication in Laravel:

```
$creds = ['username' => 'admin', 'password' => '123'];

if(Auth::attempt($creds)) {
  // valid user in the database, so proceed into the application!
  redirect('home');
}
else
{
  // not a valid user in the database so return back
  // to the login page with an error
  redirect('login')->withErrors([
    'Invalid username or password'
  ]);
}
```

## Masquerading

This package also supports the ability for the logged-in user to become another user out of the box. This is especially useful in situations where an admin-level user may need to enter another user's account in order to triage and solve a problem directly.

### Become Another User

In order to become another user, it's as simple as finding the other user and then switching the logged-in user. The previous user is maintained in the session so switching back can be seamless.

```
// findOrFail is used here with a custom User instance but you can use any
// subclass of MetaUser that you would like or MetaUser itself
$switchUser = User::findOrFail('employee');

if(Auth::user()->masqueradeAsUser($switchUser)) {
  // successfully masquerading!
}
else
{
  // masquerade attempt failed
}
```

### Am I Masquerading?

It may be useful to determine whether the user reported by `Auth::user()` is actually a masqueraded user:

```
if(Auth::user()->isMasquerading()) {
  // I am acting as someone else
}
else
{
  // it's the original logged-in user
}
```

You can also retrieve the instance of the masquerading user (the original user that logged-in) in the following way:

```
if(Auth::user()->isMasquerading()) {
  $originalUser = Auth::user()->getMasqueradingUser();
  return "This account is really " . $originalUser->display_name;
}
else
{
  // not masquerading
  return "Not masquerading";
}
```

### Stop Masquerading

Finally, it's simple to stop masquerading and return to your original user account.

```
if(Auth::user()->stopMasquerading()) {
  // I have returned to my original user account
}
else
{
  // this account was not masquerading
}
```

Calls to `Auth::user()` will now once again report the original logged-in user.