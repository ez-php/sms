<?php

declare(strict_types=1);

namespace EzPhp\Sms;

/**
 * Class SmsMessage
 *
 * Immutable value object describing one outgoing SMS.
 *
 * @package EzPhp\Sms
 */
final readonly class SmsMessage
{
    /**
     * SmsMessage Constructor
     *
     * @param string      $body Message text. Segmentation into multiple SMS parts is left to the provider.
     * @param string|null $from Sender number or alphanumeric sender ID; null uses the driver's configured default.
     */
    public function __construct(
        public string $body,
        public ?string $from = null,
    ) {
    }
}
