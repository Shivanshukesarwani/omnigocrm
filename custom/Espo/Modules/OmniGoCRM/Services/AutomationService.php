<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use DateTimeImmutable;
use DateTimeZone;

class AutomationService
{
    public function __construct(
        private EntityManager $entityManager,
        private WhatsAppCloudApi $whatsApp,
        private WhatsAppConversationService $conversationService,
    ) {}

    public function dispatch(string $event, string $entityType, string $entityId, ?string $workspaceId = null): int
    {
        $rules = $this->entityManager->getRDBRepository('AutomationRule')->where([
            'event' => $event,
            'active' => true,
            'deleted' => false,
        ]);
        $rules->where(['workspaceId' => $workspaceId]);

        $count = 0;
        foreach ($rules->find() as $rule) {
            try {
                $conditions = $this->json($rule->get('conditionsJson'), 'conditionsJson');
                $this->validateConditions($conditions);
                $actions = $this->json($rule->get('actionsJson'), 'actionsJson');

                if ($actions === []) {
                    throw new BadRequest('Automation rule must contain at least one action.');
                }
                $this->validateActions($actions);

                if (!$this->conditionsMatch($conditions, $entityType, $entityId)) {
                    continue;
                }
            } catch (BadRequest $e) {
                $this->createFailedRun($rule, $entityType, $entityId, $workspaceId, $e->getMessage());
                continue;
            }
            $run = $this->entityManager->getNewEntity('AutomationRun');
            $run->set([
                'name' => $rule->get('name') . ' / ' . $entityId,
                'ruleId' => $rule->getId(),
                'entityType' => $entityType,
                'entityId' => $entityId,
                'status' => 'Queued',
                'scheduledAt' => gmdate('Y-m-d H:i:s', time() + ((int) $rule->get('delayMinutes') * 60)),
                'actionsJson' => json_encode($actions, JSON_THROW_ON_ERROR),
                'actionIndex' => 0,
                'workspaceId' => $workspaceId ?: $rule->get('workspaceId'),
                'omniGoCRMWorkspaceId' => $workspaceId ?: $rule->get('omniGoCRMWorkspaceId'),
            ]);
            $this->entityManager->saveEntity($run);
            $count++;
        }
        return $count;
    }

    public function executeDue(): int
    {
        $this->recoverStaleRuns();

        $runs = $this->entityManager->getRDBRepository('AutomationRun')->where([
            'status' => 'Queued',
            'scheduledAt<=' => gmdate('Y-m-d H:i:s'),
            'deleted' => false,
        ])->order('scheduledAt', 'ASC')->limit(100)->find();
        $count = 0;
        foreach ($runs as $run) {
            $rule = $this->entityManager->getEntityById('AutomationRule', $run->get('ruleId'));
            $ruleWorkspaceId = $rule ? $this->entityWorkspaceId($rule) : '';
            $runWorkspaceId = $this->entityWorkspaceId($run);
            if (!$rule || !$rule->get('active') || $ruleWorkspaceId !== $runWorkspaceId) {
                $run->set('status', 'Skipped');
                $this->entityManager->saveEntity($run);
                continue;
            }
            $this->executeRun($run, $rule);
            $count++;
        }
        return $count;
    }

    private function recoverStaleRuns(): void
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - 900);
        $runs = $this->entityManager->getRDBRepository('AutomationRun')->where([
            'status' => 'Running',
            'startedAt<=' => $cutoff,
            'deleted' => false,
        ])->limit(100)->find();

        foreach ($runs as $run) {
            $run->set([
                'status' => 'Queued',
                'scheduledAt' => gmdate('Y-m-d H:i:s'),
                'startedAt' => null,
            ]);
            $this->entityManager->saveEntity($run);
        }
    }

    private function executeRun($run, $rule): void
    {
        $run->set(['status' => 'Running', 'startedAt' => gmdate('Y-m-d H:i:s')]);
        $this->entityManager->saveEntity($run);
        try {
            $actions = $this->json(
                $run->get('actionsJson') ?: $rule->get('actionsJson'),
                'actionsJson',
            );
            if ($actions === []) {
                throw new BadRequest('Automation rule must contain at least one action.');
            }
            $this->validateActions($actions);

            $result = $this->json($run->get('resultJson'), 'resultJson');
            $index = max(0, (int) $run->get('actionIndex'));
            $workspaceId = $this->entityWorkspaceId($run);
            $target = $this->entityManager->getEntityById(
                (string) $run->get('entityType'),
                (string) $run->get('entityId'),
            );
            if (!$target) {
                throw new BadRequest('Automation target record not found.');
            }
            $this->assertEntityWorkspace($target, $workspaceId, 'Automation target');

            while ($index < count($actions)) {
                $action = $actions[$index];
                if (!is_array($action)) {
                    throw new BadRequest('Each automation action must be an object.');
                }

                $type = $action['type'] ?? null;

                if ($type === 'if') {
                    $branch = $this->branchActions(
                        $action,
                        $run->get('entityType'),
                        $run->get('entityId'),
                        0,
                    );
                    array_splice($actions, $index, 1, $branch);
                    $run->set([
                        'actionsJson' => json_encode($actions, JSON_THROW_ON_ERROR),
                        'startedAt' => gmdate('Y-m-d H:i:s'),
                    ]);
                    $this->entityManager->saveEntity($run);
                    continue;
                }

                if ($type === 'wait') {
                    $delay = $this->getWaitSeconds($action);
                    $run->set([
                        'status' => 'Queued',
                        'actionIndex' => $index + 1,
                        'scheduledAt' => gmdate('Y-m-d H:i:s', time() + $delay),
                        'startedAt' => null,
                    ]);
                    $this->entityManager->saveEntity($run);
                    return;
                }

                $result[] = $this->executeAction(
                    $action,
                    $run->get('entityType'),
                    $run->get('entityId'),
                    $workspaceId,
                );
                $index++;
                $run->set([
                    'actionIndex' => $index,
                    'resultJson' => json_encode($result, JSON_THROW_ON_ERROR),
                    'startedAt' => gmdate('Y-m-d H:i:s'),
                ]);
                $this->entityManager->saveEntity($run);
            }
            $run->set(['status' => 'Completed', 'completedAt' => gmdate('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            $run->set(['status' => 'Failed', 'completedAt' => gmdate('Y-m-d H:i:s'), 'errorMessage' => $e->getMessage()]);
        }
        $this->entityManager->saveEntity($run);
    }

    private function executeAction(
        array $action,
        string $entityType,
        string $entityId,
        string $workspaceId,
    ): array
    {
        $type = $action['type'] ?? null;
        if (!is_string($type) || $type === '') {
            throw new BadRequest('Automation action type is required.');
        }

        if ($type === 'createTask') {
            $task = $this->entityManager->getNewEntity('Task');
            $taskData = [
                'name' => (string) ($action['name'] ?? 'Automation follow-up'),
                'status' => 'Not Started',
                'dateStart' => $action['dateStart'] ?? gmdate('Y-m-d H:i:s'),
                'description' => $action['description'] ?? ('Automation action for ' . $entityType . ' ' . $entityId),
                'omniGoCRMWorkspaceId' => $workspaceId,
            ];

            $parentType = $entityType;
            $parentId = $entityId;
            if ($entityType === 'WhatsAppMessage') {
                $message = $this->entityManager->getEntityById('WhatsAppMessage', $entityId);
                $parentType = 'Lead';
                $parentId = trim((string) $message?->get('leadId'));
            }

            if ($parentId !== '' && in_array($parentType, ['Account', 'Contact', 'Lead', 'Opportunity', 'Case'], true)) {
                $parent = $this->entityManager->getEntityById($parentType, $parentId);
                if (!$parent) {
                    throw new BadRequest('Automation task parent record not found.');
                }
                $this->assertEntityWorkspace($parent, $workspaceId, 'Automation task parent');
                $taskData['parentType'] = $parentType;
                $taskData['parentId'] = $parentId;
            }

            $task->set($taskData);
            $this->entityManager->saveEntity($task);
            return ['type' => $type, 'id' => $task->getId()];
        }
        if ($type === 'updateRecord') {
            $record = $this->entityManager->getEntityById($entityType, $entityId);
            if (!$record) {
                throw new BadRequest('Automation target record not found.');
            }
            $fields = is_array($action['fields'] ?? null) ? $action['fields'] : [];
            if ($fields === []) {
                throw new BadRequest('updateRecord requires a non-empty fields object.');
            }
            if (array_intersect(['workspaceId', 'omniGoCRMWorkspaceId'], array_keys($fields)) !== []) {
                throw new BadRequest('Automation actions cannot change record workspace ownership.');
            }
            $this->assertEntityWorkspace($record, $workspaceId, 'Automation target');
            $record->set($fields);
            $this->entityManager->saveEntity($record);
            return ['type' => $type, 'id' => $entityId];
        }

        if ($type === 'sendWhatsAppText') {
            return $this->sendWhatsAppText($action, $entityType, $entityId, $workspaceId);
        }

        if ($type === 'sendWhatsAppTemplate') {
            return $this->sendWhatsAppTemplate($action, $entityType, $entityId, $workspaceId);
        }

        throw new BadRequest('Unsupported automation action type: ' . (string) $type);
    }

    private function conditionsMatch(array $conditions, string $entityType, string $entityId, int $depth = 0): bool
    {
        if ($depth > 10) {
            throw new BadRequest('Automation conditions exceed the maximum nesting depth.');
        }

        if (!$conditions) {
            return true;
        }
        $record = $this->entityManager->getEntityById($entityType, $entityId);
        if (!$record) {
            return false;
        }

        if (isset($conditions['all']) || isset($conditions['any'])) {
            $all = $conditions['all'] ?? [];
            $any = $conditions['any'] ?? [];

            if (!is_array($all) || !is_array($any)) {
                throw new BadRequest('Automation condition all/any values must be arrays.');
            }

            foreach ($all as $condition) {
                if (!is_array($condition) || !$this->conditionsMatch($condition, $entityType, $entityId, $depth + 1)) {
                    return false;
                }
            }

            if ($any !== []) {
                $matchedAny = false;
                foreach ($any as $condition) {
                    if (is_array($condition) && $this->conditionsMatch($condition, $entityType, $entityId, $depth + 1)) {
                        $matchedAny = true;
                        break;
                    }
                }
                if (!$matchedAny) {
                    return false;
                }
            }

            return true;
        }

        if (array_key_exists('field', $conditions)) {
            return $this->matchCondition($conditions, $record);
        }

        foreach ($conditions as $field => $expected) {
            if ($field === 'all' || $field === 'any') {
                continue;
            }

            if (!is_string($field) || (!is_string($expected) && !is_numeric($expected) && !is_bool($expected))) {
                return false;
            }

            $condition = match ($field) {
                'textContains' => ['field' => 'textBody', 'operator' => 'contains', 'value' => $expected],
                'textEquals' => ['field' => 'textBody', 'operator' => 'equals', 'value' => $expected],
                default => ['field' => $field, 'operator' => 'equals', 'value' => $expected],
            };

            if (!$this->matchCondition($condition, $record)) {
                return false;
            }
        }
        return true;
    }

    private function matchCondition(array $condition, Entity $record): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $expected = $condition['value'] ?? null;

        if (!is_string($field) || !is_string($operator)) {
            throw new BadRequest('Automation conditions require string field and operator values.');
        }

        if (!in_array($operator, ['equals', 'notEquals', 'contains', 'startsWith', 'endsWith', 'greaterThan', 'lessThan', 'isEmpty', 'isNotEmpty'], true)) {
            throw new BadRequest('Unsupported automation condition operator: ' . $operator);
        }

        $actual = $record->get($field);
        if ($operator === 'isEmpty') {
            return $actual === null || trim((string) $actual) === '';
        }
        if ($operator === 'isNotEmpty') {
            return $actual !== null && trim((string) $actual) !== '';
        }
        if (!is_scalar($expected) || !is_scalar($actual)) {
            return false;
        }

        return match ($operator) {
            'equals' => mb_strtolower(trim((string) $actual)) === mb_strtolower(trim((string) $expected)),
            'notEquals' => mb_strtolower(trim((string) $actual)) !== mb_strtolower(trim((string) $expected)),
            'contains' => mb_stripos((string) $actual, (string) $expected) !== false,
            'startsWith' => mb_stripos((string) $actual, (string) $expected) === 0,
            'endsWith' => mb_stripos((string) $actual, (string) $expected) === mb_strlen((string) $actual) - mb_strlen((string) $expected),
            'greaterThan' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'lessThan' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            default => false,
        };
    }

    private function branchActions(array $action, string $entityType, string $entityId, int $depth): array
    {
        if ($depth >= 10) {
            throw new BadRequest('Automation branches exceed the maximum nesting depth.');
        }

        $conditions = $action['conditions'] ?? null;
        $then = $action['then'] ?? [];
        $else = $action['else'] ?? [];

        if (!is_array($conditions) || !is_array($then) || !is_array($else)) {
            throw new BadRequest('An if action requires condition, then, and optional else arrays.');
        }

        $this->validateConditions($conditions);
        $this->validateActions($then, $depth + 1);
        $this->validateActions($else, $depth + 1);
        $target = $this->entityManager->getEntityById($entityType, $entityId);
        $matches = $target !== null && $this->conditionsMatch($conditions, $entityType, $entityId);

        return $matches ? $then : $else;
    }

    private function validateActions(array $actions, int $depth = 0): void
    {
        if ($depth > 10) {
            throw new BadRequest('Automation actions exceed the maximum nesting depth.');
        }

        foreach ($actions as $action) {
            if (!is_array($action) || !is_string($action['type'] ?? null)) {
                throw new BadRequest('Each automation action must be an object with a type.');
            }

            if ($action['type'] === 'if') {
                if (!is_array($action['conditions'] ?? null)) {
                    throw new BadRequest('An if action requires a conditions object.');
                }

                $then = $action['then'] ?? [];
                $else = $action['else'] ?? [];
                if (!is_array($then) || !is_array($else)) {
                    throw new BadRequest('If action then and else values must be arrays.');
                }

                $this->validateConditions($action['conditions']);
                $this->validateActions($then, $depth + 1);
                $this->validateActions($else, $depth + 1);
                continue;
            }

            if ($action['type'] === 'wait') {
                $this->getWaitSeconds($action);
                continue;
            }

            if (!in_array($action['type'], ['createTask', 'updateRecord', 'sendWhatsAppText', 'sendWhatsAppTemplate'], true)) {
                throw new BadRequest('Unsupported automation action type: ' . $action['type']);
            }

            if ($action['type'] === 'updateRecord' && (!is_array($action['fields'] ?? null) || $action['fields'] === [])) {
                throw new BadRequest('updateRecord requires a non-empty fields object.');
            }

            if ($action['type'] === 'sendWhatsAppText' && !is_string($action['body'] ?? null)) {
                throw new BadRequest('sendWhatsAppText requires a string body.');
            }

            if ($action['type'] === 'sendWhatsAppTemplate') {
                if (!is_string($action['templateName'] ?? null) || trim($action['templateName']) === '') {
                    throw new BadRequest('sendWhatsAppTemplate requires a templateName.');
                }
                if (isset($action['components']) && !is_array($action['components'])) {
                    throw new BadRequest('WhatsApp template components must be an array.');
                }
                if (isset($action['languageCode']) && !is_string($action['languageCode'])) {
                    throw new BadRequest('WhatsApp template languageCode must be a string.');
                }
            }
        }
    }

    private function validateConditions(array $conditions, int $depth = 0): void
    {
        if ($depth > 10) {
            throw new BadRequest('Automation conditions exceed the maximum nesting depth.');
        }

        if (array_key_exists('all', $conditions) || array_key_exists('any', $conditions)) {
            if (array_diff(array_keys($conditions), ['all', 'any']) !== []) {
                throw new BadRequest('Grouped automation conditions can only contain all and any groups.');
            }

            foreach (['all', 'any'] as $group) {
                if (!array_key_exists($group, $conditions)) {
                    continue;
                }
                if (!is_array($conditions[$group])) {
                    throw new BadRequest('Automation condition groups must be arrays.');
                }
                foreach ($conditions[$group] as $condition) {
                    if (!is_array($condition)) {
                        throw new BadRequest('Each grouped automation condition must be an object.');
                    }
                    $this->validateConditions($condition, $depth + 1);
                }
            }

            return;
        }

        if (array_key_exists('field', $conditions)) {
            $field = $conditions['field'] ?? null;
            $operator = $conditions['operator'] ?? 'equals';
            if (!is_string($field) || !is_string($operator)) {
                throw new BadRequest('Automation conditions require string field and operator values.');
            }
            if (!in_array($operator, ['equals', 'notEquals', 'contains', 'startsWith', 'endsWith', 'greaterThan', 'lessThan', 'isEmpty', 'isNotEmpty'], true)) {
                throw new BadRequest('Unsupported automation condition operator: ' . $operator);
            }
            if (!in_array($operator, ['isEmpty', 'isNotEmpty'], true) && !is_scalar($conditions['value'] ?? null)) {
                throw new BadRequest('This automation condition operator requires a scalar value.');
            }

            return;
        }

        foreach ($conditions as $field => $value) {
            if (!is_string($field) || !is_scalar($value)) {
                throw new BadRequest('Flat automation conditions must map field names to scalar values.');
            }
        }
    }

    private function getWaitSeconds(array $action): int
    {
        $duration = $action['duration'] ?? null;
        $unit = $action['unit'] ?? 'minutes';

        if (!is_int($duration) || $duration < 1 || !is_string($unit)) {
            throw new BadRequest('A wait action requires a positive integer duration and a valid unit.');
        }

        $seconds = match ($unit) {
            'seconds' => $duration,
            'minutes' => $duration * 60,
            'hours' => $duration * 3600,
            'days' => $duration * 86400,
            default => throw new BadRequest('Wait unit must be seconds, minutes, hours, or days.'),
        };

        if ($seconds > 2592000) {
            throw new BadRequest('Automation waits cannot exceed 30 days.');
        }

        return $seconds;
    }

    private function json(mixed $value, string $field): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BadRequest('Automation ' . $field . ' must contain valid JSON.');
        }

        if (!is_array($decoded)) {
            throw new BadRequest('Automation ' . $field . ' must contain a JSON object or array.');
        }

        return $decoded;
    }

    private function sendWhatsAppText(
        array $action,
        string $entityType,
        string $entityId,
        string $workspaceId,
    ): array {
        $message = $this->getInboundWhatsAppMessage($entityType, $entityId, $workspaceId);
        $body = trim((string) ($action['body'] ?? ''));

        if ($body === '' || mb_strlen($body) > 4096) {
            throw new BadRequest('sendWhatsAppText requires a body of 1 to 4096 characters.');
        }

        if (!$this->isWithinWhatsAppTextWindow((string) $message->get('receivedAt'))) {
            throw new BadRequest('WhatsApp text replies are only allowed within 24 hours of the inbound message.');
        }

        $leadId = trim((string) $message->get('leadId'));
        $lead = $leadId !== '' ? $this->entityManager->getEntityById('Lead', $leadId) : null;
        if ($lead) {
            $this->assertEntityWorkspace($lead, $workspaceId, 'WhatsApp recipient');
        }
        $body = $this->personalize($body, $lead);

        if (mb_strlen($body) > 4096) {
            throw new BadRequest('Personalized WhatsApp reply is too long.');
        }

        $recipient = trim((string) $message->get('fromNumber'));
        if ($recipient === '') {
            throw new BadRequest('Inbound WhatsApp message has no sender number.');
        }

        $result = $this->whatsApp->sendText($recipient, $body);

        return $this->saveOutboundMessage(
            $message,
            $lead,
            $recipient,
            $body,
            null,
            $result,
            $workspaceId,
        );
    }

    private function sendWhatsAppTemplate(
        array $action,
        string $entityType,
        string $entityId,
        string $workspaceId,
    ): array {
        $message = $this->getInboundWhatsAppMessage($entityType, $entityId, $workspaceId);
        $leadId = trim((string) $message->get('leadId'));
        $lead = $leadId !== '' ? $this->entityManager->getEntityById('Lead', $leadId) : null;
        if ($lead) {
            $this->assertEntityWorkspace($lead, $workspaceId, 'WhatsApp recipient');
        }

        if (!$lead || !$lead->get('whatsappOptIn')) {
            throw new BadRequest('WhatsApp opt-in is required to send an automated template.');
        }

        $templateName = trim((string) ($action['templateName'] ?? ''));
        if ($templateName === '') {
            throw new BadRequest('sendWhatsAppTemplate requires templateName.');
        }

        $templateQuery = $this->entityManager
            ->getRDBRepository('WhatsAppTemplate')
            ->where([
                'name' => $templateName,
                'status' => 'Approved',
                'active' => true,
                'deleted' => false,
                'omniGoCRMWorkspaceId' => $workspaceId !== '' ? $workspaceId : null,
            ]);
        $requestedLanguage = trim((string) ($action['languageCode'] ?? ''));
        if ($requestedLanguage !== '') {
            $templateQuery->where(['languageCode' => $requestedLanguage]);
        }
        $template = $templateQuery->findOne();

        if (!$template) {
            throw new BadRequest('An active, approved WhatsApp template is required for this workspace and language.');
        }
        $this->assertEntityWorkspace($template, $workspaceId, 'WhatsApp template');

        $languageCode = trim((string) $template->get('languageCode')) ?: 'en_US';
        $components = $action['components'] ?? [];
        if (!is_array($components)) {
            throw new BadRequest('WhatsApp template components must be an array.');
        }

        $recipient = trim((string) $message->get('fromNumber'));
        if ($recipient === '') {
            throw new BadRequest('Inbound WhatsApp message has no sender number.');
        }

        $result = $this->whatsApp->sendTemplate($recipient, $templateName, $languageCode, $components);

        return $this->saveOutboundMessage(
            $message,
            $lead,
            $recipient,
            '[Template] ' . $templateName,
            $templateName,
            $result,
            $workspaceId,
        );
    }

    private function getInboundWhatsAppMessage(
        string $entityType,
        string $entityId,
        string $workspaceId,
    ): Entity {
        if ($entityType !== 'WhatsAppMessage') {
            throw new BadRequest('WhatsApp send actions require a WhatsAppReceived event.');
        }

        $message = $this->entityManager->getEntityById('WhatsAppMessage', $entityId);
        if (!$message || $message->get('direction') !== 'Inbound') {
            throw new BadRequest('Automation target is not an inbound WhatsApp message.');
        }

        $this->assertEntityWorkspace($message, $workspaceId, 'WhatsApp message');

        return $message;
    }

    private function saveOutboundMessage(
        Entity $inboundMessage,
        ?Entity $lead,
        string $recipient,
        string $body,
        ?string $templateName,
        WhatsAppSendResult $result,
        string $workspaceId,
    ): array {
        $sentAt = gmdate('Y-m-d H:i:s');
        $conversationId = trim((string) $inboundMessage->get('conversationId'));
        $conversation = $conversationId !== ''
            ? $this->entityManager->getEntityById('WhatsAppConversation', $conversationId)
            : null;

        if (!$conversation) {
            throw new BadRequest('Inbound WhatsApp message has no conversation.');
        }
        $this->assertEntityWorkspace($conversation, $workspaceId, 'WhatsApp conversation');

        $outboundMessage = $this->entityManager->getNewEntity('WhatsAppMessage');
        $outboundMessage->set([
            'name' => $result->providerMessageId,
            'providerMessageId' => $result->providerMessageId,
            'direction' => 'Outbound',
            'status' => 'Sent',
            'messageType' => $templateName === null ? 'Text' : 'Template',
            'toNumber' => $recipient,
            'textBody' => $templateName === null ? $body : null,
            'templateName' => $templateName,
            'leadId' => $lead?->getId(),
            'externalLeadId' => $lead?->get('externalLeadId'),
            'conversationId' => $conversation->getId(),
            'sentAt' => $sentAt,
            'rawPayload' => json_encode($result->response, JSON_UNESCAPED_SLASHES),
        ]);

        if ($workspaceId !== '') {
            $outboundMessage->set('omniGoCRMWorkspaceId', $workspaceId);
        }

        $this->entityManager->saveEntity($outboundMessage);
        $this->conversationService->outgoing($conversation, $body, $sentAt);

        return [
            'type' => $templateName === null ? 'sendWhatsAppText' : 'sendWhatsAppTemplate',
            'providerMessageId' => $result->providerMessageId,
        ];
    }

    private function entityWorkspaceId(Entity $entity): string
    {
        return trim((string) ($entity->get('omniGoCRMWorkspaceId') ?: $entity->get('workspaceId')));
    }

    private function assertEntityWorkspace(Entity $entity, string $workspaceId, string $label): void
    {
        if ($this->entityWorkspaceId($entity) !== $workspaceId) {
            throw new BadRequest($label . ' belongs to a different workspace.');
        }
    }

    private function isWithinWhatsAppTextWindow(string $receivedAt, ?int $now = null): bool
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $receivedAt,
            new DateTimeZone('UTC'),
        );
        if (!$date || $date->format('Y-m-d H:i:s') !== $receivedAt) {
            return false;
        }

        $timestamp = $date->getTimestamp();
        $now ??= time();

        return $timestamp <= $now && ($now - $timestamp) < 86400;
    }

    private function personalize(string $body, ?Entity $lead): string
    {
        $values = [];

        foreach (['firstName', 'lastName', 'whatsappNumber'] as $field) {
            $values['{{' . $field . '}}'] = $lead ? (string) $lead->get($field) : '';
        }

        $values['{{name}}'] = $lead
            ? trim((string) $lead->get('firstName') . ' ' . (string) $lead->get('lastName'))
            : '';

        return strtr($body, $values);
    }

    private function createFailedRun(
        Entity $rule,
        string $entityType,
        string $entityId,
        ?string $workspaceId,
        string $error,
    ): void {
        $run = $this->entityManager->getNewEntity('AutomationRun');
        $run->set([
            'name' => $rule->get('name') . ' / ' . $entityId,
            'ruleId' => $rule->getId(),
            'entityType' => $entityType,
            'entityId' => $entityId,
            'status' => 'Failed',
            'scheduledAt' => gmdate('Y-m-d H:i:s'),
            'startedAt' => gmdate('Y-m-d H:i:s'),
            'completedAt' => gmdate('Y-m-d H:i:s'),
            'errorMessage' => $error,
            'workspaceId' => $workspaceId ?: $rule->get('workspaceId'),
            'omniGoCRMWorkspaceId' => $workspaceId ?: $rule->get('omniGoCRMWorkspaceId'),
        ]);
        $this->entityManager->saveEntity($run);
    }
}
