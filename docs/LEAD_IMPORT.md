# OmniGoCRM Lead Import

OmniGoCRM uses the native CSV/XLSX import engine and adds CRM-specific lead fields.

## Supported lead data

The Lead entity supports the standard CRM fields plus:

- WhatsApp Number
- WhatsApp Opt-In
- Lead Source Detail
- Lead Score (0-100)
- Sales Stage
- Next Follow-Up
- Preferred Contact Channel
- Campaign Name
- External Lead ID
- Consent Captured At

## Recommended CSV columns

firstName,lastName,emailAddress,phoneNumber,whatsappNumber,whatsappOptIn,leadSourceDetail,leadScore,leadStage,nextFollowUpAt,preferredContactChannel,campaignName,externalLeadId,description

The native import wizard can map these columns without requiring a separate spreadsheet service.

## Duplicate handling

For external integrations, populate externalLeadId with the source-system identifier. The application layer will use this field for idempotent ingestion as Meta/Google/website integrations are added.

## Data protection

Do not import passwords, API tokens, access tokens, or other secrets into Lead records.
