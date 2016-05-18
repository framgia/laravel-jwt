# Native Laravel JWT Authentication Driver

[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/framgia/laravel-jwt.svg)](https://scrutinizer-ci.com/g/framgia/laravel-jwt/?branch=master)
[![Travis Build Status](https://img.shields.io/travis/framgia/laravel-jwt.svg)](https://travis-ci.org/framgia/laravel-jwt)
[![Packagist Version](https://img.shields.io/packagist/vpre/framgia/laravel-jwt.svg)](https://packagist.org/packages/framgia/laravel-jwt)

This package provides a native Laravel driver for JWT Authentication.

## Installation

To install this package you will need:

* Laravel 5.2 +
* PHP 5.5.9 + (Laravel dependency)

Install via **composer** - edit your `composer.json` to require the package.

```json
"require": {
    "framgia/laravel-jwt": "0.1.*"
}
```

Then run `composer update` in your terminal to install it in.

*OR*

Run `composer require framgia/laravel-jwt` to automatically install latest version

After installation you need to add the service provider to your `app.php` config file.

```php
'providers' => [
    // ...
    Framgia\Jwt\JwtServiceProvider::class,
    // ...
],
```


## Configuration

Add the `jwt` driver configuration to your `auth.php` config file.

```php
'guards' => [
    // ...
    'jwt' => [
        'driver' => 'jwt',
        'provider' => 'users', // May be replaced with preferred provider.
    ],
    // ...
],
```

`laravel-jwt` uses the application secret key for encrypting the tokens. You can use a separate key by defining the `JWT_KEY` environment variable in your server configuration or `.env` file.

In order to overwrite default settings publish the `jwt.php` config file to your project.

```
$ php artisan vendor:publish --provider="Framgia\Jwt\JwtServiceProvider"
```

## Usage

To obtain the guard instance use the facade or service container.

```php
Auth::guard('jwt');
app('auth')->guard('jwt');
```

The guard includes basic methods required by the `Guard` contract and following methods for authentication:

```php
// Retrieve new authentication token by user credentials
$token = $guard->attempt($credentials);

// Blacklist current user token to discard authentication
$guard->logout();
```

In order to pass authentication, provide the returned token with `Authorization` header:

```
Authorization: Bearer TOKEN_HERE
```

By default token includes `jit`, `sub` and `exp` claims.
If you need to provide custom claims, add `ProvidesCredentials` contract to your authenticatable instance.

```php
// ...
use Framgia\Jwt\Contracts\ProvidesCredentials;
// ...

class User implements Authenticatable, ProvidesCredentials
{
    // ...

    /**
     * Get credentials for JWT.
     *
     * @return array
     */
    public function getCredentials()
    {
        return [
            'admin' => $this->isAdmin(),
            'role' => $this->role,
        ];
    }

    // ...
}
```

## Contributing

Use the GitHub repository [Issues](https://github.com/framgia/laravel-jwt/issues) to report bugs or [Pull Requests](https://github.com/framgia/laravel-jwt/pulls) to provide bug fixes and feature updates.

## License

The MIT License (MIT)

Copyright (c) 2016 Framgia Vietnam Ltd.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## See Also
* [JWT Auth package](https://github.com/tymondesigns/jwt-auth) by Sean Tymon 
