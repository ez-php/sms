<?php

declare(strict_types=1);

namespace Tests\Sms\Driver;

use EzPhp\Sms\Driver\LogDriver;
use EzPhp\Sms\SmsException;
use EzPhp\Sms\SmsMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class SmsLogDriverTest
 *
 * `Sms` prefix: `LogDriverTest` is already taken by ez-php/mail in the shared
 * `Tests\` namespace run.
 *
 * @package Tests\Sms\Driver
 */
#[CoversClass(LogDriver::class)]
#[UsesClass(SmsMessage::class)]
#[UsesClass(SmsException::class)]
final class SmsLogDriverTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ez-sms-log-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'sms.log';

        if (is_file($file)) {
            unlink($file);
            rmdir(dirname($file));
            rmdir($this->dir);
        }

        parent::tearDown();
    }

    public function testSendAppendsOneLinePerMessageAndCreatesDirectory(): void
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'sms.log';
        $driver = new LogDriver($path, '+15550001111');

        $driver->send('+4366412345678', new SmsMessage("Line one\nLine two"));
        $driver->send('+4366400000000', new SmsMessage('Second', 'EzPhp'));

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('SMS From: +15550001111 | To: +4366412345678 | Body: Line one\nLine two', $lines[0]);
        $this->assertStringContainsString('SMS From: EzPhp | To: +4366400000000 | Body: Second', $lines[1]);
    }

    public function testSendThrowsWhenLogFileIsNotWritable(): void
    {
        // A directory path cannot be opened as a file.
        $driver = new LogDriver(sys_get_temp_dir());

        $this->expectException(SmsException::class);

        @$driver->send('+4366412345678', new SmsMessage('Hi'));
    }
}
