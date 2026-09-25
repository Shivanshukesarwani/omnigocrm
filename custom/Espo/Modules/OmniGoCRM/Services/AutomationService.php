<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Core\Exceptions\BadRequest;

class AutomationService
{
    public function __construct(private EntityManager $entityManager) {}

    public function dispatch(string $event, string $entityType, string $entityId, ?string $workspaceId = null): int
    {
        $rules = $this->entityManager->getRDBRepository('AutomationRule')->where([
            'event' => $event,
            'active' => true,
            'deleted' => false,
        ]);
        if ($workspaceId) {
            $rules->where(['workspaceId' => $workspaceId]);
        }

        $count = 0;
        foreach ($rules->find() as $rule) {
            $conditions = $this->json($rule->get('conditionsJson'));
            if (!$this->conditionsMatch($conditions, $entityType, $entityId)) {
                continue;
            }
            $run = $this->entityManager->getNewEntity('AutomationRun');
            $run->set([
                'name' => $rule->get('name') . ' / ' . $entityId,
                'ruleId' => $rule->getId(),
                'entityType' => $entityType,
                'entityId' => $entityId,
                'status' => $rule->get('delayMinutes') > 0 ? 'Queued' : 'Running',
                'scheduledAt' => gmdate('Y-m-d H:i:s', time() + ((int) $rule->get('delayMinutes') * 60)),
                'workspaceId' => $workspaceId ?: $rule->get('workspaceId'),
            ]);
            $this->entityManager->saveEntity($run);
            if ((int) $rule->get('delayMinutes') === 0) {
                $this->executeRun($run, $rule);
            }
            $count++;
        }
        return $count;
    }

    public function executeDue(): int
    {
        $runs = $this->entityManager->getRDBRepository('AutomationRun')->where([
            'status' => 'Queued',
            'scheduledAt<=' => gmdate('Y-m-d H:i:s'),
            'deleted' => false,
        ])->find();
        $count = 0;
        foreach ($runs as $run) {
            $rule = $this->entityManager->getEntityById('AutomationRule', $run->get('ruleId'));
            if (!$rule || !$rule->get('active')) {
                $run->set('status', 'Skipped');
                $this->entityManager->saveEntity($run);
                continue;
            }
            $this->executeRun($run, $rule);
            $count++;
        }
        return $count;
    }

    private function executeRun($run, $rule): void
    {
        $run->set(['status' => 'Running', 'startedAt' => gmdate('Y-m-d H:i:s')]);
        $this->entityManager->saveEntity($run);
        try {
            $actions = $this->json($rule->get('actionsJson'));
            $result = [];
            foreach ($actions as $action) {
                $result[] = $this->executeAction($action, $run->get('entityType'), $run->get('entityId'));
            }
            $run->set(['status' => 'Completed', 'completedAt' => gmdate('Y-m-d H:i:s'), 'resultJson' => json_encode($result)]);
        } catch (\Throwable $e) {
            $run->set(['status' => 'Failed', 'completedAt' => gmdate('Y-m-d H:i:s'), 'errorMessage' => $e->getMessage()]);
        }
        $this->entityManager->saveEntity($run);
    }

    private function executeAction(array $action, string $entityType, string $entityId): array
    {
        $type = $action['type'] ?? '';
        if ($type === 'createTask') {
            $task = $this->entityManager->getNewEntity('Task');
            $task->set([
                'name' => (string) ($action['name'] ?? 'Automation follow-up'),
                'status' => 'Not Started',
                'dateStart' => $action['dateStart'] ?? gmdate('Y-m-d H:i:s'),
                'description' => $action['description'] ?? ('Automation action for ' . $entityType . ' ' . $entityId),
            ]);
            $this->entityManager->saveEntity($task);
            return ['type' => $type, 'id' => $task->getId()];
        }
        if ($type === 'updateRecord') {
            $record = $this->entityManager->getEntityById($entityType, $entityId);
            if (!$record) {
                throw new BadRequest('Automation target record not found.');
            }
            $fields = is_array($action['fields'] ?? null) ? $action['fields'] : [];
            $record->set($fields);
            $this->entityManager->saveEntity($record);
            return ['type' => $type, 'id' => $entityId];
        }
        return ['type' => $type, 'status' => 'unsupported'];
    }

    private function conditionsMatch(array $conditions, string $entityType, string $entityId): bool
    {
        if (!$conditions) {
            return true;
        }
        $record = $this->entityManager->getEntityById($entityType, $entityId);
        if (!$record) {
            return false;
        }
        foreach ($conditions as $field => $expected) {
            if ((string) $record->get($field) !== (string) $expected) {
                return false;
            }
        }
        return true;
    }

    private function json(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
