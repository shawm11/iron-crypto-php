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
