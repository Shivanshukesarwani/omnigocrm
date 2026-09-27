# OmniGoCRM — EspoCRM Base

OmniGoCRM is built on the EspoCRM application and keeps OmniGoCRM-specific functionality in custom modules.

## Upstream

- Upstream project: https://github.com/espocrm/espocrm
- Upstream license: GNU AGPLv3
- Development model: maintain an upstream-compatible fork and keep OmniGoCRM functionality in custom modules where practical.

EspoCRM supports maintaining customized modules and merging upstream changes while producing production builds from controlled artifacts.

## Backend rule

Do not rewrite EspoCRM core unnecessarily.

OmniGoCRM functionality should live primarily in:

- `custom/Espo/Modules/OmniGoCRM/` for backend/module metadata
- `client/custom/modules/omni-go-crm/` for frontend code
- `docs/` for architecture and deployment
- `android/` and `ios/` for native clients

EspoCRM is the sole active CRM/data backend.

## Target product

OmniGoCRM will combine EspoCRM's CRM foundation with:

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

## Important licensing

EspoCRM is AGPLv3. OmniGoCRM must preserve required notices and comply with the AGPL when distributing or providing network access to modified versions.

The OmniGoCRM name, branding, custom modules and original additions are separate project work; upstream EspoCRM notices must remain intact where required.
