<?php

declare(strict_types=1);

namespace EzPhp\Sms;

use EzPhp\Contracts\ConfigInterface;
use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\ServiceProvider;
use EzPhp\HttpClient\HttpClient;
use EzPhp\Sms\Driver\ArrayDriver;
use EzPhp\Sms\Driver\LogDriver;
use EzPhp\Sms\Driver\NullDriver;
use EzPhp\Sms\Driver\TwilioDriver;
use EzPhp\Sms\Driver\VonageDriver;

/**
 * Class SmsServiceProvider
 *
 * Binds SmsSenderInterface to the configured driver, then wires the Sms
 * static facade in boot().
 *
 * Requires HttpClientServiceProvider (ez-php/http-client) to be registered
 * before this provider when using the twilio/vonage drivers — they resolve
 * HttpClient from the container.
 *
 * Configuration keys (in config/sms.php or environment):
 *   - sms.driver           — "null" (default) | "array" | "log" | "twilio" | "vonage"
 *   - sms.from             — default sender number / alphanumeric sender ID
 *   - sms.log_path         — log file for the log driver ("" = error_log())
 *   - sms.twilio.sid       — Twilio Account SID
 *   - sms.twilio.token     — Twilio Auth Token
 *   - sms.vonage.key       — Vonage API key
 *   - sms.vonage.secret    — Vonage API secret
 *
 * @package EzPhp\Sms
 */
final class SmsServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            SmsSenderInterface::class,
            function (ContainerInterface $app): SmsSenderInterface {
                $config = $app->make(ConfigInterface::class);
                $from = $this->configString($config, 'sms.from');

                return match ($this->configString($config, 'sms.driver')) {
                    'array' => new ArrayDriver(),
                    'log' => new LogDriver($this->configString($config, 'sms.log_path'), $from),
                    'twilio' => new TwilioDriver(
                        $app->make(HttpClient::class),
                        $this->configString($config, 'sms.twilio.sid'),
                        $this->configString($config, 'sms.twilio.token'),
                        $from,
                    ),
                    'vonage' => new VonageDriver(
                        $app->make(HttpClient::class),
                        $this->configString($config, 'sms.vonage.key'),
                        $this->configString($config, 'sms.vonage.secret'),
                        $from,
                    ),
                    default => new NullDriver(),
                };
            }
        );
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Sms::setResolver(fn (): SmsSenderInterface => $this->app->make(SmsSenderInterface::class));
    }

    /**
     * @param ConfigInterface $config
     * @param string          $key
     *
     * @return string
     */
    private function configString(ConfigInterface $config, string $key): string
    {
        $value = $config->get($key, '');

        return is_string($value) ? $value : '';
    }
}
