<?php

namespace Shawm11\Iron\Tests;

use PHPUnit\Framework\TestCase;
use Shawm11\Iron\Iron2;
use Shawm11\Iron\IronOptions;
use Shawm11\Iron\IronException;

class Iron2Test extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    /** @var array */
    protected $object = [
        'a' => 1,
        'b' => 2,
        'c' => [3, 4, 5],
        'd' => [
            'e' => 'f'
        ]
    ];

    /** @var string **/
    protected $password = 'some_not_random_password_that_is_at_least_32_characters';

    /**
     * @return void
     */
    public function testIron2()
    {
        $this->describe('Iron2 class', function () {

            $this->it('should be able to seal an array and then parse the sealed string',
                function () {
                    $iron = new Iron2(IronOptions::$defaults);
                    $sealed = $iron->seal($this->object, $this->password);
                    $unsealed = $iron->unseal($sealed, $this->password);

                    expect($unsealed)->toEqual($this->object);
                }
            );

            $this->it("should have the MAC prefix version set to '2.1'", function () {
                $iron = new Iron2(IronOptions::$defaults);
                $sealed = $iron->seal($this->object, $this->password);

                expect(substr($sealed, 0, 9))->toEqual('Fe26.2.1*');
            });
        });
    }
}
