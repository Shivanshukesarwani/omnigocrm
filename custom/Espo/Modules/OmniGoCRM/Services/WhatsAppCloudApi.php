<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;

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

    /** @return array{body: \Psr\Http\Message\StreamInterface, mimeType: string, size: int} */
    public function downloadMedia(string $mediaId): array
    {
        $accessToken = trim((string) $this->config->get('omniGoCRMWhatsAppAccessToken'));
        $phoneNumberId = trim((string) $this->config->get('omniGoCRMWhatsAppPhoneNumberId'));
        $graphVersion = trim((string) $this->config->get('omniGoCRMWhatsAppGraphVersion'));

        if ($accessToken === '' || $phoneNumberId === '' || $graphVersion === '') {
            throw new Error('WhatsApp Cloud API is not fully configured.');
        }
        if (!preg_match('/^[A-Za-z0-9_-]{1,255}$/', $mediaId)) {
            throw new Error('Invalid WhatsApp media ID.');
        }

        $headers = ['Authorization' => 'Bearer ' . $accessToken];
        $metadataUrl = sprintf(
            'https://graph.facebook.com/%s/%s',
            rawurlencode($graphVersion),
            rawurlencode($mediaId),
        );

        try {
            $metadataResponse = $this->httpClient->get($metadataUrl, [
                'headers' => $headers,
                'query' => ['phone_number_id' => $phoneNumberId],
                'allow_redirects' => false,
            ]);
            $metadata = json_decode((string) $metadataResponse->getBody(), true);
        } catch (GuzzleException $e) {
            throw new Error('WhatsApp media metadata request failed.');
        }

        $url = is_array($metadata) ? ($metadata['url'] ?? null) : null;
        $parts = is_string($url) ? parse_url($url) : false;
        if (
            !is_array($parts) ||
            strtolower((string) ($parts['scheme'] ?? '')) !== 'https' ||
            strtolower((string) ($parts['host'] ?? '')) !== 'lookaside.fbsbx.com' ||
            isset($parts['port']) || isset($parts['user']) || isset($parts['pass'])
        ) {
            throw new Error('WhatsApp returned an unsupported media URL.');
        }

        $maximumSize = 100 * 1024 * 1024;
        $expectedSize = filter_var($metadata['file_size'] ?? null, FILTER_VALIDATE_INT);
        if ($expectedSize !== false && $expectedSize !== null && ($expectedSize < 0 || $expectedSize > $maximumSize)) {
            throw new Error('WhatsApp media exceeds the 100 MB download limit.');
        }

        try {
            $response = $this->httpClient->get($url, [
                'headers' => $headers,
                'stream' => true,
                'allow_redirects' => false,
                'timeout' => 60,
            ]);
        } catch (GuzzleException $e) {
            throw new Error('WhatsApp media download failed.');
        }

        $streamResource = fopen('php://temp/maxmemory:2097152', 'w+b');
        if ($streamResource === false) throw new Error('Could not prepare the media response.');

        $target = Utils::streamFor($streamResource);
        $source = $response->getBody();
        $size = 0;
        $hash = hash_init('sha256');

        try {
            while (!$source->eof()) {
                $chunk = $source->read(8192);
                if ($chunk === '') break;
                $size += strlen($chunk);
                if ($size > $maximumSize) throw new Error('WhatsApp media exceeds the 100 MB download limit.');
                hash_update($hash, $chunk);
                $target->write($chunk);
            }
            $target->rewind();
        } catch (\Throwable $e) {
            $target->close();
            throw $e;
        }

        if ($expectedSize !== false && $expectedSize !== null && $expectedSize > 0 && $size !== $expectedSize) {
            $target->close();
            throw new Error('WhatsApp media size check failed.');
        }

        $actualHash = hash_final($hash);
        $expectedHash = $metadata['sha256'] ?? null;
        if (is_string($expectedHash) && preg_match('/^[a-f0-9]{64}$/i', $expectedHash)) {
            if (!hash_equals(strtolower($expectedHash), $actualHash)) {
                $target->close();
                throw new Error('WhatsApp media integrity check failed.');
            }
        }

        $mimeType = strtolower(trim((string) ($metadata['mime_type'] ?? 'application/octet-stream')));
        if (!preg_match('~^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$~', $mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        return ['body' => $target, 'mimeType' => $mimeType, 'size' => $size];
    }
}

final class WhatsAppSendResult
{
    public function __construct(
        public readonly string $providerMessageId,
        public readonly array $response,
    ) {}
}
