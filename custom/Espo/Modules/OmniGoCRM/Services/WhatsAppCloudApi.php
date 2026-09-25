<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WhatsAppCloudApi
{
    private Client $httpClient;

    public function __construct(
        private Config $config,
    ) {
        $this->httpClient = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
        ]);
    }


    public function sendTemplate(
        string $recipient,
        string $templateName,
        string $languageCode,
        array $components = [],
    ): WhatsAppSendResult {
        $accessToken = trim((string) $this->config->get('omniGoCRMWhatsAppAccessToken'));
        $phoneNumberId = trim((string) $this->config->get('omniGoCRMWhatsAppPhoneNumberId'));
        $graphVersion = trim((string) $this->config->get('omniGoCRMWhatsAppGraphVersion'));

        if ($accessToken === '' || $phoneNumberId === '' || $graphVersion === '') {
            throw new Error('WhatsApp Cloud API is not fully configured.');
        }

        $recipient = preg_replace('/\D+/', '', $recipient) ?? '';

        if (strlen($recipient) < 8 || strlen($recipient) > 15) {
            throw new Error('Lead WhatsApp number must be an international phone number.');
        }

        $templateName = trim($templateName);
        $languageCode = trim($languageCode);

        if (!preg_match('/^[a-zA-Z0-9_.-]{1,255}$/', $templateName)) {
            throw new Error('Invalid WhatsApp template name.');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{2,20}$/', $languageCode)) {
            throw new Error('Invalid WhatsApp template language code.');
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            rawurlencode($graphVersion),
            rawurlencode($phoneNumberId),
        );

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipient,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [
                            'code' => $languageCode,
                        ],
                        'components' => $components,
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new Error('WhatsApp Cloud API request failed: ' . $e->getMessage());
        }

        $payload = json_decode((string) $response->getBody(), true);

        if (!is_array($payload)) {
            throw new Error('WhatsApp Cloud API returned invalid JSON.');
        }

        $providerMessageId = $payload['messages'][0]['id'] ?? null;

        if (!is_string($providerMessageId) || $providerMessageId === '') {
            $message = $payload['error']['message'] ?? 'WhatsApp Cloud API did not return a message ID.';
            throw new Error((string) $message);
        }

        return new WhatsAppSendResult($providerMessageId, $payload);
    }

    public function sendText(string $recipient, string $body): WhatsAppSendResult
    {
        $accessToken = trim((string) $this->config->get('omniGoCRMWhatsAppAccessToken'));
        $phoneNumberId = trim((string) $this->config->get('omniGoCRMWhatsAppPhoneNumberId'));
        $graphVersion = trim((string) $this->config->get('omniGoCRMWhatsAppGraphVersion'));

        if ($accessToken === '') {
            throw new Error('WhatsApp Cloud API access token is not configured.');
        }

        if ($phoneNumberId === '') {
            throw new Error('WhatsApp Cloud API phone number ID is not configured.');
        }

        if ($graphVersion === '') {
            throw new Error('WhatsApp Graph API version is not configured.');
        }

        $recipient = preg_replace('/\D+/', '', $recipient) ?? '';

        if (strlen($recipient) < 8 || strlen($recipient) > 15) {
            throw new Error('Lead WhatsApp number must be an international phone number.');
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            rawurlencode($graphVersion),
            rawurlencode($phoneNumberId),
        );

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $body,
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new Error('WhatsApp Cloud API request failed: ' . $e->getMessage());
        }

        $payload = json_decode((string) $response->getBody(), true);

        if (!is_array($payload)) {
            throw new Error('WhatsApp Cloud API returned invalid JSON.');
        }

        $providerMessageId = $payload['messages'][0]['id'] ?? null;

        if (!is_string($providerMessageId) || $providerMessageId === '') {
            $message = $payload['error']['message'] ?? 'WhatsApp Cloud API did not return a message ID.';
            throw new Error((string) $message);
        }

        return new WhatsAppSendResult($providerMessageId, $payload);
    }
}

final class WhatsAppSendResult
{
    public function __construct(
        public readonly string $providerMessageId,
        public readonly array $response,
    ) {}
}
