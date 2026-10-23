<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Sms\Driver\ArrayDriver;
use EzPhp\Sms\Sms;
use EzPhp\Sms\SmsMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

/**
 * Class SmsTest
 *
 * @package Tests
 */
#[CoversClass(Sms::class)]
#[UsesClass(ArrayDriver::class)]
#[UsesClass(SmsMessage::class)]
final class SmsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Sms::resetSender();
    }

    protected function tearDown(): void
    {
        Sms::resetSender();

        parent::tearDown();
    }

    public function testSendThrowsBeforeSenderIsSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMS sender not set');

        Sms::send('+1', 'Hi');
    }

    public function testSendDelegatesMessageToSender(): void
    {
        $driver = new ArrayDriver();
        Sms::setSender($driver);
        $message = new SmsMessage('Hi', 'EzPhp');

        Sms::send('+1', $message);

        $this->assertSame([$message], $driver->sentTo('+1'));
    }

    public function testSendWrapsPlainStringBody(): void
    {
        $driver = new ArrayDriver();
        Sms::setSender($driver);

        Sms::send('+1', 'Plain body');

        $sent = $driver->sentTo('+1');
        $this->assertCount(1, $sent);
        $this->assertSame('Plain body', $sent[0]->body);
        $this->assertNull($sent[0]->from);
    }

    public function testResolverIsCalledLazilyOnce(): void
    {
        $driver = new ArrayDriver();
        $calls = 0;
        Sms::setResolver(function () use ($driver, &$calls): ArrayDriver {
            $calls++;

            return $driver;
        });

        $this->assertSame(0, $calls);

        Sms::send('+1', 'One');
        Sms::send('+1', 'Two');

        $this->assertSame(1, $calls);
        $this->assertCount(2, $driver->sentTo('+1'));
    }
}
