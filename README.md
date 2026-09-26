# OmniGoCRM

**OmniGoCRM** is an open-source, self-hostable omnichannel CRM platform for sales teams.

The project is designed around the feature set we want from a TeleCRM-style sales CRM: lead management, follow-ups, WhatsApp, calling, automation, team management, reporting, integrations, SaaS workspaces and mobile apps.

## Current architecture

The platform combines a full-featured CRM backend, web application, custom SaaS services, and native mobile clients.

Architecture and deployment guides:

- `docs/ARCHITECTURE.md`
- `docs/PLATFORM_MIGRATION.md`
- `docs/OMNIGOCRM_ROADMAP.md`
- `docs/WACRM_FEATURE_INTEGRATION.md`

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

The earlier Laravel implementation is retained temporarily while features are ported and tested. It will only be moved to `legacy/` after equivalent functionality is available and verified.

## Upstream attribution

OmniGoCRM is a fork of EspoCRM: https://github.com/espocrm/espocrm

The project is licensed under **GNU AGPLv3**. Required license, copyright, and appropriate legal notices are preserved.
