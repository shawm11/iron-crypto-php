<?php

namespace Shawm11\Iron;

class IronOptions
{
    /**
     * An array containing the default options
     *
     * @var array
     */
    public static $defaults = [
        // (Required) Options for how the data object is encrypted
        'encryption' => [
            'saltBits' => 256,
            'algorithm' => 'aes-256-cbc',
            'iterations' => 1,
            'minPasswordlength' => 32
        ],
        // (Required) Options for how the encrypted data is tranformed into an
        // HMAC so it can verified when the iron string is unsealed
        'integrity' => [
            'saltBits' => 256,
            'algorithm' => 'sha256',
            'iterations' => 1,
            'minPasswordlength' => 32
        ],
        // (Optional) Milliseconds, 0 means forever
        'ttl' => 0,
        // (Required) Seconds of permitted clock skew for incoming expirations
        'timestampSkewSec' => 60,
        // (Optional) Local clock time offset express in a number of
        // milliseconds (positive or negative)
        'localtimeOffsetMsec' => 0
    ];
}
