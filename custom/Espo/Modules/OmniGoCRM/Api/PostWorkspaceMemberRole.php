<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;

class PostWorkspaceMemberRole implements Action
{
    public function __construct(private WorkspaceMemberService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');

        $workspaceId = is_string($data->workspaceId ?? null) ? trim($data->workspaceId) : '';
        $membershipId = is_string($data->membershipId ?? null) ? trim($data->membershipId) : '';
        $role = is_string($data->role ?? null) ? trim($data->role) : '';

        if ($workspaceId === '' || $membershipId === '' || $role === '') {
            throw new BadRequest('workspaceId, membershipId and role are required.');
        }

        try {
            $membership = $this->service->changeRole($workspaceId, $membershipId, $role);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequest($e->getMessage());
        }

        return ResponseComposer::json([
            'accepted' => true,
            'membershipId' => $membership->getId(),
            'role' => $membership->get('role'),
        ]);
    }
}
