<?php

declare(strict_types=1);

namespace EzPhp\Sms;

/**
 * Class SmsException
 *
 * Base exception for all SMS errors: missing sender, a non-2xx response or
 * an error status from Twilio/Vonage, or an unwritable log file.
 *
 * @package EzPhp\Sms
 */
final class SmsException extends \RuntimeException
{
}
