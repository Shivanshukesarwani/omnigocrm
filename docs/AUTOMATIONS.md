# OmniGoCRM automations

Automation rules are scoped to the active workspace. Lead creation, lead-stage changes, opportunity-stage changes, missed calls, due follow-ups, and inbound WhatsApp messages can start rules. The automation worker runs queued actions and resumes after waits.

## WhatsApp drip sequence

Create an active `AutomationRule` with event `LeadCreated` and an `actionsJson` sequence such as:

~~~json
[
  {"type":"sendWhatsAppTemplate","templateName":"welcome","components":[{"type":"body","parameters":[{"type":"text","text":"Hi {{firstName}}"}]}]},
  {"type":"wait","duration":2,"unit":"days"},
  {"type":"sendWhatsAppTemplate","templateName":"follow_up"}
]
~~~

Each template must be active and approved in the same workspace. The lead must have WhatsApp opt-in and a WhatsApp number when each template action runs. Supported placeholders are `{{firstName}}`, `{{lastName}}`, `{{name}}`, and `{{whatsappNumber}}`.

## Assignment rule

Use an `assignRecord` action on a `LeadCreated` or `LeadStageChanged` rule. The configured `assignedUserId` must be an active workspace member. Conditional `if` actions can route different conditions to different members.

## Signed outbound webhook

Configure `omniGoCRMAutomationWebhookHosts` with the exact HTTPS hostnames that admins approve, and set the internal `omniGoCRMAutomationWebhookSecret`. Then add an action such as:

~~~json
{"type":"webhook","url":"https://hooks.example.com/omnigocrm","event":"LeadQualified"}
~~~

The destination receives a JSON envelope containing the event name, entity type and ID, workspace ID, and UTC timestamp. The request includes `X-OmniGoCRM-Signature: sha256=…`, an HMAC-SHA256 signature of the exact request body using the configured secret. Hosts must match the allowlist exactly; only HTTPS port 443 is accepted, credentials in URLs are rejected, and redirects are not followed. Delivery errors and non-2xx responses fail the automation run.

Wait actions accept `seconds`, `minutes`, `hours`, or `days`, up to 30 days. Text replies remain limited to the WhatsApp 24-hour conversation window; use approved templates for later messages.
