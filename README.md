Iron Crypto PHP
===============

![Version Number](https://img.shields.io/packagist/v/shawm11/iron-crypto.svg)
![PHP Version](https://img.shields.io/packagist/php-v/shawm11/iron-crypto.svg)
[![License](https://img.shields.io/github/license/shawm11/iron-crypto-php.svg)](LICENSE.md)

A PHP implementation of the 5.x version of the [**iron**](https://github.com/hueniverse/iron)
cryptographic utility. For more information about iron, read its
[README](https://github.com/hueniverse/iron/blob/master/README.md).

Table of Contents
-----------------

-   [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)

-   [Usage](#usage)
    -   [`Iron` vs `Iron2` Classes](#iron-vs-iron2-classes)

    -   [Examples](#examples)
        - [Password Rotation Example](#password-rotation-example)

-   [API](#api)

-   [Security Considerations](#security-considerations)

-   [Contributing/Development](#contributingdevelopment)

-   [Versioning](#versioning)

-   [License](#license)

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
The `Iron2` class includes a fix for an [issue with PDKDF2](https://github.com/hueniverse/iron/issues/55),
so it is a bit more secure than the `Iron` class. However, the iron strings
`Iron` and `Iron2` are not compatible with each other. The MAC format version in
the sealed string created using `Iron2` is 2.1 instead of 2 to indicate the
incompatibility.

Iron strings created using the `Iron` class can be unsealed by other iron
implementations and it can unseal iron strings from other implementations. This
is not true for the `Iron2` class.

In summary, use the `Iron2` class (**RECOMMENDED**) if:

- you need or want a bit more security
- compatibility with other iron implementations is not important

and use the `Iron` class if:

- unsealing an iron string created by another implementation
- the sealed iron string created will be unsealed by another implementation

### Examples

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

#### Password Rotation Example

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

See the [Security Considerations](https://github.com/hueniverse/iron#security-considerations)
section of iron's README.

Contributing/Development
------------------------

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on coding style, Git
commit message guidelines, and other development information.

Versioning
----------

This project using [SemVer](http://semver.org/) for versioning. For the versions
available, see the tags on this repository.

License
-------

This project is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).
