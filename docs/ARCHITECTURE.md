# OmniGoCRM Architecture

The platform is organized around a mature CRM backend with OmniGoCRM-specific functionality implemented in custom modules.

## Application areas

- `application/` and `client/` contain the CRM backend and web client.
- `custom/Espo/Modules/OmniGoCRM/` contains OmniGoCRM backend modules and metadata.
- `client/custom/modules/omni-go-crm/` contains custom web-client code.
- `android/` and `ios/` contain native mobile clients.
- `backend/` and `frontend/` contain the earlier prototype while features are migrated.

Keep CRM core changes focused; implement product-specific behavior in custom modules where practical.

## Product capabilities

- core CRM entities, relationships, access control, metadata, REST API, layouts, and scheduled jobs
- multi-tenant SaaS/workspaces
- lead and contact management
- Excel/CSV bulk import
- lead assignment and routing
- follow-ups and tasks
- WhatsApp shared inbox
- official WhatsApp Cloud API integration
- templates and broadcasts
- WhatsApp automation and drip campaigns
- calling and call activity
- call recording where supported by the telephony provider/device
- missed-call lead creation
- website/landing-page forms
- Meta/Google lead ingestion
- products, quotations, orders and payments
- dashboards and reports
- API/webhooks
- Android and iOS clients with feature parity for supported CRM workflows

## Licensing

The project is licensed under AGPLv3. Preserve all required license, copyright, and appropriate legal notices when modifying or distributing the application.
