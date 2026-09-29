# OmniGoCRM Development Roadmap

## Phase 0 — Foundation
- [x] Existing OmniGoCRM repository retained
- [x] EspoCRM upstream identified
- [x] AGPLv3 compliance plan documented
- [x] OmniGoCRM custom module namespace created
- [x] Vendor EspoCRM source into the development branch
- [x] Build EspoCRM dependencies and validate custom module code in CI
- [x] Docker development environment
- [x] CI build/test pipeline
- [x] Remove obsolete secondary CRM backend

## Phase 1 — Core CRM
- [x] Leads
- [x] Contacts
- [x] Accounts/companies
- [x] Opportunities/deals
- [x] Pipeline
- [x] Tasks
- [x] Follow-ups (workspace-scoped task API and native mobile list/create)
- [x] Notes
- [x] Tags
- [x] Custom fields
- [x] externalLeadId idempotency
- [x] CSV/Excel import
- [x] Lead assignment metadata foundation
- [x] Lead source tracking

## Phase 2 — Sales
- [x] Products/services
- [x] Quotations
- [x] Orders
- [x] Payments
- [x] Sales dashboard
- [x] Customer timeline

## Phase 3 — WhatsApp
- [x] WhatsApp provider abstraction
- [x] Meta WhatsApp Cloud API outbound text + signed webhook foundation
- [x] Shared inbox conversation model
- [x] Conversation assignment
- [x] Templates + approval-gated sending
- [x] Media metadata/webhook ingestion
- [x] Delivery/read states
- [x] Approved-template preflight, workspace-safe recipients, and monthly plan quotas
- [x] WhatsApp Flows
- [x] Workspace-scoped automated replies foundation
- [x] Drip-style WhatsApp sequences through workspace automation wait/template actions

## Phase 4 — Calling
- [x] Provider-neutral signed call-event webhook contract
- [x] Click-to-call
- [x] Incoming call events
- [x] Outgoing call events
- [x] Call logs
- [x] Call disposition
- [x] Recording metadata
- [ ] Provider-hosted recordings
- [x] Missed-call lead creation
- [ ] IVR integration

## Phase 5 — Lead Acquisition
- [x] Public lead forms
- [x] Website/server lead capture
- [x] Meta lead webhook + Graph adapter
- [x] Google lead webhook adapter
- [x] API lead ingestion with externalLeadId idempotency
- [x] Lead assignment metadata foundation

## Phase 6 — Automation
- [x] Trigger engine
- [x] Conditions
- [x] Actions
- [x] Delays
- [x] Scheduled jobs
- [x] Signed outbound webhooks with administrator host allowlist
- [x] Workspace-scoped automation assignment rules
- [x] Resumable automation runs
- [x] Conditional branches
- [x] Guarded automated WhatsApp actions

## Phase 7 — SaaS
- [ ] Tenant/workspace isolation hardening
- [ ] Organizations
- [ ] Users
- [ ] Roles
- [ ] Permissions (workspace role write/delete, membership deletion, conversation assignment and broadcast scheduling checks exist; full entity/field ACL policy remains)
- [ ] Subscription plans
- [ ] Usage limits
- [ ] Billing integration (signed subscription webhooks and event deduplication exist; live checkout, price mapping and invoice lifecycle remain)
- [ ] Super-admin console
- [ ] Tenant provisioning
- [ ] Audit/security controls

## Phase 8 — Mobile
- [x] Android app
- [x] iOS app
- [x] Authentication
- [x] Leads
- [x] Contacts
- [x] Pipeline
- [x] Tasks
- [x] Follow-ups
- [x] WhatsApp conversation inbox, thread history, and consent-gated replies
- [x] Calling
- [x] Device registration foundation
- [x] Dashboard summary API + native dashboard
- [x] Native quote, order, and payment workflows on Android and iOS
- [x] Native WhatsApp broadcast campaign, recipient, and scheduling workflows on Android and iOS
- [ ] Offline sync/conflict handling
- [x] Push delivery (Android and iOS FCM, with APNs routed through Firebase)

## Remaining before production SaaS launch

- External billing processor webhooks and live checkout.
- Invitation email delivery and workspace/member administration UI.
- Production Meta/Google lead-source adapters.
- Public form request-size and origin enforcement (edge rate limiting remains a production requirement).
- Native mobile sales document workflows.
- Offline sync, background refresh and conflict handling.
- PDF quote/invoice generation.
- Production Docker secrets, HTTPS/reverse proxy, backup/restore and migration runbooks.
- Full integration/E2E tests against real EspoCRM + MariaDB and a WhatsApp provider sandbox.

The active architecture is EspoCRM + OmniGoCRM custom modules + native clients.
