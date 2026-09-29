<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Modules\OmniGoCRM\Services\LeadCaptureGateway;

class PostLeadCapture implements Action
{
    public function __construct(
        private LeadCaptureGateway $gateway,
        private Config $config,
    ) {}

    private const MAX_BODY_BYTES = 32768;

    public function process(Request $request): Response
    {
        $body = $request->getBodyContents();
        if ($body !== null && strlen($body) > self::MAX_BODY_BYTES) {
            throw new BadRequest('Lead capture request is too large.');
        }

        $this->assertOriginAllowed($request->getHeader('Origin'));

        $apiKey = trim($request->getHeader('X-OmniGoCRM-Form-Key') ?? '');

        if ($apiKey === '') {
            throw new BadRequest('X-OmniGoCRM-Form-Key header is required.');
        }

        $data = $request->getParsedBody();
        return ResponseComposer::json(
            $this->gateway->capture($apiKey, $data)
        );
    }

    private function assertOriginAllowed(?string $origin): void
    {
        if ($origin === null || trim($origin) === '') return;

        $allowed = preg_split('/[\\r\\n,]+/', (string) $this->config->get('omniGoCRMLeadCaptureAllowedOrigins')) ?: [];
        $origin = $this->normalizeOrigin($origin);
        if ($origin === null) {
            throw new Forbidden('This origin is not allowed to submit leads.');
        }

        foreach ($allowed as $candidate) {
            if ($this->normalizeOrigin(trim($candidate)) === $origin) return;
        }

        throw new Forbidden('This origin is not allowed to submit leads.');
    }

    private function normalizeOrigin(string $origin): ?string
    {
        $parts = parse_url($origin);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) return null;

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) return null;
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) return null;
        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') return null;

        $host = strtolower(rtrim($parts['host'], '.'));
        if ($host === '') return null;

        $port = $parts['port'] ?? null;
        if (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)) {
            $port = null;
        }

        return $scheme . '://' . $host . ($port === null ? '' : ':' . $port);
    }
}
