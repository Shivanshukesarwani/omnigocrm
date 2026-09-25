<?php
namespace Espo\Modules\OmniGoCRM\Api;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;

class GetWorkspaceMembers implements Action
{
    public function __construct(private WorkspaceMemberService $service) {}

    public function process(Request $request): Response
    {
        $workspaceId = $request->getQueryParam('workspaceId') ?? '';
        if (!is_string($workspaceId) || trim($workspaceId) === '') {
            throw new BadRequest('workspaceId is required.');
        }

        $list = [];
        foreach ($this->service->list(trim($workspaceId)) as $membership) {
            $list[] = [
                'id' => $membership->getId(),
                'userId' => $membership->get('userId'),
                'role' => $membership->get('role'),
                'status' => $membership->get('status'),
                'joinedAt' => $membership->get('joinedAt'),
            ];
        }

        return ResponseComposer::json(['list' => $list]);
    }
}
