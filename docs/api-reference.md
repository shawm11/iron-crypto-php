API Reference
=============

Table of Contents
-----------------

-   [`Iron` and `Iron2` Classes](#iron-and-iron2-classes)
    -   [Constructor](#constructor)

    -   [`seal($object, $password)`](#sealobject-password)
        - [`seal` Parameters](#seal-parameters)

    -   [`unseal($sealed, $password)`](#unsealsealed-password)
        - [`unseal` Parameters](#unseal-parameters)

    -   [`generateKey($password, $options)`](#generatekeypassword-options)
        - [`generateKey` Parameters](#generatekey-parameters)

    -   [`encrypt($password, $options, $data)`](#encryptpassword-options-data)
        - [`encrypt` Parameters](#encrypt-parameters)

    -   [`decrypt($password, $options, $data)`](#decryptpassword-options-data)
        - [`decrypt` Parameters](#decrypt-parameters)

    -   [`hmacWithPassword($password, $options, $data)`](#hmacwithpasswordpassword-options-data)
        - [`hmacWithPassword` Parameters](#hmacwithpassword-parameters)

    -   [`getOptions()`](#getoptions)

    -   [`setOptions($options)`](#setoptionsoptions)
        - [`setOptions` Parameters](#setoptions-parameters)

-   [`IronOptions` Class](#ironoptions-class)
    - [`$defaults` Property](#defaults-property)

-   [`IronException` Class](#ironexception-class)

-   [Iron Options](#iron-options)

`Iron` and `Iron2` Classes
--------------------------

### Constructor

1. _array_ `$options` — (Required) Options for sealing arrays and unsealing iron
  strings. The default options are not automatically set; this is intentional
  and according to the iron protocol. Set this to `IronOptions::$defaults` to
  use the default options. See the [Iron Options](#iron-options) section for
  more information.

### `seal($object, $password)`

Serializes, encrypts, and signs arrays (objects) into an iron string.

Returns an iron sealed string.

#### `seal` Parameters

1.  _array_ `$object` — (Required) Data to be sealed. Can be any array that can
    serialized using PHP's `json_encode()` function.

1.  _string_ or _array_ `$password` — (Required) Can be either a password string
    used to generate a key, an associative array that contains:

    -   _string_ or _integer_ `id` — Unique identifier (consisting of only
        underscores (`_`), letters, and numbers) for the password for when there
        are multiple possible passwords. Used for password rotation.

    -   _string_ `secret` — Password string used for both encrypting the object
       and integrity (HMAC creation and verification)

    OR

    -   _string_ or _integer_ `id` — Unique identifier (consisting of only
        underscores (`_`), letters, and numbers) for the password for when there
        are multiple possible passwords. Used for password rotation.

    -   _string_ `encryption` — Password string used for encrypting the object

    -   _string_ `integrity` — Password string used for HMAC creation and
        verification

### `unseal($sealed, $password)`

Verifies, decrypts, and reconstruct an iron protocol string into an array. In
other words, unseal iron strings created by [`seal()`](#sealobject-password).

Returns the data that was sealed as an array.

#### `unseal` Parameters

1.  _string_ `$sealed` — (Required) An iron sealed string

1.  _string_ or _array_ `$password` — (Required) The password used to seal the
    `$sealed` string. Refer to the `$password` parameter in the [`seal()`
    Method](#sealobject-password) section

### `generateKey($password, $options)`

Generates a unique key from the given password.

Returns an array that contains the following:

-   _string_ `key` — Key generated using the given password

-   _string_ `salt` — Salt used to generate the key

-   _string_ `iv` — IV (initialization vector) used to generate the key. This is
    set only if the `algorithm` in the `$options` parameter is set to
    `aes-128-ctr` or `aes-256-cbc`.

#### `generateKey` Parameters

1.  _string_ `$password` — (Required) Password used to generate the unique key

1.  _array_ `$options` — (Required) Encryption options. Usually `encryption` the
    part of the iron options. See the [Iron Options](#iron-options) section for
    more information.

### `encrypt($password, $options, $data)`

Encrypt the given data with the given password.

Returns an array containing the data as encrypted text along with the password,
salt, and IV used to encrypt the data.

#### `encrypt` Parameters

1.  _string_ `$password` — (Required) Password used to generate a unique key to
    be used for encryption

1.  _array_ `$options` — (Required) Encryption options. Usually `encryption` the
    part of the iron options. See the [Iron Options](#iron-options) section for
    more information.

1.  _string_ `$data` — (Required) Data to encrypt

### `decrypt($password, $options, $data)`

Decrypt the given encrypted data (encrypted by the [`encrypt()`
method](#encryptpassword-options-data)) with the given password.

Returns the decrypted data as a string.

#### `decrypt` Parameters

1.  _string_ `$password` — (Required) Password used for encryption by the
    `encrypt()` method

1.  _array_ `$options` — (Required) Options used for encryption by the
    `encrypt()` method.

1.  _string_ `$data` — (Required) Data to decrypt

### `hmacWithPassword($password, $options, $data)`

Calculate the HMAC digest of the given data using the given password.

Returns and array with the following:

-   _string_ `digest` — HMAC digest of the given data

-   _string_ `salt` — Salt used to generate the key that was used to calculate
    the HMAC digest

#### `hmacWithPassword` Parameters

1.  _string_ `$password` — (Required) Password used to generate a unique key to
    be used for calculating the HMAC

1.  _array_ `$options` — (Required) Integrity options. Usually `integrity` the
    part of the iron options. See the [Iron Options](#iron-options) section for
    more information.

1.  _string_ `$data` — (Required) Data for which to calculate the HMAC digest

### `getOptions()`

Get the iron options.

Return an array which are the iron options.

### `setOptions($options)`

Set the iron options to the given array.

Does not return anything.

#### `setOptions` Parameters

- _array_ $options — (Required) Array to which the iron options are to be set

`IronOptions` Class
-------------------

This class does not do anything other than contain the default iron options.

### `$defaults` Property

Contains the default set of iron options, which is secure enough for most
applications. Do not use different options, unless you know what you are doing.
See the [Iron Options](#iron-options) section if you are not going to use the
default options.

The default iron options are the following:

```php
[
    'encryption' => [
        'saltBits' => 256,
        'algorithm' => 'aes-256-cbc',
        'iterations' => 1,
        'minPasswordlength' => 32
    ],
    'integrity' => [
        'saltBits' => 256,
        'algorithm' => 'sha256',
        'iterations' => 1,
        'minPasswordlength' => 32
    ],
    'ttl' => 0,
    'timestampSkewSec' => 60,
    'localtimeOffsetMsec' => 0
]
```

`IronException` Class
---------------------

All errors thrown by the `Iron` and `Iron2` classes are instances of the
`IronException` class, which behaves the exact same way as PHP's `Exception`
class.

Iron Options
------------

The iron options set when using the [`setOptions()` Method](#setoptionsoptions)
or in the [constructor](#constructor) when creating a new `Iron` or `Iron2`
instance contain the following.

-   _array_ `encryption` — (Required) Options for how the data object array is
    encrypted. It contains the following:
    -   _string_ `salt`— (Optional) Pre-generated string to be used as the salt
        when creating the PBKDF2 derived key used for encryption. If this is
        set, `saltBits` is ignored.

    -   _integer_ `saltBits` — (Required if `salt` is not set) Number of bits to
        randomly generate to be used as the salt that is used to create the
        PBKDF2 derived key used for encryption.

    -   _string_ `algorithm` — (Required) Algorithm to use for encryption. The
        choices when using the `Iron` and `Iron2` classes are `aes-128-ctr` and
        `aes-256-cbc`.

    -   _integer_ `iterations` — (Required) Number of iterations for deriving a
        key from the encryption password. The number of ideal iterations depends
        on your application's performance and security requirements. More
        iterations means it takes longer to generate the key, but it is less
        vulnerable to brute-force attacks.

    -   _integer_ `minPasswordlength` — (Required) Minimum number of characters
        allowed for the encryption password.

-   _array_ `integrity` — (Required) Options for how the encrypted data is
    transformed into an HMAC so it can verified when the iron string is
    unsealed. It contains the following:
    -   _string_ `salt`— (Optional) Pre-generated string to be used as the salt
        when creating the PBKDF2 derived key used to create the HMAC. If this is
        set, `saltBits` is ignored.

    -   _integer_ `saltBits` — (Required if `salt` is not set) Number of bits to
        randomly generate to be used as the salt that is used to create the
        PBKDF2 derived key used to create the HMAC.

    -   _string_ `algorithm` — (Required) Algorithm to use to create the HMAC.
        The only choice when using the `Iron` and `Iron2` classes is `sha256`.

    -   _integer_ `iterations` — (Required) Number of iterations for deriving a
        key from the integrity (HMAC) password. The number of ideal iterations
        depends on your application's performance and security requirements.
        More iterations means it takes longer to generate the key, but it is
        less vulnerable to brute-force attacks.

    -   _integer_ `minPasswordlength` — (Required) Minimum number of characters
        allowed for the integrity password.

-   _integer_ `ttl` — (Optional) Length of time (in milliseconds) the sealed
    iron string is valid, `0` means forever

-   _integer_ `timestampSkewSec` — (Required) Permitted clock skew (in seconds)
    for incoming expirations

-   _integer_ `localtimeOffsetMsec` — (Optional) Local clock time offset
    expressed in a number of milliseconds (positive or negative)

See the [`$defaults` Property](#defaults-property) of the `IronOptions` class
for an example options array.
