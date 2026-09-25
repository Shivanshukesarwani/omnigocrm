<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;

class PostWorkspaceMemberStatus implements Action
{
    public function __construct(private WorkspaceMemberService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');

        $workspaceId = is_string($data->workspaceId ?? null) ? trim($data->workspaceId) : '';
        $membershipId = is_string($data->membershipId ?? null) ? trim($data->membershipId) : '';
        $status = is_string($data->status ?? null) ? trim($data->status) : '';

        if ($workspaceId === '' || $membershipId === '' || $status === '') {
            throw new BadRequest('workspaceId, membershipId and status are required.');
        }

        try {
            $membership = $this->service->setStatus($workspaceId, $membershipId, $status);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequest($e->getMessage());
        }

        return ResponseComposer::json([
            'accepted' => true,
            'membershipId' => $membership->getId(),
            'status' => $membership->get('status'),
        ]);
    }
}
