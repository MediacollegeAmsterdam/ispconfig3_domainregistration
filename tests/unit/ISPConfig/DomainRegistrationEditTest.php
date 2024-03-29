<?php

namespace Domainregistration\ISPConfig;

use Domainregistration\Registrar\Openprovider;
use Domainregistration\Util\Exiter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use \app;
use \db;
use \tform_actions;
use \tpl;
use stdClass;

final class DomainRegistrationEditTest extends TestCase
{
    use PHPMock;

    public app $app;
    private MockObject $registrar;
    private MockObject $tformActions;
    private DomainregistrationEdit $subject;

    public function setUp(): void
    {
        global $app;
        $this->app = new app();
        $this->app->db = $this->createMock(db::class);
        $this->app->tpl = $this->createMock(tpl::class);
        $app = $this->app;

        $_SESSION['s']['user']['typ'] = 'user';
        $_SESSION['s']['user']['userid'] = 42;
        $_SESSION['s']['user']['username'] = 'foo';

        $this->registrar = $this->createMock(Openprovider::class);
        $this->tformActions = $this->createMock(tform_actions::class);
        $this->exiter = $this->createMock(Exiter::class);

        $this->subject = new DomainregistrationEdit($this->registrar, $this->tformActions, $this->exiter);
    }

    public function testOnShowEndSetsDomainVar(): void
    {
        $this->subject->dataRecord['domain'] = 'foo.bar';

        $this->app->tpl
            ->expects($this->once())
            ->method('setVar')
            ->with('domain', 'foo.bar');

        $this->subject->onShowEnd();
    }

    public function testOnInsertSaveChecksAvailability(): void
    {
        $this->subject->dataRecord['domain'] = 'foo.bar';

        $this->registrar
            ->expects($this->once())
            ->method('isAvailable');

        $this->subject->onInsertSave('sql');
    }

    public function testOnInsertSaveSetsConfirmVar(): void
    {
        $this->subject->dataRecord['domain'] = 'foo.bar';

        $this->registrar
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->app->tpl
            ->expects($this->once())
            ->method('setVar')
            ->with('domain_is_available', true);

        $this->subject->onInsertSave('sql');
    }

    public function testOnInsertSaveSetsReturnUrl(): void
    {
        $this->subject->dataRecord['domain'] = 'foo.bar';

        $this->subject->onInsertSave('sql');

        $this->assertEquals(
            'foo.php?registration_success=1',
            $_SESSION['s']['form']['return_to_url']
        );
    }

    public function testCreatesDomainAlias(): void
    {
        $this->subject->dataRecord['domain'] = 'foo.bar';

        $this->tformActions
            ->expects($this->any())
            ->method('sites_web_domain_get')
            ->willReturn([[
                'server_id' => 42,
                'domain_id' => 1337,
                'domain' => 'foo.bar'
            ]]);

        $this->registrar
            ->expects($this->once())
            ->method('isAvailable');

        $this->subject->onInsertSave('sql');
    }

    public function testCreatesDnsRecords(): void
    {
        $this->subject->dataRecord['domain'] = 'foo.bar';

        $this->tformActions
            ->expects($this->any())
            ->method('sites_web_domain_get')
            ->willReturn([[
                'server_id' => 42,
                'domain_id' => 1337,
                'domain' => 'foo.bar'
            ]]);

        $this->registrar
            ->expects($this->once())
            ->method('isAvailable');

        $this->registrar
            ->expects($this->any())
            ->method('dnsAddRecordA');

        $function = $this->getFunctionMock(__NAMESPACE__, 'gethostbyname');
        $function
            ->expects($this->once())
            ->willReturn('1.2.3.4');

        $this->subject->onInsertSave('sql');
    }

    public function testOnShowEdit(): void
    {
        $this->subject->dataRecord['registrar_identifier'] = '42';

        $this->registrar
            ->expects($this->once())
            ->method('getInfo')
            ->with('42')
            ->willReturn(array('data' => array('auth_code' => '1337')));

        $this->app->tpl
            ->expects($this->once())
            ->method('setVar')
            ->with('auth_code', '1337');

        $this->subject->onShowEdit();

        $this->assertEquals(
            $this->app->tform->wordbook['transfer_domain_txt'],
            $this->app->tform->formDef['tabs']['domainregistration']['title']
        );
    }

    public function testOnShowNewDoesNothingIfThereAreNoLimits(): void
    {
        global $conf;

        $conf['domainregistration'] = [];

        $this->app->db
            ->expects($this->never())
            ->method('query');

        $this->subject->onShowNew();
    }

    public function testOnShowNewDoesNothingIfUserIsNotAdmin(): void
    {
        global $conf;

        $conf['domainregistration']['max_active_domains_per_client'] = 1;
        $_SESSION['s']['user']['typ'] = 'admin';

        $this->app->db
            ->expects($this->never())
            ->method('query');

        $this->subject->onShowNew();
    }

    public function testOnShowNewEnforcesTheConfiguredLimit(): void
    {
        global $conf;

        $conf['domainregistration']['max_active_domains_per_client'] = 1;

        $mock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['rows'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('rows')
            ->willReturn(1);

        $this->app->db
            ->expects($this->once())
            ->method('query')
            ->willReturn($mock);

        $this->subject->onShowNew();

        $this->assertEquals(
            $this->app->tform->wordbook['limit_domainregistration_txt'],
            $this->app->error
        );
    }

    public function testOnShowNewDoesNotErrorIfUserIsWithinConfiguredLimit(): void
    {
        global $conf;

        $conf['domainregistration']['max_active_domains_per_client'] = 2;

        $mock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['rows'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('rows')
            ->willReturn(1);

        $this->app->db
            ->expects($this->once())
            ->method('query')
            ->willReturn($mock);

        $this->subject->onShowNew();

        $this->assertEquals(
            null,
            $this->app->error
        );
    }
}
