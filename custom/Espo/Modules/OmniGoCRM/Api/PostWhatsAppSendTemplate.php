<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\OmniGoCRM\Services\WhatsAppCloudApi;
use Espo\Modules\OmniGoCRM\Services\WhatsAppConversationService;

class PostWhatsAppSendTemplate implements Action
{
    public function __construct(
        private WhatsAppCloudApi $client,
        private EntityManager $entityManager,
        private WhatsAppConversationService $conversationService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if ($data === null) {
            throw new BadRequest('A JSON payload is required.');
        }

        $leadId = isset($data->leadId) && is_string($data->leadId) ? trim($data->leadId) : '';
        $templateName = isset($data->templateName) && is_string($data->templateName) ? trim($data->templateName) : '';
        $languageCode = isset($data->languageCode) && is_string($data->languageCode) ? trim($data->languageCode) : '';
        $components = isset($data->components) && is_array($data->components) ? $data->components : [];

        if ($leadId === '' || $templateName === '') {
            throw new BadRequest('leadId and templateName are required.');
        }

        /** @var ?Lead $lead */
        $lead = $this->entityManager->getEntityById(Lead::ENTITY_TYPE, $leadId);

        if (!$lead) {
            throw new BadRequest('Lead not found.');
        }

        if (!$lead->get('whatsappOptIn')) {
            throw new BadRequest('WhatsApp opt-in is required for CRM-initiated messaging.');
        }

        $recipient = trim((string) $lead->get('whatsappNumber'));

        if ($recipient === '') {
            throw new BadRequest('Lead has no WhatsApp number.');
        }

        $template = $this->entityManager
            ->getRDBRepository('WhatsAppTemplate')
            ->where([
                'name' => $templateName,
                'status' => 'Approved',
                'active' => true,
                'deleted' => false,
            ])
            ->findOne();

        if (!$template) {
            throw new BadRequest('Only an active Approved WhatsAppTemplate can be sent.');
        }

        if ($languageCode === '') {
            $languageCode = (string) $template->get('languageCode') ?: 'en_US';
        }

        $result = $this->client->sendTemplate(
            recipient: $recipient,
            templateName: $templateName,
            languageCode: $languageCode,
            components: $components,
        );

        $conversation = $this->conversationService->findOrCreate(
            waId: $recipient,
            lead: $lead,
        );

        $message = $this->entityManager->getNewEntity('WhatsAppMessage');

        $message->set([
            'name' => $result->providerMessageId,
            'providerMessageId' => $result->providerMessageId,
            'direction' => 'Outbound',
            'status' => 'Sent',
            'messageType' => 'Template',
            'toNumber' => $recipient,
            'templateName' => $templateName,
            'leadId' => $leadId,
            'externalLeadId' => $lead->get('externalLeadId'),
            'conversationId' => $conversation->getId(),
            'sentAt' => gmdate('Y-m-d H:i:s'),
            'rawPayload' => json_encode($result->response, JSON_UNESCAPED_SLASHES),
        ]);

        $this->entityManager->saveEntity($message);
        $this->conversationService->outgoing($conversation, '[Template] ' . $templateName, $message->get('sentAt'));

        return ResponseComposer::json([
            'accepted' => true,
            'leadId' => $leadId,
            'templateName' => $templateName,
            'providerMessageId' => $result->providerMessageId,
        ]);
    }
}
