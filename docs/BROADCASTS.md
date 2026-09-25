# OmniGoCRM WhatsApp Broadcasts

OmniGoCRM broadcasts are implemented as workspace-scoped campaign records plus recipient records, processed by an EspoCRM scheduled job.

## Flow

1. Create an active Approved `WhatsAppTemplate`.
2. Create a `BroadcastCampaign` with the template name, language and optional template components JSON.
3. Add leads as `BroadcastRecipient` records.
4. Schedule the campaign.
5. The `DispatchBroadcastCampaigns` system job runs every minute and processes at most 100 queued recipients per campaign per run.
6. Each recipient must have `whatsappOptIn=true` on the Lead.
7. Successful sends create normal `WhatsAppMessage` records and update the WhatsApp conversation.
8. Failed or skipped recipients retain an error/status on their recipient record.
9. The campaign is marked Completed after all queued recipients have been processed.

## APIs

~~~text
POST /api/v1/OmniGoCRM/Broadcast/recipient
POST /api/v1/OmniGoCRM/Broadcast/schedule
~~~

Recipient example:

~~~json
{
  "campaignId": "CAMPAIGN_ID",
  "leadId": "LEAD_ID"
}
~~~

Schedule example:

~~~json
{
  "campaignId": "CAMPAIGN_ID",
  "scheduledAt": "2026-09-26 10:00:00"
}
~~~

The scheduler intentionally caps processing per run so a large audience is spread across multiple runs. It does not bypass the CRM opt-in field.

## Drip campaigns

The broadcast model is the base layer for future drip sequences. A future automation entity can chain multiple BroadcastCampaigns by relative delays while reusing the same recipient and opt-in safeguards.
