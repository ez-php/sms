# ez-php/sms

SMS delivery for ez-php — Twilio and Vonage drivers over `ez-php/http-client`, plus Log, Null and Array drivers for development and tests, behind one `SmsSenderInterface` and an `Sms` static façade.

## Installation

```bash
composer require ez-php/sms
```

Register the providers (`provider/modules.php`) — `HttpClientServiceProvider` first, the Twilio/Vonage drivers resolve `HttpClient` from the container:

```php
\EzPhp\HttpClient\HttpClientServiceProvider::class,
\EzPhp\Sms\SmsServiceProvider::class,
```

## Configuration

`config/sms.php`:

```php
return [
    'driver' => getenv('SMS_DRIVER') ?: 'null',   // null | array | log | twilio | vonage
    'from' => getenv('SMS_FROM') ?: '',            // default sender number / alphanumeric ID
    'log_path' => getenv('SMS_LOG_PATH') ?: '',    // log driver file ('' = error_log())
    'twilio' => [
        'sid' => getenv('SMS_TWILIO_SID') ?: '',
        'token' => getenv('SMS_TWILIO_TOKEN') ?: '',
    ],
    'vonage' => [
        'key' => getenv('SMS_VONAGE_KEY') ?: '',
        'secret' => getenv('SMS_VONAGE_SECRET') ?: '',
    ],
];
```

## Usage

```php
use EzPhp\Sms\Sms;
use EzPhp\Sms\SmsMessage;

Sms::send('+4366412345678', 'Your code is 123456');                   // default sender
Sms::send('+4366412345678', new SmsMessage('Order shipped', 'MyShop')); // sender override
```

Or inject `EzPhp\Sms\SmsSenderInterface` directly:

```php
public function __construct(private readonly SmsSenderInterface $sms) {}

$this->sms->send($phone, new SmsMessage('Hello'));
```

Every driver throws `EzPhp\Sms\SmsException` when delivery fails — including Vonage, whose API answers HTTP 200 for rejected messages; the driver checks each part's `status`.

Phone numbers are passed to the provider as-is; use E.164 (`+<country><number>`).

## Drivers

| Driver | Purpose |
|---|---|
| `twilio` | Twilio Programmable Messaging REST API |
| `vonage` | Vonage (Nexmo) SMS API, always `type=unicode` |
| `log` | Appends one line per message (incl. body) to `log_path` — local development |
| `array` | Records messages in memory — `ArrayDriver::sentTo($number)` for test assertions |
| `null` | Discards everything (default) |

## Testing

```php
$driver = new \EzPhp\Sms\Driver\ArrayDriver();
Sms::setSender($driver);

// ... code under test ...

$this->assertCount(1, $driver->sentTo('+4366412345678'));
Sms::resetSender();
```

## Not included

No phone-number parsing, inbound SMS / delivery receipts, or queued delivery of its own. For notifications (including queued delivery), use `ez-php/notification`'s `sms` channel — it sends through the `SmsSenderInterface` bound here.

## License

MIT
