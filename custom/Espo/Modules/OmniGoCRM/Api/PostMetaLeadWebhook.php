<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\LeadSourceWebhookService;
class PostMetaLeadWebhook implements Action {
    public function __construct(private LeadSourceWebhookService $service) {}
    public function process(Request $request): Response {
        $raw = (string) $request->getBodyContents();
        $this->service->verifyMetaSignature($raw, $request->getHeader('X-Hub-Signature-256'));
        $data = $request->getParsedBody();
        return ResponseComposer::json(['accepted'=>true,'results'=>$this->service->captureMeta($data)]);
    }
}
