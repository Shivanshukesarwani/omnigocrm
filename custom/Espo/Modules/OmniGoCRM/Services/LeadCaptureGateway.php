<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Tools\LeadCapture\CaptureService;
use stdClass;

class LeadCaptureGateway
{
    private const SOURCE_OPTIONS = [
        '',
        'Call',
        'Email',
        'Existing Customer',
        'Partner',
        'Public Relations',
        'Web Site',
        'Campaign',
        'Other',
    ];

    private const STAGE_OPTIONS = [
        'New',
        'Contacted',
        'Qualified',
        'Proposal',
        'Negotiation',
        'Won',
        'Lost',
    ];

    private const MAX_TEXT_LENGTH = 10000;

    private const CHANNEL_OPTIONS = [
        'WhatsApp',
        'Phone',
        'Email',
        'SMS',
        'Any',
    ];

    public function __construct(
        private CaptureService $captureService,
        private EntityManager $entityManager,
    ) {}

    /**
     * @return array{
     *     accepted: bool,
     *     status: string,
     *     leadId: ?string,
     *     externalLeadId: ?string
     * }
     */
    public function capture(string $apiKey, stdClass $input): array
    {
        $data = $this->normalize($input);

        if (property_exists($data, 'website') && is_string($data->website) && trim($data->website) !== '') {
            throw new BadRequest('Spam check failed.');
        }

        if (property_exists($data, 'honeypot') && is_string($data->honeypot) && trim($data->honeypot) !== '') {
            throw new BadRequest('Spam check failed.');
        }

        $externalLeadId = $data->externalLeadId ?? null;

        if ($externalLeadId !== null) {
            $existing = $this->findByExternalLeadId($externalLeadId);

            if ($existing) {
                return [
                    'accepted' => true,
                    'status' => 'duplicate',
                    'leadId' => $existing->getId(),
                    'externalLeadId' => $externalLeadId,
                ];
            }
        }

        $this->captureService->capture($apiKey, $data);

        $lead = null;

        if ($externalLeadId !== null) {
            $lead = $this->findByExternalLeadId($externalLeadId);
        }

        return [
            'accepted' => true,
            'status' => 'created',
            'leadId' => $lead?->getId(),
            'externalLeadId' => $externalLeadId,
        ];
    }

    private function normalize(stdClass $input): stdClass
    {
        $data = clone $input;

        $aliases = [
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'email' => 'emailAddress',
            'mobile' => 'phoneNumber',
            'phone' => 'phoneNumber',
            'whatsapp' => 'whatsappNumber',
            'whatsapp_number' => 'whatsappNumber',
            'whatsapp_opt_in' => 'whatsappOptIn',
            'source_detail' => 'leadSourceDetail',
            'score' => 'leadScore',
            'stage' => 'leadStage',
            'follow_up_at' => 'nextFollowUpAt',
            'preferred_contact_channel' => 'preferredContactChannel',
            'campaign_name' => 'campaignName',
            'external_lead_id' => 'externalLeadId',
            'consent_captured_at' => 'consentCapturedAt',
        ];

        foreach ($aliases as $from => $to) {
            if (!property_exists($data, $to) && property_exists($data, $from)) {
                $data->{$to} = $data->{$from};
            }

            unset($data->{$from});
        }

        $stringFields = [
            'firstName',
            'lastName',
            'title',
            'accountName',
            'website',
            'addressStreet',
            'addressCity',
            'addressState',
            'addressCountry',
            'addressPostalCode',
            'leadSourceDetail',
            'campaignName',
            'externalLeadId',
        ];

        foreach ($stringFields as $field) {
            if (!property_exists($data, $field)) {
                continue;
            }

            if (!is_string($data->{$field})) {
                unset($data->{$field});
                continue;
            }

            $data->{$field} = trim($data->{$field});

            if (mb_strlen($data->{$field}) > 255) {
                $data->{$field} = mb_substr($data->{$field}, 0, 255);
            }
        }

        if (property_exists($data, 'description')) {
            if (!is_string($data->description)) {
                unset($data->description);
            } else {
                $data->description = mb_substr(strip_tags($data->description), 0, self::MAX_TEXT_LENGTH);
            }
        }

        unset($data->honeypot);

        if (property_exists($data, 'source')) {
            if (!is_string($data->source) || !in_array($data->source, self::SOURCE_OPTIONS, true)) {
                unset($data->source);
            }
        }

        if (property_exists($data, 'leadStage')) {
            if (!is_string($data->leadStage) || !in_array($data->leadStage, self::STAGE_OPTIONS, true)) {
                throw new BadRequest('Invalid leadStage.');
            }
        }

        if (property_exists($data, 'preferredContactChannel')) {
            if (
                !is_string($data->preferredContactChannel) ||
                !in_array($data->preferredContactChannel, self::CHANNEL_OPTIONS, true)
            ) {
                throw new BadRequest('Invalid preferredContactChannel.');
            }
        }

        if (property_exists($data, 'leadScore')) {
            if (!is_numeric($data->leadScore)) {
                throw new BadRequest('leadScore must be numeric.');
            }

            $data->leadScore = (int) $data->leadScore;

            if ($data->leadScore < 0 || $data->leadScore > 100) {
                throw new BadRequest('leadScore must be between 0 and 100.');
            }
        }

        if (property_exists($data, 'whatsappOptIn')) {
            $data->whatsappOptIn = filter_var(
                $data->whatsappOptIn,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($data->whatsappOptIn === null) {
                throw new BadRequest('whatsappOptIn must be a boolean.');
            }
        }

        if (
            !property_exists($data, 'firstName') &&
            !property_exists($data, 'lastName') &&
            !property_exists($data, 'emailAddress') &&
            !property_exists($data, 'phoneNumber') &&
            !property_exists($data, 'whatsappNumber')
        ) {
            throw new BadRequest('At least one lead identity field is required.');
        }

        return $data;
    }

    private function findByExternalLeadId(string $externalLeadId): ?Lead
    {
        /** @var ?Lead $lead */
        $lead = $this->entityManager
            ->getRDBRepositoryByClass(Lead::class)
            ->where([
                'externalLeadId' => $externalLeadId,
                'deleted' => false,
            ])
            ->findOne();

        return $lead;
    }
}
