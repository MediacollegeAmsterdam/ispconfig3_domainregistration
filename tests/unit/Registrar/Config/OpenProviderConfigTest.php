<?php

namespace Domainregistration\Registrar\Config;

use Domainregistration\Registrar\Config\OpenproviderConfig;
use PHPUnit\Framework\TestCase;

final class OpenproviderConfigTest extends TestCase
{
    private OpenproviderConfig $subject;

    public function setUp(): void
    {
        $this->subject = new OpenproviderConfig();
    }

    /**
     * @dataProvider provider
     */
    public function testSettingAndGetting($property)
    {
        $setter = sprintf('set%s', ucfirst($property));
        $getter = sprintf('get%s', ucfirst($property));
        $value = md5(time());

        call_user_func([$this->subject, $setter], $value);
        $retrievedValue = call_user_func([$this->subject, $getter]);

        $this->assertEquals($value, $retrievedValue);
    }

    public function provider()
    {
        return [
            ['endpoint'],
            ['username'],
            ['password'],
            ['ownerHandle'],
            ['adminHandle'],
            ['techHandle'],
            ['billingHandle'],
        ];
    }
}