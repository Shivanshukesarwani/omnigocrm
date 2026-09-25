# OmniGoCRM Calling

The calling layer is provider-neutral. OmniGoCRM stores calls in native EspoCRM `Call` records and adds provider/recording metadata.

## Mobile calling

The native Android/iOS clients can use the platform phone dialer for click-to-call. A CRM call record should be created after the provider/platform supplies the real call outcome; the server does not pretend that opening the dialer proves the call was completed.

## Webhook contract

Providers or a small adapter service can POST normalized call events to:

~~~text
POST /api/v1/OmniGoCRM/Calling/webhook
~~~

Header:

~~~text
X-OmniGoCRM-Signature: sha256=<HMAC-SHA256(body, configured secret)>
~~~

Normalized JSON example:

~~~json
{
  "provider": "example-provider",
  "externalCallId": "call-123",
  "direction": "Inbound",
  "status": "missed",
  "from": "+919876543210",
  "to": "+9153...",
  "duration": 0,
  "startedAt": "2026-09-25 18:20:00",
  "recordingStatus": "Not Available"
}
~~~

For a missed inbound call, OmniGoCRM creates a new Lead when no matching phone number exists, with source `Call` and lead-source detail `Missed call`.

## Recording metadata

The Call record stores:

- provider
- external call ID
- recording status
- external recording URL
- recording duration
- recording-consent timestamp
- transcript metadata

The system intentionally does not promise universal Android call recording. Recording availability depends on the telephony provider/device/OS and local requirements.

## Required server setting

Set `omniGoCRMCallingWebhookSecret` and, for providers that create Call records without an authenticated CRM user, set `omniGoCRMCallingDefaultAssignedUserId`.
