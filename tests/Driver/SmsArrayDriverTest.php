<?php

declare(strict_types=1);

namespace Tests\Sms\Driver;

use EzPhp\Sms\Driver\ArrayDriver;
use EzPhp\Sms\Driver\NullDriver;
use EzPhp\Sms\SmsMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class SmsArrayDriverTest
 *
 * Covers both in-memory drivers. `Sms` prefix: `ArrayDriverTest` and
 * `NullDriverTest` are already taken in the shared `Tests\` namespace run.
 *
 * @package Tests\Sms\Driver
 */
#[CoversClass(ArrayDriver::class)]
#[CoversClass(NullDriver::class)]
#[UsesClass(SmsMessage::class)]
final class SmsArrayDriverTest extends TestCase
{
    public function testArrayDriverRecordsMessagesPerRecipient(): void
    {
        $driver = new ArrayDriver();
        $first = new SmsMessage('One');
        $second = new SmsMessage('Two');

        $driver->send('+1', $first);
        $driver->send('+1', $second);

        $this->assertSame([$first, $second], $driver->sentTo('+1'));
        $this->assertSame([], $driver->sentTo('+2'));

        $driver->reset();

        $this->assertSame([], $driver->sentTo('+1'));
    }

    public function testNullDriverDiscardsSilently(): void
    {
        $this->expectNotToPerformAssertions();

        new NullDriver()->send('+1', new SmsMessage('Ignored'));
    }
}
