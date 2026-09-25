<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;

class LeadSourceWebhookService
{
    private Client $http;

    public function __construct(private Config $config, private LeadCaptureGateway $gateway) {
        $this->http = new Client(['timeout' => 15, 'connect_timeout' => 8]);
    }

    public function verifyMeta(string $mode, string $token, string $challenge): string
    {
        $expected = trim((string) $this->config->get('omniGoCRMMetaLeadVerifyToken'));
        if ($expected === '' || $mode !== 'subscribe' || !hash_equals($expected, $token)) {
            throw new Forbidden('Meta lead webhook verification failed.');
        }
        return $challenge;
    }

    public function verifyMetaSignature(string $body, ?string $signature): void
    {
        $secret = trim((string) $this->config->get('omniGoCRMMetaLeadAppSecret'));
        if ($secret === '' || !is_string($signature) || !str_starts_with($signature, 'sha256=')) {
            throw new Forbidden('Invalid Meta lead webhook signature.');
        }
        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
        if (!hash_equals($expected, $signature)) {
            throw new Forbidden('Invalid Meta lead webhook signature.');
        }
    }

    public function captureMeta(stdClass $payload): array
    {
        $results = [];
        foreach (($payload->entry ?? []) as $entry) {
            if (!is_object($entry)) continue;
            foreach (($entry->changes ?? []) as $change) {
                if (!is_object($change)) continue;
                $value = $change->value ?? null;
                if (!is_object($value)) continue;
                $leadgenId = isset($value->leadgen_id) ? (string) $value->leadgen_id : '';
                $fieldData = $value->field_data ?? [];
                if ($leadgenId !== '' && (!is_array($fieldData) || count($fieldData) === 0)) {
                    $fieldData = $this->fetchMetaLeadFieldData($leadgenId);
                }
                $data = $this->mapMetaFieldData($fieldData);
                if ($leadgenId !== '') $data->externalLeadId = 'meta:' . $leadgenId;
                $data->source = 'Campaign';
                $data->leadSourceDetail = 'Meta Lead Ads';
                $results[] = $this->gateway->capture($this->metaApiKey(), $data);
            }
        }
        return $results;
    }

    public function captureGoogle(stdClass $payload): array
    {
        $items = [];
        if (isset($payload->leads) && is_array($payload->leads)) {
            $items = $payload->leads;
        } else {
            $items = [$payload];
        }
        $results = [];
        foreach ($items as $item) {
            if (!is_object($item)) continue;
            $data = isset($item->lead) && is_object($item->lead) ? clone $item->lead : clone $item;
            $external = isset($data->externalLeadId) ? (string) $data->externalLeadId : (isset($data->leadId) ? (string) $data->leadId : '');
            if ($external !== '') $data->externalLeadId = 'google:' . $external;
            $data->source = 'Campaign';
            $data->leadSourceDetail = 'Google Lead Ads';
            $results[] = $this->gateway->capture($this->googleApiKey(), $data);
        }
        return $results;
    }

    private function fetchMetaLeadFieldData(string $leadgenId): array
    {
        $token = $this->metaApiKey();
        try {
            $response = $this->http->get('https://graph.facebook.com/' . rawurlencode((string) ($this->config->get('omniGoCRMMetaGraphVersion') ?: 'v23.0')) . '/' . rawurlencode($leadgenId), [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['fields' => 'field_data'],
            ]);
            $payload = json_decode((string) $response->getBody(), true);
            return is_array($payload['field_data'] ?? null) ? $payload['field_data'] : [];
        } catch (GuzzleException $e) {
            throw new BadRequest('Meta lead details could not be fetched: ' . $e->getMessage());
        }
    }

    private function mapMetaFieldData(mixed $fields): stdClass
    {
        $out = new stdClass();
        if (!is_array($fields)) return $out;
        $aliases = [
            'first_name' => 'firstName', 'last_name' => 'lastName',
            'email' => 'emailAddress', 'phone_number' => 'phoneNumber',
            'phone' => 'phoneNumber', 'mobile' => 'phoneNumber',
            'whatsapp' => 'whatsappNumber', 'company' => 'accountName',
            'campaign' => 'campaignName', 'name' => 'firstName',
        ];
        foreach ($fields as $field) {
            if (!is_object($field)) continue;
            $name = strtolower(trim((string) ($field->name ?? '')));
            $value = $field->values[0] ?? null;
            if ($name === '' || !is_string($value)) continue;
            $key = $aliases[$name] ?? null;
            if ($key) $out->{$key} = trim($value);
        }
        return $out;
    }

    private function metaApiKey(): string
    {
        $key = trim((string) $this->config->get('omniGoCRMMetaLeadApiKey'));
        if ($key === '') throw new BadRequest('Meta lead API key is not configured.');
        return $key;
    }

    private function googleApiKey(): string
    {
        $key = trim((string) $this->config->get('omniGoCRMGoogleLeadApiKey'));
        if ($key === '') throw new BadRequest('Google lead API key is not configured.');
        return $key;
    }
}
