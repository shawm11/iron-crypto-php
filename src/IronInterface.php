<?php

namespace Shawm11\Iron;

interface IronInterface
{
    /**
     * @param  array  $options  The options for sealing arrays and unsealing
     *                          iron strings. See IronOptions::defaults for an
     *                          example of the available options.
     */
    public function __construct(array $options);

    /**
     * Serializes, encrypts, and signs arrays into an iron protocol string
     *
     * @param  array  $object  The data being sealed. Can be any array that can
     *                         serialized using PHP's `json_encode()` function.
     * @param  string|array  $password  Can be either a password string used to
     *                                  generate a key, an associative array
     *                                  containing and `id` and a `secret`, or
     *                                  an associative array containing an `id`,
     *                                  `encryption`, and an `integrity`
     * @throws IronException
     * @return string  An iron sealed string
     */
    public function seal(array $object, $password);

    /**
     * Verifies, decrypts, and reconstruct an iron protocol string into an
     * array. Unseal iron strings created by `seal()`.
     *
     * @param  string  $sealed  An iron sealed string
     * @param  string|array  $password  The string or array password used to
     *                                  create the given iron sealed string
     * @throws IronException
     * @return array  The data that was sealed
     */
    public function unseal($sealed, $password);

    /**
     * Generates a unique key from the given password
     *
     * @param  string  $password
     * @param  array  $options  The "encryption" options, which include the keys
     *                          `salt` (string) or `saltBits` (integer),
     *                          `algorithm` (string), and `iterations` (integer)
     * @throws IronException
     * @return array
     */
    public function generateKey($password, $options);

    /**
     * Encrypt the given data with the given password
     *
     * @param  string  $password
     * @param  array  $options  The "encryption" options, which include the keys
     *                          `salt` (string) or `saltBits` (integer),
     *                          `algorithm` (string), and `iterations` (integer)
     * @param  string  $data  The string to encrypt
     * @throws IronException
     * @return array  An array containing the data as encrypted text along with
     *                the password, salt, and IV used to encrypt the data
     */
    public function encrypt($password, $options, $data);

    /**
     * Decrypt the given encrypted data with the given password
     *
     * @param  string  $password
     * @param  array  $options  The "encryption" options, which include the keys
     *                          `salt` (string) or `saltBits` (integer),
     *                          `algorithm` (string), and `iterations` (integer)
     * @param  string  $data  The encrypted string to decrypt
     * @throws IronException
     * @return string  The decrypted string
     */
    public function decrypt($password, $options, $data);

    /**
     * Calculate the HMAC digest of the given data using the given password
     *
     * @param  string  $password
     * @param  array  $options  The "integrity" options, which include the keys
     *                          `salt` (string) or `saltBits` (integer),
     *                          `algorithm` (string), and `iterations` (integer)
     * @param  string  $data  The data string to calculate the HMAC
     * @return array  An associative array with the keys `digest` and `salt`
     */
    public function hmacWithPassword($password, $options, $data);

    /**
     * Get the iron options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Set the iron options to the given array
     *
     * @param  array  $options
     */
    public function setOptions(array $options);
}
