<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Modules\OmniGoCRM\Services\LeadSourceWebhookService;
class GetMetaLeadWebhook implements Action {
    public function __construct(private LeadSourceWebhookService $service) {}
    public function process(Request $request): Response {
        return new \Espo\Core\Api\Response($this->service->verifyMeta(
            (string) ($request->getQueryParams()['hub_mode'] ?? ''),
            (string) ($request->getQueryParams()['hub_verify_token'] ?? ''),
            (string) ($request->getQueryParams()['hub_challenge'] ?? '')
        ));
    }
}
