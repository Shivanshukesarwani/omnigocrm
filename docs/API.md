# API

Base URL:

```
https://your-crm-domain/api/
```

## Authentication

POST `/login` with email and password. The API returns a bearer token.

Send it as:

```
Authorization: Bearer TOKEN
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
- GET `/follow-ups`
- POST `/follow-ups`
- POST `/calls`
- POST `/calls/{id}/recording`
- GET `/message-templates`
- GET `/whatsapp/{type}/{id}`

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
- POST `/device-tokens`
- DELETE `/device-tokens/{id}`

Database notifications work without Firebase. FCM push delivery is an optional environment-specific integration.

## Security

The API uses workspace-aware route middleware and workspace-scoped foreign-key validation. Android bearer tokens are stored as SHA-256 hashes rather than plain text.
