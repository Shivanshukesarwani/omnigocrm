# OmniGoCRM — Fresh Development

This branch starts the fresh OmniGoCRM development cycle.

## Product direction

OmniGoCRM is an open-source, self-hostable omnichannel CRM/SaaS platform built around EspoCRM as the single CRM/data backend.

The development goal is to build a complete sales CRM covering:

- Lead and contact management
- Sales pipeline and opportunities
- Calling
- WhatsApp
- SMS and email
- Lead acquisition
- Bulk operations
- Automation and workflows
- Follow-ups
- Sales documents and payments
- Reporting and analytics
- Teams and workspaces
- SaaS tenancy and billing
- Android and iOS apps
- APIs and integrations
- Self-hosted and cloud deployment

## Fresh-development rules

1. EspoCRM remains the only CRM/data backend.
2. No Laravel or second CRM backend.
3. New functionality must use the active EspoCRM/OmniGoCRM architecture.
4. Web, Android and iOS clients should use the same backend APIs and business rules.
5. Features are implemented only after their data model, API, permissions, UI and tests are considered.
6. Security, tenant isolation and auditability are first-class requirements.
7. Production deployment must remain Docker/Kubernetes friendly.
8. Existing code is treated as a reference baseline; obsolete or duplicate implementations can be replaced during this development cycle.

## TeleCRM feature reference

The TeleCRM screenshots supplied during development are a requirements reference. They are not treated as a specification to copy verbatim.

Reference feature groups:

- 1-click dialer
- Web click-to-call
- Call recording
- 1-click WhatsApp
- 1-click SMS/email
- WhatsApp notifications
- Excel upload
- Bulk lead editing
- Automatic lead distribution
- Custom API integrations
- Push notifications
- Payment creation
- Hourly reports
- Daily reports
- Leaderboards
- Sales reports
- Agent reports
- Custom reports
- Bulk operations
- Instant welcome messages
- Triggers and workflows
- Technical support

Each reference feature will be mapped against the current OmniGoCRM implementation before development so existing functionality is extended rather than duplicated.

## Development sequence

### Phase 1 — Core CRM foundation
Leads, contacts, accounts, opportunities, pipelines, activities, tasks, follow-ups, tags, custom fields, import/export, assignment and duplicate handling.

### Phase 2 — Communication
WhatsApp, calling, SMS, email, notifications, conversation inbox and communication history.

### Phase 3 — Lead acquisition
Website forms, API capture, Meta, Google, campaigns, attribution and lead routing.

### Phase 4 — Automation
Triggers, conditions, actions, delays, branches, sequences, visual workflow builder and automation monitoring.

### Phase 5 — Sales
Products, quotes, orders, payments, invoices and customer timeline.

### Phase 6 — Reporting
Sales, lead, agent, calling, WhatsApp, conversion, follow-up, hourly/daily and custom reports.

### Phase 7 — Team/SaaS
Workspaces, users, roles, permissions, tenant isolation, plans, quotas, billing, provisioning and super-admin.

### Phase 8 — Mobile
Android and iOS feature parity, push notifications, background sync and offline/conflict handling.

### Phase 9 — Integrations and platform
Public APIs, webhooks, integration builder, developer tools, audit/security controls and deployment tooling.

## Completion standard

A feature is not considered complete merely because a database field or endpoint exists. A production feature should have:

- Backend implementation
- Data model/metadata
- API
- Permission checks
- Web UI where applicable
- Mobile UI where applicable
- Validation/error handling
- Automation hooks where applicable
- Tests
- Documentation
- Production deployment compatibility

## Current branch

Branch: `fresh-development`

The existing `main` branch remains preserved as the starting reference while this fresh development cycle is built.
