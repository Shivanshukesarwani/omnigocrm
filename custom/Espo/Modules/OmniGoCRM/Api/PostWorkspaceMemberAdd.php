<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;

class PostWorkspaceMemberAdd implements Action
{
    public function __construct(private WorkspaceMemberService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');

        $workspaceId = is_string($data->workspaceId ?? null) ? trim($data->workspaceId) : '';
        $userId = is_string($data->userId ?? null) ? trim($data->userId) : '';
        $role = is_string($data->role ?? null) ? trim($data->role) : 'Agent';

        if ($workspaceId === '' || $userId === '') {
            throw new BadRequest('workspaceId and userId are required.');
        }

        try {
            $membership = $this->service->addMember($workspaceId, $userId, $role);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequest($e->getMessage());
        }

        return ResponseComposer::json([
            'accepted' => true,
            'membershipId' => $membership->getId(),
            'workspaceId' => $workspaceId,
            'userId' => $membership->get('userId'),
            'role' => $membership->get('role'),
            'status' => $membership->get('status'),
        ]);
    }
}
