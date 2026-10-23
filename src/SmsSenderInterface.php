<?php

declare(strict_types=1);

namespace EzPhp\Sms;

/**
 * Interface SmsSenderInterface
 *
 * Contract for SMS delivery drivers (Twilio, Vonage, Log, Null, Array).
 * A driver delivers exactly one message to exactly one phone number; fan-out
 * to multiple recipients is the caller's responsibility.
 *
 * @package EzPhp\Sms
 */
interface SmsSenderInterface
{
    /**
     * Deliver an SMS to one phone number.
     *
     * @param string     $to      Recipient phone number, passed to the provider as-is (E.164, e.g. "+4366412345678").
     * @param SmsMessage $message The message to deliver.
     *
     * @throws SmsException When the underlying transport or provider rejects the message.
     *
     * @return void
     */
    public function send(string $to, SmsMessage $message): void;
}
