# OmniGoCRM

**OmniGoCRM** is an open-source, self-hostable CRM/SaaS platform being built on top of the EspoCRM foundation.

The project is designed around the feature set we want from a TeleCRM-style sales CRM: lead management, follow-ups, WhatsApp, calling, automation, team management, reporting, integrations, SaaS workspaces and mobile apps.

## Current architecture

The project is migrating from the original Laravel prototype to an **EspoCRM-based architecture**.

EspoCRM provides the mature CRM foundation: entities, relationships, ACL, metadata, REST API, layouts, scheduled jobs and extension/module support. OmniGoCRM-specific functionality is being added in custom modules instead of unnecessarily rewriting the core.

See:

- `docs/ESPO_BASE.md`
- `docs/ESPO_MIGRATION.md`
- `docs/OMNIGOCRM_ROADMAP.md`

## Product target

### CRM
- Leads
- Contacts
- Companies/accounts
- Deals/opportunities
- Pipelines
- Tasks
- Follow-ups
- Notes
- Tags
- Custom fields
- Duplicate detection
- Excel/CSV import
- Lead assignment and routing
- Lead-source tracking

### WhatsApp
- Shared inbox
- Official WhatsApp Cloud API
- Templates
- Broadcasts
- Media
- Conversation assignment
- Delivery/read status
- WhatsApp Flows
- Chatbot/automations
- Drip campaigns

### Calling
- Click-to-call
- Incoming/outgoing call logging
- Call disposition
- Call recording metadata
- Provider integrations
- Missed-call lead creation
- IVR integrations

### Automation
- Triggers
- Conditions
- Actions
- Delays
- Scheduled jobs
- Follow-up sequences
- Lead routing
- Webhooks
- API actions

### Sales
- Products/services
- Quotations
- Orders
- Payments
- Customer timeline

### Lead acquisition
- Website forms
- API/webhooks
- Meta lead ingestion
- Google lead ingestion
- Automatic lead assignment

### SaaS
- Organizations/workspaces
- Tenant isolation
- Users
- Roles and permissions
- Subscription plans
- Usage limits
- Billing
- Super-admin
- Audit/security controls

### Mobile
Android and iOS clients will use the same CRM backend and expose the major CRM, follow-up, WhatsApp, calling, notification and dashboard workflows supported by the platform.

## Repository status

The Laravel implementation that existed before the EspoCRM migration is retained temporarily while features are ported and tested. It will only be moved to `legacy/` after equivalent functionality is available and verified.

## Upstream

OmniGoCRM is based on the open-source **EspoCRM** project:

https://github.com/espocrm/espocrm

EspoCRM is licensed under **GNU AGPLv3**. Required upstream license and attribution notices will be preserved. OmniGoCRM additions are maintained separately under the same project compliance requirements.

## Development direction

The target is not merely an EspoCRM rebrand. The goal is a complete **Omnichannel CRM SaaS** with EspoCRM as the reliable CRM engine and OmniGoCRM modules for the additional product requirements.
