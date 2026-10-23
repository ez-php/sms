<?php

declare(strict_types=1);

namespace EzPhp\Sms\Driver;

use EzPhp\Sms\SmsException;
use EzPhp\Sms\SmsMessage;
use EzPhp\Sms\SmsSenderInterface;

/**
 * Class LogDriver
 *
 * Writes every outgoing message to a log file instead of delivering it.
 * Designed for local development, where the full body is needed to read
 * e.g. a one-time code without a real provider account.
 *
 * Each entry records the timestamp, sender, recipient and body on one line
 * (newlines in the body are escaped). The log file is created (including its
 * parent directory) on first write.
 *
 * @package EzPhp\Sms\Driver
 */
final class LogDriver implements SmsSenderInterface
{
    /**
     * LogDriver Constructor
     *
     * @param string $logPath     Absolute path to the log file. An empty string writes via error_log().
     * @param string $defaultFrom Sender recorded when the message has none.
     */
    public function __construct(
        private readonly string $logPath,
        private readonly string $defaultFrom = '',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function send(string $to, SmsMessage $message): void
    {
        $entry = sprintf(
            "[%s] SMS From: %s | To: %s | Body: %s\n",
            date('Y-m-d H:i:s'),
            $message->from ?? $this->defaultFrom,
            $to,
            str_replace(["\r", "\n"], ['\r', '\n'], $message->body),
        );

        if ($this->logPath === '') {
            error_log($entry);

            return;
        }

        $dir = dirname($this->logPath);

        if ($dir !== '.' && !is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new SmsException(sprintf('Cannot create SMS log directory %s.', $dir));
        }

        if (file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new SmsException(sprintf('Cannot write SMS log file %s.', $this->logPath));
        }
    }
}
