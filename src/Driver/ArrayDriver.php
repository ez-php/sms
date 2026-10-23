<?php

declare(strict_types=1);

namespace EzPhp\Sms\Driver;

use EzPhp\Sms\SmsMessage;
use EzPhp\Sms\SmsSenderInterface;

/**
 * Class ArrayDriver
 *
 * Stores sent messages in-memory, grouped by recipient number. Designed for
 * assertions in unit and feature tests — inspect sent messages without any
 * network side-effects.
 *
 * Usage:
 *   $driver = new ArrayDriver();
 *   Sms::setSender($driver);
 *   // ... exercise code under test ...
 *   $this->assertCount(1, $driver->sentTo('+4366412345678'));
 *
 * @package EzPhp\Sms\Driver
 */
final class ArrayDriver implements SmsSenderInterface
{
    /**
     * @var array<string, list<SmsMessage>>
     */
    private array $sent = [];

    /**
     * @param string     $to
     * @param SmsMessage $message
     *
     * @return void
     */
    public function send(string $to, SmsMessage $message): void
    {
        $this->sent[$to][] = $message;
    }

    /**
     * Return all messages sent to the given phone number.
     *
     * @param string $to
     *
     * @return list<SmsMessage>
     */
    public function sentTo(string $to): array
    {
        return $this->sent[$to] ?? [];
    }

    /**
     * Clear all recorded messages.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->sent = [];
    }
}
