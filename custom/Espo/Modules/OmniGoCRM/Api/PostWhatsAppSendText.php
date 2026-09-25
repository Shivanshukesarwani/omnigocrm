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

class PostWhatsAppSendText implements Action
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

        $leadId = isset($data->leadId) && is_string($data->leadId)
            ? trim($data->leadId)
            : '';

        $body = isset($data->body) && is_string($data->body)
            ? trim($data->body)
            : '';

        if ($leadId === '') {
            throw new BadRequest('leadId is required.');
        }

        if ($body === '') {
            throw new BadRequest('body is required.');
        }

        if (mb_strlen($body) > 4096) {
            throw new BadRequest('body is too long.');
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

        $result = $this->client->sendText(
            recipient: $recipient,
            body: $body,
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
            'messageType' => 'Text',
            'toNumber' => $recipient,
            'textBody' => $body,
            'leadId' => $leadId,
            'externalLeadId' => $lead->get('externalLeadId'),
            'conversationId' => $conversation->getId(),
            'sentAt' => gmdate('Y-m-d H:i:s'),
            'rawPayload' => json_encode($result->response, JSON_UNESCAPED_SLASHES),
        ]);

        $this->entityManager->saveEntity($message);
        $this->conversationService->outgoing($conversation, $body, $message->get('sentAt'));

        return ResponseComposer::json([
            'accepted' => true,
            'leadId' => $leadId,
            'providerMessageId' => $result->providerMessageId,
        ]);
    }
}
