<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Application\Application;
use EzPhp\HttpClient\HttpClientServiceProvider;
use EzPhp\Sms\Driver\ArrayDriver;
use EzPhp\Sms\Driver\LogDriver;
use EzPhp\Sms\Driver\NullDriver;
use EzPhp\Sms\Driver\TwilioDriver;
use EzPhp\Sms\Driver\VonageDriver;
use EzPhp\Sms\Sms;
use EzPhp\Sms\SmsMessage;
use EzPhp\Sms\SmsSenderInterface;
use EzPhp\Sms\SmsServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Sms\ApplicationTestCase;

/**
 * Class SmsServiceProviderTest
 *
 * @package Tests
 */
#[CoversClass(SmsServiceProvider::class)]
#[UsesClass(Sms::class)]
#[UsesClass(SmsMessage::class)]
#[UsesClass(NullDriver::class)]
#[UsesClass(ArrayDriver::class)]
#[UsesClass(LogDriver::class)]
#[UsesClass(TwilioDriver::class)]
#[UsesClass(VonageDriver::class)]
final class SmsServiceProviderTest extends ApplicationTestCase
{
    private const array ENV_VARS = ['SMS_DRIVER', 'SMS_FROM', 'SMS_LOG_PATH', 'SMS_TWILIO_SID', 'SMS_TWILIO_TOKEN', 'SMS_VONAGE_KEY', 'SMS_VONAGE_SECRET'];

    protected function setUp(): void
    {
        $this->clearEnv();
        Sms::resetSender();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->clearEnv();
        Sms::resetSender();

        parent::tearDown();
    }

    /**
     * @param Application $app
     *
     * @return void
     */
    protected function configureApplication(Application $app): void
    {
        $app->register(HttpClientServiceProvider::class);
        $app->register(SmsServiceProvider::class);
    }

    public function testDefaultDriverIsNull(): void
    {
        $this->assertInstanceOf(NullDriver::class, $this->app()->make(SmsSenderInterface::class));
    }

    /**
     * @return array<string, array{string, class-string<SmsSenderInterface>}>
     */
    public static function driverProvider(): array
    {
        return [
            'array' => ['array', ArrayDriver::class],
            'log' => ['log', LogDriver::class],
            'twilio' => ['twilio', TwilioDriver::class],
            'vonage' => ['vonage', VonageDriver::class],
            'unknown falls back to null' => ['carrier-pigeon', NullDriver::class],
        ];
    }

    /**
     * @param string                           $driver
     * @param class-string<SmsSenderInterface> $expected
     *
     * @return void
     */
    #[DataProvider('driverProvider')]
    public function testDriverIsSelectedFromConfig(string $driver, string $expected): void
    {
        putenv('SMS_DRIVER=' . $driver);

        $this->assertInstanceOf($expected, $this->app()->make(SmsSenderInterface::class));
    }

    public function testFacadeResolvesConfiguredDriverLazily(): void
    {
        putenv('SMS_DRIVER=array');

        Sms::send('+4366412345678', 'Hello');

        $driver = $this->app()->make(SmsSenderInterface::class);
        $this->assertInstanceOf(ArrayDriver::class, $driver);
        $this->assertCount(1, $driver->sentTo('+4366412345678'));
    }

    /**
     * @return void
     */
    private function clearEnv(): void
    {
        foreach (self::ENV_VARS as $var) {
            putenv($var . '=');
        }
    }
}
