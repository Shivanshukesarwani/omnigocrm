# OmniGoCRM WhatsApp

OmniGoCRM uses the official WhatsApp Cloud API architecture for server-initiated messaging. Manual click-to-chat remains separate and does not attempt to automate WhatsApp Web sessions.

## Configuration

Keep these values out of Git:

- `omniGoCRMWhatsAppAccessToken`
- `omniGoCRMWhatsAppAppSecret`
- `omniGoCRMWhatsAppVerifyToken`
- `omniGoCRMWhatsAppPhoneNumberId`
- `omniGoCRMWhatsAppGraphVersion`

The Graph API version is configurable so an installation can move between supported provider versions without changing application code.

## Outbound text

~~~text
POST /api/v1/OmniGoCRM/WhatsApp/sendText
~~~

Payload:

~~~json
{
  "leadId": "LEAD_ID",
  "body": "Hello Rahul, thank you for contacting us."
}
~~~

CRM-initiated text requires `whatsappOptIn=true` on the Lead.

Every successful send creates a `WhatsAppMessage` record containing the provider message ID, lead linkage and outbound status.

## Approved templates

`WhatsAppTemplate` stores:

- provider template name/ID
- language code
- category
- approval status
- template body
- provider component JSON
- active flag

Only records with `status=Approved` and `active=true` may be sent by the template API.

Endpoint:

~~~text
POST /api/v1/OmniGoCRM/WhatsApp/sendTemplate
~~~

Example:

~~~json
{
  "leadId": "LEAD_ID",
  "templateName": "hello_world",
  "languageCode": "en_US",
  "components": []
}
~~~

The component array is passed through to the provider payload so approved template variables can be supplied without hard-coding a single template schema.

## Conversations

`WhatsAppConversation` groups message history by WhatsApp ID and stores:

- status
- unread count
- last message time
- last message preview
- Lead/Contact linkage
- assignment
- customer display name

Inbound webhook events create or update a conversation; outbound text/template sends update the same conversation.

## Signed webhook

~~~text
GET  /api/v1/OmniGoCRM/WhatsApp/webhook
POST /api/v1/OmniGoCRM/WhatsApp/webhook
~~~

GET validates the configured verification token.

POST validates the `X-Hub-Signature-256` HMAC using the configured app secret before storing inbound messages or provider statuses.

## Security

Never commit WhatsApp tokens or app secrets. EspoCRM also provides App Secrets for sensitive values, which can be used instead of plain configuration where appropriate.
