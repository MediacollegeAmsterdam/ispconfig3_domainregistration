<?php

namespace Domainregistration\ISPConfig;

use PHPUnit\Framework\TestCase;

final class RegistrationPolicyTest extends TestCase
{
    /**
     * @dataProvider configurationCases
     */
    public function testRequiresExplicitOptIn($conf, $enabled): void
    {
        $this->assertSame($enabled, RegistrationPolicy::isEnabled($conf));
    }

    public function configurationCases(): array
    {
        return [
            'missing configuration' => [[], false],
            'missing flag' => [['domainregistration' => []], false],
            'disabled' => [['domainregistration' => ['registration_enabled' => false]], false],
            'enabled' => [['domainregistration' => ['registration_enabled' => true]], true],
            'string false' => [['domainregistration' => ['registration_enabled' => 'false']], false],
            'string true' => [['domainregistration' => ['registration_enabled' => 'true']], false],
            'integer one' => [['domainregistration' => ['registration_enabled' => 1]], false],
            'null' => [['domainregistration' => ['registration_enabled' => null]], false],
        ];
    }
}
