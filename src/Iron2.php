<?php

namespace Shawm11\Iron;

class Iron2 extends Iron
{
    /**
     * The algorithm used to generate the PBKDF2 key derivation
     *
     * @var string
     */
    protected $derivedKeyAlgorithm = 'sha256';

    /**
     * The MAC normalization format version. This prevents the comparison of MAC
     * values generated with different normalized string formats.
     *
     * @var string
     */
    protected $macFormatVersion = '2.1';
}
