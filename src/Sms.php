<?php

declare(strict_types=1);

namespace EzPhp\Sms;

use Closure;
use RuntimeException;

/**
 * Class Sms
 *
 * Static facade for the configured SmsSenderInterface.
 * Call setSender() before using any static methods, or setResolver() to
 * defer resolution until first use (done automatically by
 * SmsServiceProvider::boot(), so that registering the provider does not
 * force the driver binding to resolve — and be cached as a singleton —
 * before the application has finished configuring it, e.g. before test code
 * has set SMS_DRIVER). Throws RuntimeException if called before either is
 * set — fail-fast prevents silent discards.
 *
 * Global state is intentional and documented — the facade allows
 * Sms::send() from anywhere without container access.
 *
 * @package EzPhp\Sms
 */
final class Sms
{
    /**
     * @var SmsSenderInterface|null
     */
    private static ?SmsSenderInterface $sender = null;

    /**
     * @var (Closure(): SmsSenderInterface)|null
     */
    private static ?Closure $resolver = null;

    /**
     * Set the underlying sender directly.
     *
     * @param SmsSenderInterface $sender
     *
     * @return void
     */
    public static function setSender(SmsSenderInterface $sender): void
    {
        self::$sender = $sender;
    }

    /**
     * Defer resolution of the underlying sender until the first actual
     * facade call, instead of resolving it immediately.
     *
     * @param Closure(): SmsSenderInterface $resolver
     *
     * @return void
     */
    public static function setResolver(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Reset the sender and resolver to null.
     *
     * Call this in setUp()/tearDown() of any test that touches the Sms facade.
     *
     * @return void
     */
    public static function resetSender(): void
    {
        self::$sender = null;
        self::$resolver = null;
    }

    /**
     * Deliver an SMS to one phone number.
     *
     * @param string            $to      Recipient phone number (E.164).
     * @param SmsMessage|string $message A message, or a plain body sent from the default sender.
     *
     * @throws SmsException When delivery fails.
     *
     * @return void
     */
    public static function send(string $to, SmsMessage|string $message): void
    {
        self::sender()->send($to, is_string($message) ? new SmsMessage($message) : $message);
    }

    /**
     * Return the current sender, resolving it from the deferred resolver on
     * first access, or throw if neither a sender nor a resolver has been set.
     *
     * @return SmsSenderInterface
     */
    private static function sender(): SmsSenderInterface
    {
        if (self::$sender !== null) {
            return self::$sender;
        }

        if (self::$resolver !== null) {
            self::$sender = (self::$resolver)();

            return self::$sender;
        }

        throw new RuntimeException(
            'SMS sender not set. Register SmsServiceProvider or call Sms::setSender().'
        );
    }
}
