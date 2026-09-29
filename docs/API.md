# API

Base URL:

```
https://your-crm-domain/api/v1/
```

## Authentication

POST `/login` with email and password. The API returns a bearer token.

Send it using EspoCRM's authorization header:

```
Espo-Authorization: Basic BASE64_USERNAME_COLON_TOKEN
```

All authenticated API requests are scoped to the user's workspace.

## CRM

- GET `/dashboard`
- GET / POST `/leads`
- GET `/leads/{id}`
- POST `/leads/{id}/convert-contact`
- GET `/contacts`
- GET `/contacts/{id}`
- POST `/contacts/{id}/convert-customer`
- GET `/customers`
- GET `/customers/{id}`
- GET `/follow-ups` returns up to 100 follow-up tasks in the active workspace, ordered by due date.
- POST `/follow-ups` creates a follow-up task. Required fields: `name`, `dateStart` (ISO 8601 date/time), `parentType` (`Lead`, `Contact`, `Account`, or `Opportunity`), and `parentId`. Optional fields: `description`, `priority`, and `assignedUserId`; an assignee must be an active member of the workspace.
- POST `/calls`
- POST `/calls/{id}/recording`
- GET `/message-templates`
- GET `/whatsapp/{type}/{id}`

## WhatsApp operations

- POST `/OmniGoCRM/WhatsApp/conversationAction` supports `read`, `close`, `open`, `assign`, and `unassign`. Assignment requires an active workspace member's `assignedUserId`.
- GET `/OmniGoCRM/WhatsApp/conversationMessages?conversationId={id}` returns the active workspace conversation history, including text and media metadata.
- POST `/OmniGoCRM/Broadcast/recipient` adds a workspace lead to a campaign; recipients without WhatsApp opt-in are skipped.
- POST `/OmniGoCRM/Broadcast/schedule` requires an active, approved workspace template and at least one queued recipient. It rejects schedules above the plan's remaining monthly recipient allowance. The dispatcher rechecks template approval, workspace ownership, recipient opt-in, and quota before sending.

Broadcast and automation template components support `{{firstName}}`, `{{lastName}}`, `{{name}}`, and `{{whatsappNumber}}` per recipient.

Lead/contact/customer detail responses include calls, follow-ups and the activity timeline.

## SaaS operations

- GET / POST `/companies`
- GET / POST / PATCH `/tasks`
- GET / POST `/tags`
- GET `/products`
- GET / POST `/quotations`
- GET / POST `/orders`
- GET / POST `/payments`

## Imports

POST `/imports/leads` accepts normalized lead rows for bulk import.

The web Lead Import screen accepts CSV and spreadsheet formats including XLSX/XLS/ODS.

## Notifications and mobile registration

- GET `/notifications`
- POST `/notifications/{id}/read`
- POST `/OmniGoCRM/Devices/register`

Database notifications work without Firebase. Android and iOS FCM delivery are enabled when a server-side Firebase service account is configured; iOS additionally requires APNs configured for the Firebase project and a correctly signed app. See `docs/FCM.md`.

## Security

The API uses workspace-aware route middleware and workspace-scoped foreign-key validation. Android bearer tokens are stored as SHA-256 hashes rather than plain text.
