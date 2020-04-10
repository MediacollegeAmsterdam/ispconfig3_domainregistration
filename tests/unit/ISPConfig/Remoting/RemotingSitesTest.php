<?php

namespace Domainregistration\ISPConfig\Remoting;

use PHPUnit\Framework\TestCase;

final class RemotingSitesTest extends TestCase
{
    private RemotingSites $subject;

    public function setUp(): void
    {
        $this->subject = new RemotingSites();
    }

    public function testOverridesGetSession(): void
    {
        $_SESSION['s']['user']['userid'] = 1337;

        $result = $this->subject->getSession(42);

        $this->assertEquals(1337, $result['remote_userid']);
        $this->assertEquals(true, $result['client_login']);
        $this->assertEquals('', $result['remote_functions']);
        $this->assertArrayHasKey('remote_session', $result);
        $this->assertArrayHasKey('tstamp', $result);
    }
}
