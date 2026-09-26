# OmniGoCRM WhatsApp

OmniGoCRM uses the official WhatsApp Cloud API architecture for server-initiated messaging. Manual click-to-chat remains separate and does not attempt to automate WhatsApp Web sessions.

## Configuration

Keep these credentials out of Git:

- `omniGoCRMWhatsAppAccessToken`
- `omniGoCRMWhatsAppAppSecret`
- `omniGoCRMWhatsAppVerifyToken`
- `omniGoCRMWhatsAppPhoneNumberId`
- `omniGoCRMWhatsAppGraphVersion`

The Graph API version is configurable so an installation can move between supported provider versions without changing application code.
Set the admin configuration value `omniGoCRMWhatsAppWorkspaceId` to the workspace that owns this WhatsApp Business phone number. Incoming messages, new leads, conversations, and automation runs are assigned to this workspace unless an existing matched lead already belongs to one.

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

Never commit WhatsApp tokens or app secrets. Use the server's App Secrets feature for sensitive values instead of plain configuration where appropriate.


## Shared Inbox API

Use the standard entity API to list `WhatsAppConversation` records and their related `WhatsAppMessage` records. The custom conversation action supports `read`, `open`, and `close` for authenticated CRM users.

The conversation record tracks unread count and last-message timestamps. Inbound webhook events increment unread count; opening a conversation and calling the `read` action resets it.

## Template lifecycle

Templates are intentionally stored as provider-backed records. `Draft` and `Pending` templates are not sendable. `Approved` + `active=true` is required. Provider synchronization can update approval status, language, category, components, and `lastSyncedAt`; provider credentials remain installation secrets.

## Provider boundary

All server-initiated messaging uses Meta's official Cloud API. OmniGoCRM does not automate WhatsApp Web, browser sessions, QR logins, or unofficial client protocols.

## Incoming-message automation

Create an `AutomationRule` record in the WhatsApp workspace. Select **WhatsApp Message Received**, enable the rule, and use JSON conditions/actions. The inbound webhook queues matching rules after saving the message; executions and failures are visible as `AutomationRun` records. The scheduled automation job processes queued work each minute.

Conditions are optional. Flat objects compare fields using `equals`; the `textContains` and `textEquals` shorthand perform case-insensitive message-text matching:

~~~json
{
  "textContains": "pricing",
  "messageType": "Text"
}
~~~

For grouped conditions, use `all` and/or `any` with condition objects. Supported operators are `equals`, `notEquals`, `contains`, `startsWith`, `endsWith`, `greaterThan`, `lessThan`, `isEmpty`, and `isNotEmpty`:

~~~json
{
  "all": [
    { "field": "messageType", "operator": "equals", "value": "Text" }
  ],
  "any": [
    { "field": "textBody", "operator": "contains", "value": "pricing" },
    { "field": "textBody", "operator": "contains", "value": "cost" }
  ]
}
~~~

Use one or more of these actions. `if` selects a `then` or `else` action list, and `wait` suspends the run for a bounded interval before continuing:

~~~json
[
  {
    "type": "if",
    "conditions": {
      "textContains": "pricing"
    },
    "then": [
      { "type": "sendWhatsAppText", "body": "Hi {{firstName}}, I can help with pricing." }
    ],
    "else": [
      { "type": "wait", "duration": 2, "unit": "minutes" },
      { "type": "createTask", "name": "Review WhatsApp inquiry" }
    ]
  }
]
~~~

An action list can also directly send a reply:

~~~json
[
  {
    "type": "sendWhatsAppText",
    "body": "Hi {{firstName}}, thanks for asking about our pricing."
  }
]
~~~

Text replies are sent only within 24 hours of the inbound message, as required by WhatsApp's customer-service window. Supported lead placeholders are `{{firstName}}`, `{{lastName}}`, `{{name}}`, and `{{whatsappNumber}}`.

Approved templates can be sent outside the service window, but require the lead's WhatsApp opt-in and an active, approved `WhatsAppTemplate`:

~~~json
[
  {
    "type": "sendWhatsAppTemplate",
    "templateName": "follow_up",
    "languageCode": "en_US",
    "components": []
  }
]
~~~

Automation conditions/actions must be valid JSON. Supported action types are `createTask`, `updateRecord`, `sendWhatsAppText`, `sendWhatsAppTemplate`, `if`, and `wait`; unsupported actions fail the run instead of being reported as successful. The visual automation builder and keyword/scheduled triggers are planned separately; see `docs/WACRM_FEATURE_INTEGRATION.md`.


## Shared inbox

The native `WhatsAppConversation` entity groups messages by WhatsApp ID. The authenticated inbox endpoint is:

```text
GET /api/v1/OmniGoCRM/WhatsApp/inbox
POST /api/v1/OmniGoCRM/WhatsApp/conversationAction
```

Conversation actions are `read`, `close`, and `open`. Conversations retain unread counts, assignment fields, lead/contact links, and last-message metadata. Individual messages retain provider IDs, delivery state, template name, and media metadata.

## Media

Inbound image, document, audio and video webhooks are persisted with the provider media ID, MIME type and optional caption. Media download/storage is intentionally separated from webhook receipt so webhook processing remains fast and idempotent.

## Templates

Only active `WhatsAppTemplate` records with provider status `Approved` can be sent through the CRM template endpoint. CRM-initiated messages continue to require lead WhatsApp opt-in.
