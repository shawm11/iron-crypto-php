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
