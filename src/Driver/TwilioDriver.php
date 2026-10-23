<?php

declare(strict_types=1);

namespace EzPhp\Sms\Driver;

use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpClientException;
use EzPhp\Sms\SmsException;
use EzPhp\Sms\SmsMessage;
use EzPhp\Sms\SmsSenderInterface;

/**
 * Class TwilioDriver
 *
 * Delivers SMS via Twilio's Programmable Messaging REST API
 * (`POST /2010-04-01/Accounts/{AccountSid}/Messages.json`), authenticated
 * with HTTP Basic auth (Account SID + Auth Token).
 *
 * Twilio answers a rejected message with a non-2xx status and a JSON body
 * carrying `code`/`message`; both are surfaced in the thrown SmsException.
 *
 * @package EzPhp\Sms\Driver
 */
final class TwilioDriver implements SmsSenderInterface
{
    private const string API_BASE = 'https://api.twilio.com/2010-04-01';

    /**
     * TwilioDriver Constructor
     *
     * @param HttpClient $client      HTTP client used to reach the Twilio API.
     * @param string     $accountSid  Twilio Account SID (also the Basic-auth username).
     * @param string     $authToken   Twilio Auth Token (the Basic-auth password).
     * @param string     $defaultFrom Sender number used when the message has none.
     */
    public function __construct(
        private readonly HttpClient $client,
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $defaultFrom = '',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function send(string $to, SmsMessage $message): void
    {
        $from = $message->from ?? $this->defaultFrom;

        if ($from === '') {
            throw new SmsException('Twilio requires a sender: set sms.from or pass one on the SmsMessage.');
        }

        try {
            $response = $this->client
                ->post(sprintf('%s/Accounts/%s/Messages.json', self::API_BASE, rawurlencode($this->accountSid)))
                ->withHeaders(['authorization' => 'Basic ' . base64_encode($this->accountSid . ':' . $this->authToken)])
                ->withForm([
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message->body,
                ])
                ->send();
        } catch (HttpClientException $e) {
            throw new SmsException(sprintf('Twilio request failed: %s', $e->getMessage()), 0, $e);
        }

        if (!$response->ok()) {
            /** @var mixed $decoded */
            $decoded = $response->json();
            $detail = is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
                ? $decoded['message']
                : $response->body();

            throw new SmsException(sprintf('Twilio rejected SMS (HTTP %d): %s', $response->status(), $detail));
        }
    }
}
