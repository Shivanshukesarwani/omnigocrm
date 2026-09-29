<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class FCMPushService
{
    private Client $http;
    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private Log $log,
    ) {
        $this->http = new Client(['timeout' => 10, 'connect_timeout' => 4, 'http_errors' => false]);
    }

    public function deliver(string $notificationId): void
    {
        $credentialsJson = trim((string) $this->config->get('omniGoCRMFCMServiceAccountJson'));
        if ($credentialsJson === '') return;

        $notification = $this->entityManager->getEntityById('Notification', $notificationId);
        if (!$notification || $notification->get('deleted')) return;

        $userId = trim((string) $notification->get('userId'));
        if ($userId === '') return;

        $credentials = json_decode($credentialsJson, true);
        $projectId = is_array($credentials) ? trim((string) ($credentials['project_id'] ?? '')) : '';
        if ($projectId === '') {
            $this->log->error('FCM push is configured without a Firebase project_id.');
            return;
        }

        $accessToken = $this->getAccessToken($credentials);
        if ($accessToken === null) return;

        $devices = $this->entityManager->getRDBRepository('OmniGoCRMDevice')->where([
            'userId' => $userId,
            'pushProvider' => 'FCM',
            'active' => true,
            'deleted' => false,
        ])->find();

        foreach ($devices as $device) {
            $token = trim((string) $device->get('pushToken'));
            if ($token === '') continue;

            try {
                $response = $this->http->post(
                    'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send',
                    [
                        'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                        'json' => [
                            'message' => [
                                'token' => $token,
                                'notification' => [
                                    'title' => 'OmniGoCRM',
                                    'body' => 'You have a new CRM notification.',
                                ],
                                'data' => ['notificationId' => $notificationId],
                                'android' => ['priority' => 'HIGH'],
                            ],
                        ],
                    ],
                );
            } catch (GuzzleException) {
                $this->log->warning('FCM push delivery failed due to a network error.');
                continue;
            }

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) continue;

            $responseBody = (string) $response->getBody();
            if ($response->getStatusCode() === 404 || str_contains($responseBody, 'UNREGISTERED')) {
                $device->setMultiple(['active' => false, 'pushToken' => null]);
                $this->entityManager->saveEntity($device, [SaveOption::SILENT => true]);
            }

            $this->log->warning('FCM rejected a push notification with HTTP ' . $response->getStatusCode() . '.');
        }
    }

    private function getAccessToken(mixed $credentials): ?string
    {
        if ($this->accessToken && $this->accessTokenExpiresAt > time() + 60) return $this->accessToken;
        if (!is_array($credentials)) {
            $this->log->error('FCM service-account configuration is not valid JSON.');
            return null;
        }

        $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
        $privateKey = trim((string) ($credentials['private_key'] ?? ''));
        if ($clientEmail === '' || $privateKey === '') {
            $this->log->error('FCM service-account configuration is missing client_email or private_key.');
            return null;
        }

        $issuedAt = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64Url(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header . '.' . $claims;

        $key = openssl_pkey_get_private($privateKey);
        if (!$key || !openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            $this->log->error('FCM service-account private key could not sign an OAuth assertion.');
            return null;
        }

        try {
            $response = $this->http->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $unsigned . '.' . $this->base64Url($signature),
                ],
            ]);
        } catch (GuzzleException) {
            $this->log->warning('FCM OAuth token request failed due to a network error.');
            return null;
        }

        $result = json_decode((string) $response->getBody(), true);
        $token = is_array($result) ? trim((string) ($result['access_token'] ?? '')) : '';
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || $token === '') {
            $this->log->error('FCM OAuth token request was rejected.');
            return null;
        }

        $this->accessToken = $token;
        $this->accessTokenExpiresAt = time() + max(60, (int) ($result['expires_in'] ?? 3600));
        return $this->accessToken;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
