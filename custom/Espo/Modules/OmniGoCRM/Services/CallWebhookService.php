<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Modules\Crm\Entities\Lead;
use stdClass;

class CallWebhookService
{
    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
    ) {}

    public function verifySignature(string $body, ?string $signature): void
    {
        $secret = trim((string) $this->config->get('omniGoCRMCallingWebhookSecret'));

        if ($secret === '') {
            throw new Forbidden('Calling webhook secret is not configured.');
        }

        if (!is_string($signature) || $signature === '') {
            throw new Forbidden('Missing calling webhook signature.');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);

        if (!hash_equals($expected, $signature)) {
            throw new Forbidden('Invalid calling webhook signature.');
        }
    }

    public function handle(stdClass $payload): array
    {
        $provider = $this->stringValue($payload, 'provider') ?: 'generic';
        $externalCallId = $this->stringValue($payload, 'externalCallId');

        if ($externalCallId === '') {
            throw new BadRequest('externalCallId is required.');
        }

        $status = strtolower($this->stringValue($payload, 'status'));
        $direction = ucfirst(strtolower($this->stringValue($payload, 'direction')) ?: 'Inbound');

        if (!in_array($direction, ['Inbound', 'Outbound'], true)) {
            throw new BadRequest('direction must be Inbound or Outbound.');
        }

        $from = $this->normalizePhone($this->stringValue($payload, 'from'));
        $to = $this->normalizePhone($this->stringValue($payload, 'to'));
        $phone = $direction === 'Inbound' ? $from : $to;

        $lead = $this->findLead($phone);

        if (!$lead && $direction === 'Inbound' && in_array($status, ['missed', 'no-answer', 'busy', 'failed'], true) && $phone !== '') {
            $lead = $this->createMissedCallLead($phone);
        }

        $call = $this->entityManager
            ->getRDBRepository('Call')
            ->where([
                'omniGoCRMExternalCallId' => $externalCallId,
                'deleted' => false,
            ])
            ->findOne();

        $start = $this->dateValue($payload, 'startedAt') ?: gmdate('Y-m-d H:i:s');
        $duration = max(0, (int) ($payload->duration ?? 0));
        $end = gmdate('Y-m-d H:i:s', strtotime($start) + $duration);

        if (!$call) {
            $call = $this->entityManager->getNewEntity('Call');

            $call->set([
                'name' => ($direction === 'Inbound' ? 'Inbound' : 'Outbound') . ' call ' . $phone,
                'dateStart' => $start,
                'dateEnd' => $end,
                'direction' => $direction,
            ]);

            if ($lead) {
                $call->set([
                    'parentId' => $lead->getId(),
                    'parentType' => Lead::ENTITY_TYPE,
                ]);
            }
        }

        $call->set([
            'status' => $this->mapStatus($status),
            'omniGoCRMProvider' => $provider,
            'omniGoCRMExternalCallId' => $externalCallId,
            'omniGoCRMPhoneNumber' => $phone,
            'omniGoCRMRecordingStatus' => $this->mapRecordingStatus($payload),
            'omniGoCRMRecordingExternalUrl' => $this->nullableString($payload, 'recordingUrl'),
            'omniGoCRMTranscript' => $this->nullableString($payload, 'transcript'),
        ]);

        if (isset($payload->recordingDuration)) {
            $call->set('omniGoCRMRecordingDuration', max(0, (int) $payload->recordingDuration));
        }

        if ($payload->recordingConsentAt ?? null) {
            $call->set('omniGoCRMRecordingConsentAt', $this->dateValue($payload, 'recordingConsentAt'));
        }

        $assignedUserId = trim((string) $this->config->get('omniGoCRMCallingDefaultAssignedUserId'));

        if ($assignedUserId !== '' && !$call->get('assignedUserId')) {
            $call->set('assignedUserId', $assignedUserId);
        }

        $this->entityManager->saveEntity($call);

        return [
            'accepted' => true,
            'callId' => $call->getId(),
            'leadId' => $lead?->getId(),
            'status' => $call->get('status'),
        ];
    }

    private function findLead(string $phone): ?Lead
    {
        if ($phone === '') {
            return null;
        }

        /** @var ?Lead */
        $lead = $this->entityManager
            ->getRDBRepositoryByClass(Lead::class)
            ->where([
                'OR' => [
                    ['phoneNumber*' => '%' . $phone . '%'],
                    ['whatsappNumber*' => '%' . $phone . '%'],
                ],
                'deleted' => false,
            ])
            ->findOne();

        return $lead;
    }

    private function createMissedCallLead(string $phone): Lead
    {
        $lead = $this->entityManager
            ->getRDBRepositoryByClass(Lead::class)
            ->getNew();

        $lead->set([
            'phoneNumber' => $phone,
            'leadStage' => 'New',
            'source' => 'Call',
            'leadSourceDetail' => 'Missed call',
        ]);

        $this->entityManager->saveEntity($lead);

        return $lead;
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'answered', 'completed', 'connected' => 'Held',
            'missed', 'no-answer', 'busy', 'failed', 'rejected', 'declined' => 'Not Held',
            default => 'Planned',
        };
    }

    private function mapRecordingStatus(stdClass $payload): string
    {
        $status = strtolower($this->stringValue($payload, 'recordingStatus'));

        if ($status !== '') {
            return match ($status) {
                'available', 'completed', 'ready' => 'Available',
                'requested', 'processing' => 'Requested',
                'failed', 'error' => 'Failed',
                'deleted' => 'Deleted',
                default => 'Not Available',
            };
        }

        return $this->nullableString($payload, 'recordingUrl') ? 'Available' : 'Not Available';
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function stringValue(stdClass $payload, string $key): string
    {
        return isset($payload->{$key}) && is_string($payload->{$key})
            ? trim($payload->{$key})
            : '';
    }

    private function nullableString(stdClass $payload, string $key): ?string
    {
        $value = $this->stringValue($payload, $key);
        return $value !== '' ? $value : null;
    }

    private function dateValue(stdClass $payload, string $key): ?string
    {
        $value = $this->stringValue($payload, $key);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : gmdate('Y-m-d H:i:s', $timestamp);
    }
}
