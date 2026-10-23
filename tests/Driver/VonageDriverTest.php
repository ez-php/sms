<?php

declare(strict_types=1);

namespace Tests\Sms\Driver;

use EzPhp\HttpClient\FakeTransport;
use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpClientException;
use EzPhp\HttpClient\HttpResponse;
use EzPhp\Sms\Driver\VonageDriver;
use EzPhp\Sms\SmsException;
use EzPhp\Sms\SmsMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class VonageDriverTest
 *
 * @package Tests\Sms\Driver
 */
#[CoversClass(VonageDriver::class)]
#[UsesClass(SmsMessage::class)]
#[UsesClass(SmsException::class)]
final class VonageDriverTest extends TestCase
{
    public function testSendPostsCredentialsAndMessageAsForm(): void
    {
        $transport = new FakeTransport([
            'https://rest.nexmo.com/*' => HttpResponse::fake(['message-count' => '1', 'messages' => [['status' => '0', 'message-id' => 'abc']]], 200),
        ]);
        $driver = new VonageDriver(new HttpClient($transport), 'key', 'secret', 'EzPhp');

        $driver->send('+4366412345678', new SmsMessage('Your code is 123456'));

        $recorded = $transport->getRecorded();
        $this->assertCount(1, $recorded);
        $this->assertSame('https://rest.nexmo.com/sms/json', $recorded[0]['url']);

        parse_str($recorded[0]['body'], $body);
        $this->assertSame('key', $body['api_key']);
        $this->assertSame('secret', $body['api_secret']);
        $this->assertSame('EzPhp', $body['from']);
        $this->assertSame('4366412345678', $body['to']);
        $this->assertSame('Your code is 123456', $body['text']);
        $this->assertSame('unicode', $body['type']);
    }

    public function testSendThrowsWhenAnyPartIsRejected(): void
    {
        $transport = new FakeTransport([
            'https://rest.nexmo.com/*' => HttpResponse::fake([
                'message-count' => '2',
                'messages' => [
                    ['status' => '0'],
                    ['status' => '9', 'error-text' => 'Partner quota exceeded'],
                ],
            ], 200),
        ]);
        $driver = new VonageDriver(new HttpClient($transport), 'key', 'secret', 'EzPhp');

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('Vonage rejected SMS (status 9): Partner quota exceeded');

        $driver->send('+4366412345678', new SmsMessage('Hi'));
    }

    public function testSendThrowsOnHttpError(): void
    {
        $transport = new FakeTransport(['https://rest.nexmo.com/*' => HttpResponse::fake('Service Unavailable', 503)]);
        $driver = new VonageDriver(new HttpClient($transport), 'key', 'secret', 'EzPhp');

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('Vonage rejected SMS (HTTP 503): Service Unavailable');

        $driver->send('+4366412345678', new SmsMessage('Hi'));
    }

    public function testSendThrowsOnUnexpectedResponse(): void
    {
        $transport = new FakeTransport(['https://rest.nexmo.com/*' => HttpResponse::fake(['messages' => []], 200)]);
        $driver = new VonageDriver(new HttpClient($transport), 'key', 'secret', 'EzPhp');

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('Vonage returned an unexpected response');

        $driver->send('+4366412345678', new SmsMessage('Hi'));
    }

    public function testSendThrowsWithoutSender(): void
    {
        $driver = new VonageDriver(new HttpClient(new FakeTransport()), 'key', 'secret');

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('Vonage requires a sender');

        $driver->send('+4366412345678', new SmsMessage('Hi'));
    }

    public function testTransportFailureIsWrappedInSmsException(): void
    {
        $failure = new HttpClientException('Connection timed out');
        $transport = new FakeTransport(['https://rest.nexmo.com/*' => $failure]);
        $driver = new VonageDriver(new HttpClient($transport), 'key', 'secret', 'EzPhp');

        try {
            $driver->send('+4366412345678', new SmsMessage('Hi'));
            $this->fail('Expected SmsException.');
        } catch (SmsException $e) {
            $this->assertSame('Vonage request failed: Connection timed out', $e->getMessage());
            $this->assertSame($failure, $e->getPrevious());
        }
    }
}
