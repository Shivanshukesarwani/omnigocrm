<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Modules\OmniGoCRM\Services\LeadSourceWebhookService;
class PostGoogleLeadWebhook implements Action {
    public function __construct(private LeadSourceWebhookService $service, private Config $config) {}
    public function process(Request $request): Response {
        $expected = trim((string) $this->config->get('omniGoCRMGoogleLeadApiKey'));
        $provided = trim((string) ($request->getHeader('X-OmniGoCRM-Google-Key') ?? ''));
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) throw new Forbidden('Invalid Google lead webhook key.');
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');
        return ResponseComposer::json(['accepted'=>true,'results'=>$this->service->captureGoogle($data)]);
    }
}
