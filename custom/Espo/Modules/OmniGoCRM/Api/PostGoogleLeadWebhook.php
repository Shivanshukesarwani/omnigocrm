<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\LeadSourceWebhookService;
class PostGoogleLeadWebhook implements Action {
    public function __construct(private LeadSourceWebhookService $service) {}
    public function process(Request $request): Response {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');
        return ResponseComposer::json(['accepted'=>true,'results'=>$this->service->captureGoogle($data)]);
    }
}
