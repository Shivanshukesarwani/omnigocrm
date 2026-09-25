<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;

class DashboardService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function summary(): array
    {
        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($workspaceId === '') {
            throw new BadRequest('Select an active OmniGoCRM workspace.');
        }

        $counts = [];

        foreach ([
            'Lead' => 'leads',
            'Contact' => 'contacts',
            'Account' => 'accounts',
            'Opportunity' => 'opportunities',
            'Task' => 'tasks',
            'Quote' => 'quotes',
            'Order' => 'orders',
            'Payment' => 'payments',
            'WhatsAppConversation' => 'whatsappConversations',
        ] as $entityType => $key) {
            $counts[$key] = $this->entityManager
                ->getRDBRepository($entityType)
                ->where([
                    'omniGoCRMWorkspaceId' => $workspaceId,
                    'deleted' => false,
                ])
                ->count();
        }

        $counts['openTasks'] = $this->entityManager
            ->getRDBRepository('Task')
            ->where([
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status!=' => 'Completed',
                'deleted' => false,
            ])
            ->count();

        $counts['unreadWhatsApp'] = $this->entityManager
            ->getRDBRepository('WhatsAppConversation')
            ->where([
                'omniGoCRMWorkspaceId' => $workspaceId,
                'unreadCount>' => 0,
                'deleted' => false,
            ])
            ->count();

        $counts['pendingPayments'] = $this->entityManager
            ->getRDBRepository('Payment')
            ->where([
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status' => ['Pending', 'Authorized', 'Partially Paid'],
                'deleted' => false,
            ])
            ->count();

        $sales = [
            'quoteValue' => $this->sum($workspaceId, 'Quote', 'totalAmount'),
            'orderValue' => $this->sum($workspaceId, 'Order', 'totalAmount'),
            'paidValue' => $this->sumByStatuses($workspaceId, 'Payment', 'amount', ['Paid']),
            'pendingValue' => $this->sumByStatuses($workspaceId, 'Payment', 'amount', ['Pending', 'Authorized', 'Partially Paid']),
            'pipelineByStage' => $this->groupCount($workspaceId, 'Opportunity', 'stage'),
            'leadsByStage' => $this->groupCount($workspaceId, 'Lead', 'leadStage'),
        ];

        return [
            'workspaceId' => $workspaceId,
            'counts' => $counts,
            'sales' => $sales,
            'generatedAt' => gmdate('Y-m-d H:i:s'),
        ];
    }

    private function sum(string $workspaceId, string $entityType, string $field): float
    {
        $sum = 0.0;
        foreach ($this->entityManager->getRDBRepository($entityType)->where(['omniGoCRMWorkspaceId' => $workspaceId, 'deleted' => false])->find() as $record) {
            $sum += (float) ($record->get($field) ?? 0);
        }
        return $sum;
    }

    private function sumByStatuses(string $workspaceId, string $entityType, string $field, array $statuses): float
    {
        $sum = 0.0;
        foreach ($this->entityManager->getRDBRepository($entityType)->where(['omniGoCRMWorkspaceId' => $workspaceId, 'status' => $statuses, 'deleted' => false])->find() as $record) {
            $sum += (float) ($record->get($field) ?? 0);
        }
        return $sum;
    }

    private function groupCount(string $workspaceId, string $entityType, string $field): array
    {
        $out = [];
        foreach ($this->entityManager->getRDBRepository($entityType)->where(['omniGoCRMWorkspaceId' => $workspaceId, 'deleted' => false])->find() as $record) {
            $value = (string) ($record->get($field) ?: 'Unknown');
            $out[$value] = ($out[$value] ?? 0) + 1;
        }
        ksort($out);
        return $out;
    }
    }
}
