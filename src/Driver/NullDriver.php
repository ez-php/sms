<?php

declare(strict_types=1);

namespace EzPhp\Sms\Driver;

use EzPhp\Sms\SmsMessage;
use EzPhp\Sms\SmsSenderInterface;

/**
 * Class NullDriver
 *
 * Silently discards all messages. Use as the default driver in environments
 * with no configured provider credentials, or in tests where SMS
 * side-effects are irrelevant.
 *
 * @package EzPhp\Sms\Driver
 */
final class NullDriver implements SmsSenderInterface
{
    /**
     * @param string     $to
     * @param SmsMessage $message
     *
     * @return void
     */
    public function send(string $to, SmsMessage $message): void
    {
        // intentional no-op
    }
}
