<?php

declare(strict_types=1);

namespace Tests\Sms\Driver;

use EzPhp\HttpClient\FakeTransport;
use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpResponse;
use EzPhp\Sms\Driver\TwilioDriver;
use EzPhp\Sms\SmsException;
use EzPhp\Sms\SmsMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class TwilioDriverTest
 *
 * @package Tests\Sms\Driver
 */
#[CoversClass(TwilioDriver::class)]
#[UsesClass(SmsMessage::class)]
#[UsesClass(SmsException::class)]
final class TwilioDriverTest extends TestCase
{
    public function testSendPostsFormWithBasicAuth(): void
    {
        $transport = new FakeTransport([
            'https://api.twilio.com/*' => HttpResponse::fake(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);
        $driver = new TwilioDriver(new HttpClient($transport), 'AC123', 'secret', '+15550001111');

        $driver->send('+4366412345678', new SmsMessage('Your code is 123456'));

        $recorded = $transport->getRecorded();
        $this->assertCount(1, $recorded);
        $this->assertSame('https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json', $recorded[0]['url']);
        $this->assertSame('Basic ' . base64_encode('AC123:secret'), $recorded[0]['headers']['authorization']);

        parse_str($recorded[0]['body'], $body);
        $this->assertSame('+4366412345678', $body['To']);
        $this->assertSame('+15550001111', $body['From']);
        $this->assertSame('Your code is 123456', $body['Body']);
    }

    public function testMessageSenderOverridesDefault(): void
    {
        $transport = new FakeTransport(['https://api.twilio.com/*' => HttpResponse::fake('{}', 201)]);
        $driver = new TwilioDriver(new HttpClient($transport), 'AC123', 'secret', '+15550001111');

        $driver->send('+4366412345678', new SmsMessage('Hi', '+15559999999'));

        parse_str($transport->getRecorded()[0]['body'], $body);
        $this->assertSame('+15559999999', $body['From']);
    }

    public function testSendThrowsWithProviderMessageOnError(): void
    {
        $transport = new FakeTransport([
            'https://api.twilio.com/*' => HttpResponse::fake(['code' => 21211, 'message' => "The 'To' number is not valid."], 400),
        ]);
        $driver = new TwilioDriver(new HttpClient($transport), 'AC123', 'secret', '+15550001111');

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage("Twilio rejected SMS (HTTP 400): The 'To' number is not valid.");

        $driver->send('invalid', new SmsMessage('Hi'));
    }

    public function testSendThrowsWithRawBodyWhenErrorIsNotJson(): void
    {
        $transport = new FakeTransport(['https://api.twilio.com/*' => HttpResponse::fake('Bad Gateway', 502)]);
        $driver = new TwilioDriver(new HttpClient($transport), 'AC123', 'secret', '+15550001111');

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('Twilio rejected SMS (HTTP 502): Bad Gateway');

        $driver->send('+4366412345678', new SmsMessage('Hi'));
    }

    public function testSendThrowsWithoutSenderAndMakesNoRequest(): void
    {
        $transport = new FakeTransport();
        $driver = new TwilioDriver(new HttpClient($transport), 'AC123', 'secret');

        try {
            $driver->send('+4366412345678', new SmsMessage('Hi'));
            $this->fail('Expected SmsException.');
        } catch (SmsException $exception) {
            $this->assertStringContainsString('requires a sender', $exception->getMessage());
        }

        $this->assertSame([], $transport->getRecorded());
    }
}
