Iron Crypto PHP
===============

![Version Number](https://img.shields.io/packagist/v/shawm11/iron-crypto.svg)
![PHP Version](https://img.shields.io/packagist/php-v/shawm11/iron-crypto.svg)
[![License](https://img.shields.io/github/license/shawm11/iron-crypto-php.svg)](LICENSE.md)

A PHP implementation of the 6.x version of the [**iron**](https://github.com/hapijs/iron)
cryptographic utility.

Table of Contents
-----------------

<!--lint disable list-item-spacing-->

- [What is Iron?](#what-is-iron)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Usage](#usage)
  - [`Iron` vs `Iron2` Classes](#iron-vs-iron2-classes)
  - [Examples](#examples)
    - [Examples of Sealed Iron Strings](#examples-of-sealed-iron-strings)
    - [Code Example of Common Usage](#code-example-of-common-usage)
    - [Password Rotation Example](#password-rotation-code-example)
- [API](#api)
- [Security Considerations](#security-considerations)
- [Related Projects](#related-projects)
- [Contributing/Development](#contributingdevelopment)
- [Versioning](#versioning)
- [License](#license)

<!--lint enable list-item-spacing-->

What is iron?
-------------

According to the [_iron_ API](https://github.com/hapijs/iron/blob/master/API.md):

> **iron** is a cryptographic utility for sealing a JSON object using symmetric
> key encryption with message integrity verification. Or in other words, it lets
> you encrypt an object, send it around (in cookies, authentication credentials,
> etc.), then receive it back and decrypt it. The algorithm ensures that the
> message was not tampered with, and also provides a simple mechanism for
> password rotation.

_iron_ can be considered as an alternative to JSON Web Tokens (JWT). Check out
[this _iron_ issue](https://github.com/hapijs/iron/issues/30) for a small
discussion of the difference between _iron_ and JWT.

Note that _iron_ is often spelled in all lowercase letters; however, the _i_ is
capitalized in the class names in this package.

Getting Started
---------------

### Prerequisites

- Git 2.9+
- PHP 5.5.0+
- OpenSSL PHP Extension
- JSON PHP Extension
- [Composer](https://getcomposer.org/)

### Installation

Download and install using [Composer](https://getcomposer.org/):

```shell
composer require shawm11/iron-crypto
```

Usage
-----

### `Iron` vs `Iron2` Classes

Either the `Iron` or `Iron2` class can be used to seal or unseal iron strings.
The `Iron2` class includes a fix for an [issue with PDKDF2](https://github.com/hapijs/iron/issues/55),
so it is a bit more secure than the `Iron` class. However, the iron strings
`Iron` and `Iron2` are not compatible with each other. The MAC format version in
the sealed string created using `Iron2` is 2.1 instead of 2 to indicate the
incompatibility.

_iron_ strings created using the `Iron` class can be unsealed by other _iron_
implementations and it can unseal iron strings from other implementations. This
is not true for the `Iron2` class.

In summary, use the `Iron2` class (**RECOMMENDED**) if:

- you need or want a bit more security
- compatibility with other iron implementations is not important

and use the `Iron` class if:

- unsealing an _iron_ string created by another implementation
- the sealed _iron_ string created will be unsealed by another implementation

### Examples

#### Examples of Sealed Iron Strings

Array to be sealed:
```php
[
    'a' => 1,
    'b' => 2,
    'c' => [3, 4, 5],
    'd' => [
        'e' => 'f'
    ]
]
```

##### `Iron2::seal()`

Password: `some_not_random_password_that_is_at_least_32_characters`

Output:
```text
Fe26.2.1**50a5bec38a21775318b487bda8eb5bac8ef0033fa14ab3d7d963643b648fb50a*dZ7cUbgFie4_EKYQ1H1RyA*mclk0QCWDb-irF7E5quIcRa52t4TXmo3Jq1BnJFgVv4dZq9fWnB0CUdRA8bKXIEX**da6bb68d955f9db04e9739a2a197ce9780de56f9be26ba24b7bf145c12851d53*0xYQdFBJxipufS03zBu6VZmIlHClv6CTlCc_To1rbIU
```

##### `Iron2::seal()` with password rotation

Passwords:
```php
[
    "some_not_random_password_that_is_at_least_32_characters1",
    "some_not_random_password_that_is_at_least_32_characters2",
    "some_not_random_password_that_is_at_least_32_characters3" // Chose this one to create output
]
```

Output:

```text
Fe26.2.1*2*292e8975ab168c4aff5af0674ae7e49f11307a367e75aee7f5f71063d8132523*QkjFNS0jl7963ENLosY25g*uKNcL7JAlDPURnvMb0C_jHyELe0b84554QcYzeaYWiHI1x0Qwq3Njikf_z_iLYxX**18280c5865db88bd915570325c56f8b6897a3daf710d8a9c9330ead5f392ec4d*ogb2rO5-QiOQk28gfpa3p2PimRM5y015C892SQ_c3y8
```

##### `Iron::seal()`

Password: `some_not_random_password_that_is_at_least_32_characters`

Output:
```text
Fe26.2**6589f8726e6b87f875bd9cbdea1985642d8d2e82168360586cf9cdb46b370fcc*-2XpTXRy5ZL0gJK6Qx9i4Q*hZa7pqt31QIR_ihVZ6qjUv_b0v5KLd1Enhq5q0IjbSfbvnUm_kRDahIC-nAoCsjJ**c74d1c46525da622ddc699c8dabf3902e1f1497bf54e086004fa560d85082e71*1qpfA_ZlR4r5Uo99Py1UU_l7v8lZYjtFI-4QVFYHA1g
```

#### Code Example of Common Usage

```php
<?php

// Here Iron2 is used, but this code works the same way with Iron
use Shawm11\Iron\Iron2;
use Shawm11\Iron\IronOptions;
use Shawm11\Iron\IronException;

// This could be some decoded JSON
$obj = [
    'a' => 1,
    'b' => 2,
    'c' => [3, 4, 5],
    'd' => [
        'e' => 'f'
    ]
];
$password = 'some_not_random_password_that_is_at_least_32_characters';
$sealed = '';
$unsealed = [];

// Initialize with the default options
$iron = new Iron2(IronOptions::defaults);

// Seal an array (or a decoded JSON array)
try {
    $sealed = $iron->seal($obj, $password); // Output is a string
    echo $sealed;
} catch (IronException $e) {
    echo 'SEAL ERROR: ' . $e->getMessage();
}

// Unseal an iron-sealed object string, which can be sent via cookies, a URI
// query parameter, or an HTTP header attribute
try {
    $unsealed = $iron->unseal($sealed, $password); // Output is an array
    echo $unsealed;
} catch (IronException $e) {
    echo 'UNSEAL ERROR: ' . $e->getMessage();
}
```

#### Password Rotation Code Example

```php
<?php

// Here Iron is used (unlike the previous example), but this code works the same
// way with Iron2
use Shawm11\Iron;
use Shawm11\Iron\IronOptions;
use Shawm11\Iron\IronException;

// This could be some decoded JSON
$obj = [
    'a' => 1,
    'b' => 2,
    'c' => [3, 4, 5],
    'd' => [
        'e' => 'f'
    ]
];

// The password IDs (array keys) for the array of possible passwords can be
// anything as long as they contain only letters, numbers, and underscores.
$possiblePasswords = [
    'a' => 'some_not_random_password_that_is_at_least_32_characters1',
    'b' => 'some_not_random_password_that_is_at_least_32_characters2',
    '0' => 'some_not_random_password_that_is_at_least_32_characters3',
    '1' => 'some_not_random_password_that_is_at_least_32_characters4',
    '_' => 'some_not_random_password_that_is_at_least_32_characters5',
    'Something_different42' => 'some_not_random_password_that_is_at_least_32_characters6',
    // The password with the special name of 'default' will be the password that
    // is used if no password ID is specified in an iron string. In other words,
	// it is the default password.
    'default' => 'some_not_random_password_that_is_at_least_32_characters'
];

$chosenPasswordKey = '1';
$password = [
    'id' => $chosenPasswordKey, // Used to uniquely identify the password
    'secret' => $possiblePasswords[$chosenPasswordKey] // The actual password
];

$sealed = '';
$unsealed = [];

// Initialize with the default options
$iron = new Iron(IronOptions::defaults);

// Seal an array (or decoded JSON array)
try {
    $sealed = $iron->seal($obj, $password); // Output is a string
    echo $sealed;
} catch (IronException $e) {
    echo 'SEAL ERROR: ' . $e->getMessage();
}

// Unseal an iron-sealed object string, which can be sent via cookies, a URI
// query parameter, or an HTTP header attribute
try {
    $unsealed = $iron->unseal($sealed, $possiblePasswords); // Output is an array
    echo $unsealed;
} catch (IronException $e) {
    echo 'UNSEAL ERROR: ' . $e->getMessage();
}
```

API
---

See the [API Reference](docs/api-reference.md) for details about the API.

Security Considerations
-----------------------

See the [Security Considerations](https://github.com/hapijs/iron/blob/master/API.md#security-considerations)
section of iron's API document.

Related Projects
----------------

-   [Oz PHP Implementation](https://github.com/shawm11/oz-auth-php) — Oz is a
    web authorization protocol that is an alternative to OAuth 1.0a and
    OAuth 2.0 three-legged authorization. Oz utilizes both Hawk and _iron_.

-   [Hawk PHP Implementation](https://github.com/shawm11/hawk-auth-php) — Hawk
    is an HTTP authentication scheme that is an alternative to OAuth 1.0a and
    OAuth 2.0 two-legged authentication.

Contributing/Development
------------------------

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on coding style, Git
commit message guidelines, and other development information.

Versioning
----------

This project uses [SemVer](http://semver.org/) for versioning. For the versions
available, see the tags on this repository.

License
-------

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
