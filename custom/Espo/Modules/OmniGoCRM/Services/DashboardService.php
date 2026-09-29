<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;

class DashboardService
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceMemberService $memberService,
        private WorkspaceService $workspaceService,
    ) {}

    public function summary(): array
    {
        $workspaceId = $this->workspaceService->currentId() ?? '';

        if ($workspaceId === '') {
            throw new BadRequest('A workspace could not be initialized for your account. Reload OmniGoCRM and try again.');
        }
        $this->memberService->activeMembership($workspaceId);

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

        $inbox = $this->inboxMetrics($workspaceId);

        return [
            'workspaceId' => $workspaceId,
            'counts' => $counts,
            'sales' => $sales,
            'inbox' => $inbox,
            'generatedAt' => gmdate('Y-m-d H:i:s'),
        ];
    }

    private function inboxMetrics(string $workspaceId): array
    {
        $now = time();
        $windowStart = gmdate('Y-m-d H:i:s', $now - 30 * 86400);
        $openConversations = $this->entityManager->getRDBRepository('WhatsAppConversation')->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
            'status' => 'Open',
            'deleted' => false,
        ])->find();

        $openCount = 0;
        $unassignedCount = 0;
        $waitingCount = 0;
        $waitingOver24Hours = 0;
        $byAssignee = [];
        $members = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
            'workspaceId' => $workspaceId,
            'status' => 'Active',
            'deleted' => false,
        ])->find();
        foreach ($members as $membership) {
            $userId = (string) $membership->get('userId');
            if ($userId === '') continue;
            $user = $this->entityManager->getEntityById('User', $userId);
            $byAssignee[$userId] = [
                'userId' => $userId,
                'name' => $user ? (string) $user->get('name') : (string) $membership->get('name'),
                'openConversations' => 0,
                'waitingForResponse' => 0,
                'waitingOver24Hours' => 0,
                'latestReplySeconds30dTotal' => 0,
                'latestReplySamples30d' => 0,
            ];
        }

        foreach ($openConversations as $conversation) {
            $openCount++;
            $assigneeId = (string) $conversation->get('assignedUserId');
            if (isset($byAssignee[$assigneeId])) $byAssignee[$assigneeId]['openConversations']++;
            if (!$conversation->get('assignedUserId') && !$conversation->get('assignedTeamId')) {
                $unassignedCount++;
            }

            $inboundAt = $this->timestamp($conversation->get('lastTrackedInboundAt'));
            $lastResponseAt = $this->timestamp($conversation->get('lastResponseAt'));
            if ($inboundAt === null || ($lastResponseAt !== null && $lastResponseAt >= $inboundAt)) continue;

            $waitingCount++;
            if (isset($byAssignee[$assigneeId])) $byAssignee[$assigneeId]['waitingForResponse']++;
            if ($now - $inboundAt >= 86400) $waitingOver24Hours++;
            if ($now - $inboundAt >= 86400 && isset($byAssignee[$assigneeId])) {
                $byAssignee[$assigneeId]['waitingOver24Hours']++;
            }
        }

        $inboundMessages = $this->entityManager->getRDBRepository('WhatsAppMessage')->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
            'direction' => 'Inbound',
            'receivedAt>=' => $windowStart,
            'deleted' => false,
        ])->count();
        $outboundMessages = $this->entityManager->getRDBRepository('WhatsAppMessage')->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
            'direction' => 'Outbound',
            'sentAt>=' => $windowStart,
            'deleted' => false,
        ])->count();

        $replySeconds = 0;
        $replySamples = 0;
        $recentReplies = $this->entityManager->getRDBRepository('WhatsAppConversation')->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
            'lastResponseAt>=' => $windowStart,
            'deleted' => false,
        ])->find();
        foreach ($recentReplies as $conversation) {
            $seconds = $conversation->get('lastResponseSeconds');
            if ($seconds === null || (int) $seconds < 0) continue;
            $replySeconds += (int) $seconds;
            $replySamples++;

            $assigneeId = (string) $conversation->get('assignedUserId');
            if (!isset($byAssignee[$assigneeId])) continue;
            $byAssignee[$assigneeId]['latestReplySeconds30dTotal'] += (int) $seconds;
            $byAssignee[$assigneeId]['latestReplySamples30d']++;
        }

        foreach ($byAssignee as &$assignee) {
            $assignee['averageLatestReplySeconds30d'] = $assignee['latestReplySamples30d'] > 0
                ? (int) round($assignee['latestReplySeconds30dTotal'] / $assignee['latestReplySamples30d'])
                : null;
            unset($assignee['latestReplySeconds30dTotal']);
        }
        unset($assignee);
        usort($byAssignee, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return [
            'windowDays' => 30,
            'openConversations' => $openCount,
            'unassignedOpenConversations' => $unassignedCount,
            'waitingForResponse' => $waitingCount,
            'waitingOver24Hours' => $waitingOver24Hours,
            'inboundMessages30d' => $inboundMessages,
            'outboundMessages30d' => $outboundMessages,
            'messageTrend30d' => [
                'inbound' => $this->dailyMessageCounts($workspaceId, 'Inbound', 'receivedAt', $windowStart),
                'outbound' => $this->dailyMessageCounts($workspaceId, 'Outbound', 'sentAt', $windowStart),
            ],
            'averageLatestReplySeconds30d' => $replySamples > 0 ? (int) round($replySeconds / $replySamples) : null,
            'replySamples30d' => $replySamples,
            'byAssignee' => $byAssignee,
        ];
    }

    private function timestamp(mixed $value): ?int
    {
        if (!is_string($value) || trim($value) === '') return null;

        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dailyMessageCounts(string $workspaceId, string $direction, string $dateField, string $windowStart): array
    {
        $dayExpression = Expression::date(Expression::column($dateField));
        $rows = $this->entityManager->getRDBRepository('WhatsAppMessage')->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
            'direction' => $direction,
            $dateField . '>=' => $windowStart,
            'deleted' => false,
        ])->select($dayExpression, 'day')->select('COUNT:id', 'messageCount')->group($dayExpression)->find();

        $counts = [];
        foreach ($rows as $row) {
            $day = (string) $row->get('day');
            if ($day !== '') $counts[$day] = (int) $row->get('messageCount');
        }

        $trend = [];
        $start = (new \DateTimeImmutable(gmdate('Y-m-d'), new \DateTimeZone('UTC')))->modify('-29 days');
        for ($offset = 0; $offset < 30; $offset++) {
            $day = $start->modify('+' . $offset . ' days')->format('Y-m-d');
            $trend[] = ['date' => $day, 'count' => $counts[$day] ?? 0];
        }

        return $trend;
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
