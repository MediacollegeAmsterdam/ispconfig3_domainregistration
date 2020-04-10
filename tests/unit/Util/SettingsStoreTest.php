<?php

namespace Domainregistration\Util;

use Domainregistration\Exception\Http\ClientException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use \db;

final class SettingsStoreTest extends TestCase
{
    private MockObject $db;
    private SettingsStore $subject;

    public function setUp(): void
    {
        $this->db = $this->createMock(db::class);

        $this->subject = new SettingsStore($this->db);
    }

    public function testGetsValue(): void
    {
        $this->db
            ->expects($this->once())
            ->method('queryOneRecord')
            ->willReturn(['config_value' => 'foo']);

        $this->subject->get('key');
    }

    public function testInsertsValue(): void
    {
        $this->db
            ->expects($this->once())
            ->method('queryOneRecord')
            ->willReturn(null);

        $this->db
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('INSERT INTO ??'),
                SettingsStore::TABLE,
                SettingsStore::COLUMN_CONFIG_KEY,
                SettingsStore::COLUMN_CONFIG_VALUE,
                'foo',
                'bar'
            );

        $this->subject->set('foo', 'bar');
    }

    public function testUpdatesValue(): void
    {
        $this->db
            ->expects($this->once())
            ->method('queryOneRecord')
            ->willReturn(['config_value' => 'bar']);

        $this->db
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('UPDATE ?? SET ??'),
                SettingsStore::TABLE,
                SettingsStore::COLUMN_CONFIG_VALUE,
                'bar',
                SettingsStore::COLUMN_CONFIG_KEY,
                'foo'
            );

        $this->subject->set('foo', 'bar');
    }
}
