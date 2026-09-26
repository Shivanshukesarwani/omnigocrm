# WhatsApp webhook foundation

OmniGoCRM now has a signed webhook receiver suitable for the official WhatsApp Cloud API flow.

## Endpoint

~~~text
GET  /api/v1/OmniGoCRM/WhatsApp/webhook
POST /api/v1/OmniGoCRM/WhatsApp/webhook
~~~

The GET route implements the standard verification challenge using the configured `omniGoCRMWhatsAppVerifyToken`.

The POST route validates `X-Hub-Signature-256` using `omniGoCRMWhatsAppAppSecret` before processing events.

## Inbound messages

Inbound WhatsApp messages are stored as `WhatsAppMessage` records. The current parser supports text, image, document, audio, video, interactive, and template message types at the record level. Text bodies are captured for text messages and unsupported payload details remain available in `rawPayload`.

The receiver attempts to match the sender to an existing Lead using the WhatsApp or phone number. When no lead exists, it creates a new Lead sourced as an inbound WhatsApp lead. The message is stored in its WhatsApp conversation and active `WhatsAppReceived` automation rules are queued for processing.

## Delivery status

Outbound provider status events update matching `WhatsAppMessage` records from Sent to Delivered, Read, or Failed when the provider message ID matches.

Delivery status updates are processed with the webhook. Media downloading and template catalog synchronization remain separate layers; assignment and internal-note workflows are part of the planned shared-inbox phase.
