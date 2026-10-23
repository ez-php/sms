<?php

declare(strict_types=1);

namespace EzPhp\Sms\Driver;

use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpClientException;
use EzPhp\Sms\SmsException;
use EzPhp\Sms\SmsMessage;
use EzPhp\Sms\SmsSenderInterface;

/**
 * Class VonageDriver
 *
 * Delivers SMS via Vonage's (formerly Nexmo) SMS API
 * (`POST https://rest.nexmo.com/sms/json`), authenticated with the API key
 * and secret in the form body.
 *
 * The SMS API answers HTTP 200 even for rejected messages — the outcome is
 * the per-part `status` field in the `messages` array ("0" = accepted), with
 * the reason in `error-text`. Every part must be accepted for send() to
 * succeed.
 *
 * @package EzPhp\Sms\Driver
 */
final class VonageDriver implements SmsSenderInterface
{
    private const string ENDPOINT = 'https://rest.nexmo.com/sms/json';

    /**
     * VonageDriver Constructor
     *
     * @param HttpClient $client      HTTP client used to reach the Vonage API.
     * @param string     $apiKey      Vonage API key.
     * @param string     $apiSecret   Vonage API secret.
     * @param string     $defaultFrom Sender number or alphanumeric sender ID used when the message has none.
     */
    public function __construct(
        private readonly HttpClient $client,
        private readonly string $apiKey,
        private readonly string $apiSecret,
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
            throw new SmsException('Vonage requires a sender: set sms.from or pass one on the SmsMessage.');
        }

        try {
            $response = $this->client
                ->post(self::ENDPOINT)
                ->withForm([
                    'api_key' => $this->apiKey,
                    'api_secret' => $this->apiSecret,
                    'from' => $from,
                    'to' => ltrim($to, '+'),
                    'text' => $message->body,
                    'type' => 'unicode',
                ])
                ->send();
        } catch (HttpClientException $e) {
            throw new SmsException(sprintf('Vonage request failed: %s', $e->getMessage()), 0, $e);
        }

        if (!$response->ok()) {
            throw new SmsException(sprintf('Vonage rejected SMS (HTTP %d): %s', $response->status(), $response->body()));
        }

        /** @var mixed $decoded */
        $decoded = $response->json();

        if (!is_array($decoded) || !isset($decoded['messages']) || !is_array($decoded['messages']) || $decoded['messages'] === []) {
            throw new SmsException('Vonage returned an unexpected response: ' . $response->body());
        }

        foreach ($decoded['messages'] as $part) {
            $status = is_array($part) ? ($part['status'] ?? null) : null;

            if ($status !== '0') {
                $error = is_array($part) && isset($part['error-text']) && is_string($part['error-text'])
                    ? $part['error-text']
                    : 'unknown error';

                throw new SmsException(sprintf(
                    'Vonage rejected SMS (status %s): %s',
                    is_string($status) ? $status : '?',
                    $error,
                ));
            }
        }
    }
}
