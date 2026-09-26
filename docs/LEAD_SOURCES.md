# OmniGoCRM lead-source integrations

## Website forms

Use `POST /api/v1/OmniGoCRM/LeadCapture` with the `X-OmniGoCRM-Form-Key` header. The gateway supports common first/last name, email, mobile, WhatsApp, source, campaign and `externalLeadId` aliases and deduplicates repeated external IDs.

## Meta Lead Ads

Webhook:

```text
GET  /api/v1/OmniGoCRM/LeadSource/meta
POST /api/v1/OmniGoCRM/LeadSource/meta
```

Configure the Meta verify token and app secret. The POST request is verified with `X-Hub-Signature-256`. When Meta sends only a `leadgen_id`, OmniGoCRM retrieves the lead's `field_data` from the Meta Graph API using the configured Meta lead access token. Each imported lead receives an `externalLeadId` beginning with `meta:` so webhook retries are idempotent.

## Google lead ingestion

Webhook:

```text
POST /api/v1/OmniGoCRM/LeadSource/google
```

The endpoint requires `X-OmniGoCRM-Google-Key`. It accepts either a single normalized lead object or a `leads` array. Each external ID is namespaced with `google:` before passing through the same idempotent LeadCaptureGateway.

Google provider-specific field mapping can be expanded without changing the CRM Lead entity.

## Security

Do not commit provider tokens or webhook secrets. Use the server's configuration and secret management in each deployment. Public lead endpoints should also be protected by deployment-level rate limiting, HTTPS and origin controls where applicable.
