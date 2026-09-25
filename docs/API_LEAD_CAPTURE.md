# OmniGoCRM Lead Capture API

OmniGoCRM exposes a small server-to-server endpoint on top of EspoCRM's native Lead Capture service.

## Endpoint

~~~text
POST /api/v1/OmniGoCRM/LeadCapture
~~~

Required header:

~~~text
X-OmniGoCRM-Form-Key: <EspoCRM Lead Capture API key>
~~~

Content type:

~~~text
application/json
~~~

The value is the API key of an active EspoCRM Lead Capture record. This keeps lead creation, field validation, duplicate handling, CAPTCHA support, target-list behavior, and future EspoCRM upgrades in the native service.

## Example request

~~~json
{
  "firstName": "Rahul",
  "lastName": "Sharma",
  "emailAddress": "rahul@example.com",
  "phoneNumber": "+919876543210",
  "whatsappNumber": "+919876543210",
  "whatsappOptIn": true,
  "leadSourceDetail": "Website contact form",
  "leadStage": "New",
  "preferredContactChannel": "WhatsApp",
  "campaignName": "September Website",
  "externalLeadId": "website-2026-000123",
  "description": "Requested a quotation."
}
~~~

Common aliases are accepted, including `first_name`, `last_name`, `email`, `mobile`, `whatsapp`, and `external_lead_id`.

## Idempotency

Use `externalLeadId` for every lead coming from a website form, Meta, Google, or another integration.

When the same `externalLeadId` already exists, OmniGoCRM returns:

~~~json
{
  "accepted": true,
  "status": "duplicate",
  "leadId": "existing-lead-id",
  "externalLeadId": "website-2026-000123"
}
~~~

For a new submission:

~~~json
{
  "accepted": true,
  "status": "created",
  "leadId": "new-lead-id",
  "externalLeadId": "website-2026-000123"
}
~~~

## Lead Capture configuration

Create an active EspoCRM Lead Capture record and include every field that you want to persist in its Field List. Recommended OmniGoCRM fields:

- firstName / lastName
- emailAddress
- phoneNumber
- whatsappNumber
- whatsappOptIn
- leadSourceDetail
- leadStage
- leadScore
- nextFollowUpAt
- preferredContactChannel
- campaignName
- externalLeadId
- consentCapturedAt
- description

For a public website form, prefer the native Lead Capture form with CAPTCHA enabled. This endpoint is intended for a website backend, automation service, or other trusted server. Do not put the Lead Capture API key into browser JavaScript.

## Security

The endpoint intentionally has no EspoCRM user login because it is designed for public/server integrations. The Lead Capture API key is therefore treated as a credential.

Do not commit API keys, WhatsApp access tokens, Meta app secrets, or other credentials to the Git repository.

For browser-direct forms with cross-origin requests, OmniGoCRM will use a separate public-form layer with origin controls and rate limiting rather than exposing a long-lived Lead Capture API key to browsers.
