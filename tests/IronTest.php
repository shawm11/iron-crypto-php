<?php

namespace Shawm11\Iron\Tests;

use PHPUnit\Framework\TestCase;
use Shawm11\Iron\Iron;
use Shawm11\Iron\IronOptions;
use Shawm11\Iron\IronException;

class IronTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    protected $object = [
        'a' => 1,
        'b' => 2,
        'c' => [3, 4, 5],
        'd' => [
            'e' => 'f'
        ]
    ];

    protected $password = 'some_not_random_password_that_is_at_least_32_characters';

    public function testIron()
    {
        $this->describe('Iron class', function () {
            $this->it('should be able to seal an array and then parse the sealed string',
                function () {
                    $iron = new Iron(IronOptions::$defaults);
                    $sealed = $iron->seal($this->object, $this->password);
                    $unsealed = $iron->unseal($sealed, $this->password);

                    expect($unsealed)->equals($this->object);
                }
            );

            $this->it("should have the MAC prefix version set to '2'", function () {
                $iron = new Iron(IronOptions::$defaults);
                $sealed = $iron->seal($this->object, $this->password);

                expect(substr($sealed, 0, 7))->equals('Fe26.2*');
            });

            $this->it('should be able to seal and unseal an array when the expiration is set',
                function () {
                    // Set expiration
                    $options = IronOptions::$defaults;
                    $options['ttl'] = 200;

                    $iron = new Iron($options);
                    $sealed = $iron->seal($this->object, $this->password);
                    $unsealed = $iron->unseal($sealed, $this->password);

                    expect($unsealed)->equals($this->object);
                }
            );

            $this->it('should be able to seal and unseal an array when the expiration and time offset are set',
                function () {
                    $options = IronOptions::$defaults;
                    $options['ttl'] = 200; // Set expiration
                    $options['localtimeOffsetMsec'] = -100000; // Set time offset

                    $iron = new Iron($options);
                    $sealed = $iron->seal($this->object, $this->password);
                    $unsealed = $iron->unseal($sealed, $this->password);

                    expect($unsealed)->equals($this->object);
                }
            );

            $this->it(
                'should be able to seal and unseal an array when the ' .
                'password is chosen from an array of passwords',
                function ($encryptPassword, $possiblePasswords) {
                    $iron = new Iron(IronOptions::$defaults);
                    $sealed = $iron->seal($this->object, $encryptPassword);
                    $unsealed = $iron->unseal($sealed, $possiblePasswords);

                    expect($unsealed)->equals($this->object);
                },
                [
                    'examples' => [
                        [
                            $this->password,
                            ['default' => $this->password]
                        ],
                        [
                            ['id' => 0, 'secret' => "{$this->password}1"],
                            ["{$this->password}1", "{$this->password}2", "{$this->password}3"]
                        ],
                        [
                            ['id' => 2, 'secret' => "{$this->password}3"],
                            ["{$this->password}1", "{$this->password}2", "{$this->password}3"]
                        ],
                        [
                            ['id' => 'a', 'secret' => "{$this->password}1"],
                            ['a' => "{$this->password}1", 'b' => "{$this->password}2", 'c' => "{$this->password}3"]
                        ],
                        [
                            ['id' => 'c', 'secret' => "{$this->password}3"],
                            ['a' => "{$this->password}1", 'b' => "{$this->password}2", 'c' => "{$this->password}3"]
                        ]
                    ]
                ]
            );

            $this->it('should fail to parse a sealed string when the password is not found',
                function () {
                    $possiblePasswords = [ "{$this->password}2",  "{$this->password}3"];
                    $iron = new Iron(IronOptions::$defaults);
                    // Use password that does not exist
                    $sealed = $iron->seal($this->object, ['id' => 10, 'secret' => "{$this->password}1"]);

                    $this->assertThrowsWithMessage(
                        IronException::class,
                        'Cannot find password: 10',
                        function () use ($iron, $sealed, $possiblePasswords) {
                            $iron->unseal($sealed, $possiblePasswords);
                        }
                    );
                }
            );
        });
    }

    public function testSeal()
    {
        $this->describe('Iron::seal()', function () {
            $this->it('should throw an error when the password is missing', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))->seal($this->object, null);
                });
            });

            $this->it('should throw an error when the password ID is invalid', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Invalid password ID', function () {
                    (new Iron(IronOptions::$defaults))->seal($this->object, ['id' => 'foo$', 'secret' => 'foo']);
                });
            });
        });
    }

    public function testUnseal()
    {
        $this->describe('Iron::unseal()', function () {
            $this->it('should throw an error when number of sealed components is wrong', function () {
                $sealed = 'x*Fe26.2**91f7df7b826113a0bd608408b9b867f1e0cf89d14fc0e6f425c2dae5a2ba41ab' .
                          '*bXNHFmid88Bm-OcCsDaUrA' .
                          '*djluTHNnaHhiMWxVaE95elBrSGtZOUx3b1lqcE50R08zM1BiNmd5RXVwb0tYa2FDSW4wbk1PeWtlbzIwN1h5dQ*' .
                          '*e757ca3132236c72ed9f947043706b1029546ab6bc92e9a266b4a4b9db9e164e'.
                          '*TSj6sizzKHw0CxXM0mTiCjD4_QhENMZKtBCJyPoCnmk';

                $this->assertThrowsWithMessage(
                    IronException::class,
                    'Incorrect number of sealed components',
                    function () use ($sealed) {
                        (new Iron(IronOptions::$defaults))->unseal($sealed, $this->password);
                    }
                );
            });

            $this->it('should throw an error when the password is missing', function () {
                $sealed = 'Fe26.2**91f7df7b826113a0bd608408b9b867f1e0cf89d14fc0e6f425c2dae5a2ba41ab' .
                          '*bXNHFmid88Bm-OcCsDaUrA' .
                          '*djluTHNnaHhiMWxVaE95elBrSGtZOUx3b1lqcE50R08zM1BiNmd5RXVwb0tYa2FDSW4wbk1PeWtlbzIwN1h5dQ*' .
                          '*e757ca3132236c72ed9f947043706b1029546ab6bc92e9a266b4a4b9db9e164e' .
                          '*TSj6sizzKHw0CxXM0mTiCjD4_QhENMZKtBCJyPoCnmk';

                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () use ($sealed) {
                    (new Iron(IronOptions::$defaults))->unseal($sealed, null);
                });
            });

            $this->it('should throw an error when the password is an empty string', function () {
                $sealed = 'Fe26.2**91f7df7b826113a0bd608408b9b867f1e0cf89d14fc0e6f425c2dae5a2ba41ab' .
                          '*bXNHFmid88Bm-OcCsDaUrA' .
                          '*djluTHNnaHhiMWxVaE95elBrSGtZOUx3b1lqcE50R08zM1BiNmd5RXVwb0tYa2FDSW4wbk1PeWtlbzIwN1h5dQ*' .
                          '*e757ca3132236c72ed9f947043706b1029546ab6bc92e9a266b4a4b9db9e164e' .
                          '*TSj6sizzKHw0CxXM0mTiCjD4_QhENMZKtBCJyPoCnmk';

                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () use ($sealed) {
                    (new Iron(IronOptions::$defaults))->unseal($sealed, '');
                });
            });

            $this->it('should throw an error when the MAC prefix is wrong', function () {
                $sealed = 'Fe27.2**91f7df7b826113a0bd608408b9b867f1e0cf89d14fc0e6f425c2dae5a2ba41ab' .
                          '*bXNHFmid88Bm-OcCsDaUrA' .
                          '*djluTHNnaHhiMWxVaE95elBrSGtZOUx3b1lqcE50R08zM1BiNmd5RXVwb0tYa2FDSW4wbk1PeWtlbzIwN1h5dQ*' .
                          '*e757ca3132236c72ed9f947043706b1029546ab6bc92e9a266b4a4b9db9e164e' .
                          '*TSj6sizzKHw0CxXM0mTiCjD4_QhENMZKtBCJyPoCnmk';

                $this->assertThrowsWithMessage(IronException::class, 'Wrong mac prefix', function () use ($sealed) {
                    (new Iron(IronOptions::$defaults))->unseal($sealed, $this->password);
                });
            });

            $this->it('should throw an error when the integrity check fails', function () {
                $sealed = 'Fe26.2**91f7df7b826113a0bd608408b9b867f1e0cf89d14fc0e6f425c2dae5a2ba41ab' .
                          '*bXNHFmid88Bm-OcCsDaUrA' .
                          '*djluTHNnaHhiMWxVaE95elBrSGtZOUx3b1lqcE50R08zM1BiNmd5RXVwb0tYa2FDSW4wbk1PeWtlbzIwN1h5dQ*' .
                          '*e757ca3132236c72ed9f947043706b1029546ab6bc92e9a266b4a4b9db9e164e' .
                          '*Qj53DFE3GZd5yigt-mVl9lnp0VUoSjh5a5jgDmod1EZ';

                $this->assertThrowsWithMessage(IronException::class, 'Bad HMAC value', function () use ($sealed) {
                    (new Iron(IronOptions::$defaults))->unseal($sealed, $this->password);
                });
            });
        });
    }

    public function testGenerateKey()
    {
        $this->describe('Iron::generateKey()', function () {
            $this->it('should throw an error when the password is missing', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))->generateKey(null, IronOptions::$defaults['encryption']);
                });
            });

            $this->it('should throw an error when the password is an empty string', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))->generateKey('', IronOptions::$defaults['encryption']);
                });
            });

            $this->it('should throw an error when the options is not an array', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Bad options', function () {
                    (new Iron(IronOptions::$defaults))->generateKey($this->password, 'hello');
                });
            });

            $this->it('should throw an error when the password is too short', function () {
                $this->assertThrowsWithMessage(
                    IronException::class,
                    'Password string too short (min 32 characters required)',
                    function () {
                        (new Iron(IronOptions::$defaults))
                            ->generateKey('this is too short', IronOptions::$defaults['encryption']);
                    }
                );
            });

            $this->it('should throw an error when an unknown algorithm is specified', function () {
                $options = IronOptions::$defaults['encryption'];
                $options['algorithm'] = 'unknown';

                $this->assertThrowsWithMessage(
                    IronException::class,
                    'Unknown algorithm: unknown',
                    function () use ($options) {
                        (new Iron(IronOptions::$defaults))->generateKey($this->password, $options);
                    }
                );
            });

            $this->it('should throw an error when no salt or salt bits are provided', function () {
                $options = [
                    'algorithm' => 'sha256',
                    'iterations' => 2,
                    'minPasswordlength' => 32,
                    // No 'salt' or 'saltBits'
                ];

                $this->assertThrowsWithMessage(
                    IronException::class,
                    'Missing salt or saltBits options',
                    function () use ($options) {
                        (new Iron(IronOptions::$defaults))->generateKey($this->password, $options);
                    }
                );
            });

            $this->it('should throw an error when invalid salt bits are provided', function () {
                $options = [
                    'saltBits' => 99999999999999999999,
                    'algorithm' => 'sha256',
                    'iterations' => 2,
                    'minPasswordlength' => 32,
                ];

                $this->assertThrowsWithMessage(
                    IronException::class,
                    'Failed to generate salt',
                    function () use ($options) {
                        (new Iron(IronOptions::$defaults))->generateKey($this->password, $options);
                    }
                );
            });
        });
    }

    public function testDecrypt()
    {
        $this->describe('Iron::decrypt()', function () {
            $this->it('should throw an error when the password is missing', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))->decrypt(null, IronOptions::$defaults['encryption'], 'data');
                });
            });

            $this->it('should throw an error when the password is an empty string', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))->decrypt('', IronOptions::$defaults['encryption'], 'data');
                });
            });
        });
    }

    public function testHmacWithPassword()
    {
        $this->describe('Iron::hmacWithPassword()', function () {
            $this->it('should throw an error when the password is missing', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))
                        ->hmacWithPassword(null, IronOptions::$defaults['integrity'], 'data');
                });
            });

            $this->it('should throw an error when the password is an empty string', function () {
                $this->assertThrowsWithMessage(IronException::class, 'Empty password', function () {
                    (new Iron(IronOptions::$defaults))
                        ->hmacWithPassword('', IronOptions::$defaults['integrity'], 'data');
                });
            });
        });
    }
}
