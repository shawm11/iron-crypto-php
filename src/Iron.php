<?php

namespace Shawm11\Iron;

class Iron implements IronInterface
{
    /** @var array */
    protected $options = [];

    /**
     * Configuration of the available encryption algorithms
     *
     * @var array
     */
    protected $algorithms = [
        'aes-128-ctr' => [
            'keyBits' => 128,
            'ivBits' => 128
        ],
        'aes-256-cbc' => [
            'keyBits' => 256,
            'ivBits' => 128
        ],
        'sha256' => [
            'keyBits' => 256
        ]
    ];

    /**
     * The algorithm used to generate the PBKDF2 key derivation
     *
     * @var string
     */
    protected $derivedKeyAlgorithm = 'sha1';

    /**
     * The MAC normalization format version. This prevents the comparison of MAC
     * values generated with different normalized string formats.
     *
     * @var string
     */
    protected $macFormatVersion = '2';

    /**
     * The string that is prepended to the output when sealing a JSON object.
     * This is initialized in the constructor.
     *
     * @var string
     */
    protected $macPrefix = '';

    public function __construct(array $options)
    {
        $this->setOptions($options);
        $this->macPrefix = "Fe26.{$this->macFormatVersion}";
    }

    /**
     * {@inheritdoc}
     */
    public function seal(array $object, $password)
    {
        // Get local time offset
        $localtimeOffset = empty($this->options['localtimeOffsetMsec'])
            ? 0
            : $this->options['localtimeOffsetMsec'];
        // Measure "now" (in microseconds since Unix epoch) before any other
        // processing
        $now = floor(microtime(true) * 1000) + $localtimeOffset;

        // Serialize object array (i.e. convert array to JSON string)
        $objectString = json_encode($object);

        // Check if there was an error parsing the JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IronException('Failed to stringify object: ' . json_last_error_msg());
        }

        // Obtain password
        $passwordId = '';
        $password = $this->normalizePassword($password);

        // Check if the password ID is set
        if (isset($password['id']) && !is_null($password['id'])) {
            // Check if the password ID has at least one character that is not a
            // letter, number, or underscore (_)
            if (!preg_match('/^\w+$/', $password['id'])) {
                throw new IronException('Invalid password ID');
            }

            $passwordId = $password['id'];
        }

        // Encrypt object string
        $cipher = $this->encrypt($password['encryption'], $this->options['encryption'], $objectString);

        // Encode the encrypted string into URL-safe Base64
        $encryptedBase64 = $this->base64urlEncode($cipher['encrypted']);

        // Encode the IV into URL-safe Base64
        $iv = $this->base64urlEncode($cipher['key']['iv']);

        // Get the expiration date/time (if there is one)
        $expiration = empty($this->options['ttl'])
            ? ''
            : ($now + $this->options['ttl']);

        $macBaseString = implode('*', [
            $this->macPrefix,
            $passwordId,
            $cipher['key']['salt'],
            $iv,
            $encryptedBase64,
            $expiration
        ]);

        // HMAC the combined values
        $mac = $this->hmacWithPassword($password['integrity'], $this->options['integrity'], $macBaseString);

        // Put it all together:
        // prefix*[password-id]*encryption-salt*encryption-iv*encrypted*[expiration]*hmac-salt*hmac
        $sealed = implode('*', [
            $macBaseString,
            $mac['salt'],
            $mac['digest']
        ]);

        return $sealed;
    }

    /**
     * {@inheritdoc}
     */
    public function unseal($sealed, $password)
    {
        // Get local time offset
        $localtimeOffset = empty($this->options['localtimeOffsetMsec'])
            ? 0
            : $this->options['localtimeOffsetMsec'];
        // Measure "now" (in microseconds since Unix epoch) before any other
        // processing
        $now = floor(microtime(true) * 1000) + $localtimeOffset;

        // Break string into components
        $parts = explode('*', $sealed);

        // Check if there is an incorrect number of parts
        if (count($parts) !== 8) {
            throw new IronException('Incorrect number of sealed components');
        }

        $macPrefix = $parts[0];
        $passwordId = $parts[1];
        $encryptionSalt = $parts[2];
        $encryptionIv = $parts[3];
        $encryptedBase64 = $parts[4];
        $expiration = $parts[5];
        $hmacSalt = $parts[6];
        $hmac = $parts[7];
        $macBaseString = implode('*', [
            $macPrefix,
            $passwordId,
            $encryptionSalt,
            $encryptionIv,
            $encryptedBase64,
            $expiration
        ]);

        // Check prefix
        if ($macPrefix !== $this->macPrefix) {
            throw new IronException('Wrong MAC prefix');
        }

        // Check expiration
        if ($expiration !== '') {
            // Check if the expiration is not a number
            if (!preg_match('/^\d+$/', $expiration)) {
                throw new IronException('Invalid expiration');
            }

            // The number is too large to be an integer, so evaluate it as a
            // double
            $exp = floor(doubleval($expiration));

            if ($exp <= ($now - $this->options['timestampSkewSec'] * 1000)) {
                throw new IronException('Expired seal');
            }
        }

        // Obtain password
        if (is_array($password)) {
            // If no password ID, use default password (if set)
            $passwordIndex = ($passwordId !== '') ? $passwordId : 'default';
            if (!isset($password[$passwordIndex]) || is_null($password[$passwordIndex])) {
                throw new IronException('Cannot find password: ' . $passwordId);
            }

            $password = $password[$passwordIndex];
        }

        $password = $this->normalizePassword($password);

        // Check HMAC
        $macOptions = $this->options['integrity'];
        $macOptions['salt'] = $hmacSalt;
        $mac = $this->hmacWithPassword($password['integrity'], $macOptions, $macBaseString);

        // Use a timing-attack-safe string comparison via PHP's `hash_equals()`
        // function. The order of the parameters matter with the `hash_equals()`
        // function.
        //
        // For more information about timing attacks & constant-time string
        // comparison: https://codahale.com/a-lesson-in-timing-attacks/
        if (!hash_equals($mac['digest'], $hmac)) {
            throw new IronException('Bad HMAC value');
        }

        // Decrypt
        $encrypted = $this->base64urlDecode($encryptedBase64);
        $decryptOptions = $this->options['encryption'];
        $decryptOptions['salt'] = $encryptionSalt;
        $decryptOptions['iv'] = $this->base64urlDecode($encryptionIv);
        $decrypted = $this->decrypt($password['encryption'], $decryptOptions, $encrypted);

        // Parse JSON
        $object = json_decode($decrypted, true);

        // Check if there was an error parsing the JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IronException('Failed parsing sealed object JSON: ' . json_last_error_msg());
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function generateKey($password, $options)
    {
        // Check if the password is an empty string
        if (!$password) {
            throw new IronException('Empty password');
        }

        // Check if the options are a non-empty array
        if (!$options || gettype($options) !== 'array') {
            throw new IronException('Bad options');
        }

        // Get algorithm
        $algorithm = isset($this->algorithms[$options['algorithm']])
            ? $this->algorithms[$options['algorithm']]
            : null;

        // Check if an invalid algorithm was given
        if (!$algorithm) {
            throw new IronException('Unknown algorithm: ' . $options['algorithm']);
        }

        // Check if the password is too short
        if (strlen($password) < $options['minPasswordlength']) {
            throw new IronException(
                'Password string too short (min '
                . $options['minPasswordlength']
                . ' characters required)'
            );
        }

        // Get the 'salt' option value if it was set
        $salt = isset($options['salt']) ? $options['salt'] : null;

        // Check if the 'salt' option was not set
        if (!$salt) {
            // Check if the 'saltBits' option was not set or NULL
            if (empty($options['saltBits'])) {
                throw new IronException('Missing salt or saltBits options');
            }

            
            $saltGenerationFailMessage = 'Failed to generate salt';
            $saltLength = intval(floor($options['saltBits'] / 8));
            
            // Check if the length of salt is greater than 0 to prevent an Error
            // from being thrown in PHP 7+
            if ($saltLength <= 0) {
                throw new IronException($saltGenerationFailMessage);
            }
            
            // Generate salt
            $randomSalt = openssl_random_pseudo_bytes($saltLength);

            // Check if the generation of random bytes failed
            if ($randomSalt === false) {
                throw new IronException($saltGenerationFailMessage);
            }

            $salt = bin2hex($randomSalt);
        }

        // Generate a PBKDF2 key derivation from the password and salt using
        // this derived key algorithm specified
        $derivedKey = hash_pbkdf2(
            $this->derivedKeyAlgorithm,
            $password,
            $salt,
            $options['iterations'],
            $algorithm['keyBits'] / 8,
            true
        );
        $result = [
            'key' => $derivedKey,
            'salt' => $salt,
        ];

        // Check if the IV in the options are set
        if (isset($options['iv']) && !is_null($options['iv'])) {
            $result['iv'] = $options['iv'];
        } elseif (isset($algorithm['ivBits']) && !is_null($algorithm['ivBits'])) { // If the algorithm IV bits are set
            $randomIv = openssl_random_pseudo_bytes(intval(floor($algorithm['ivBits'] / 8)));

            // Check if generation of random bytes failed
            if ($randomIv === false) {
                throw new IronException('Failed to generate IV');
            }

            $result['iv'] = $randomIv;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt($password, $options, $data)
    {
        // Generate a key using the password and a IV
        $key = $this->generateKey($password, $options);

        $cipher = [
            'encrypted' => openssl_encrypt(
                $data,
                $options['algorithm'],
                $key['key'],
                OPENSSL_RAW_DATA,
                isset($key['iv']) ? $key['iv'] : null
            ),
            'key' => $key
        ];

        if ($cipher['encrypted'] === false) {
            throw new IronException('Encryption failed');
        }

        return $cipher;
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt($password, $options, $data)
    {
        $key = $this->generateKey($password, $options);
        $decipher = openssl_decrypt(
            $data,
            $options['algorithm'],
            $key['key'],
            OPENSSL_RAW_DATA,
            $key['iv']
        );

        if ($decipher === false) {
            throw new IronException('Decryption failed');
        }

        return $decipher;
    }

    /**
     * {@inheritdoc}
     */
    public function hmacWithPassword($password, $options, $data)
    {
        // Generate a key using the password
        $key = $this->generateKey($password, $options);
        // HMAC the data using the password
        $hmac = hash_hmac($options['algorithm'], $data, $key['key'], true);
        $result = [
            'digest' => $this->base64urlEncode($hmac),
            'salt' => $key['salt']
        ];

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Normalizes a password parameter into a [ id, encryption, integrity ]
     * object
     *
     * @param string|array  $password  String or object with [ id, secret ] or
     *                                 [ id, encryption, integrity ]
     * @return array
     */
    protected function normalizePassword($password)
    {
        $obj = [];

        if (is_array($password)) {
            $obj['id'] = $password['id'];
            $obj['encryption'] = (isset($password['secret']) && !is_null($password['secret']))
                ? $password['secret']
                : $password['encryption'];
            $obj['integrity'] = (isset($password['secret']) && !is_null($password['secret']))
                ? $password['secret']
                : $password['integrity'];
        } else { // password is a string
            $obj['encryption'] = $password;
            $obj['integrity'] = $password;
        }

        return $obj;
    }

    /**
     * Encode the data given into a URL-safe Base64 encoded string.
     * Follows RFC 4648.
     *
     * @param string $data  The data to encode into a URL-safe Base64 string
     * @return string
     */
    protected function base64urlEncode($data)
    {
        // Based on the `base64urlEncode()` function in the Hoek NodeJS library
        if (gettype($data) !== 'string') {
            throw new IronException('Value not a string');
        }

        // Code from http://php.net/manual/en/function.base64-encode.php#103849
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode the given URL-safe Base64 string.
     * Follows RFC 4648.
     *
     * @param string $data  The URL-safe Base64 string to decode
     * @return string
     */
    protected function base64urlDecode($data)
    {
        // Based on the `base64urlDecode()` function in the Hoek NodeJS library
        if (gettype($data) !== 'string') {
            throw new IronException('Value not a string');
        }

        // Also based on the `base64urlDecode()` function in the Hoek NodeJS
        // library
        if (!preg_match('/^[\w\-]*$/', $data)) {
            throw new IronException('Invalid character');
        }

        // Code from http://php.net/manual/en/function.base64-encode.php#103849
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
