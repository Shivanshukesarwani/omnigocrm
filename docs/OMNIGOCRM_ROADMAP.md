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
- [ ] Follow-ups
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
- [ ] Broadcast production controls
- [x] WhatsApp Flows
- [x] Workspace-scoped automated replies foundation
- [ ] Drip campaigns

## Phase 4 — Calling
- [ ] Telephony provider abstraction
- [x] Click-to-call
- [ ] Incoming call events
- [ ] Outgoing call events
- [x] Call logs
- [ ] Call disposition
- [ ] Recording metadata
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
- [ ] Webhooks
- [ ] Assignment rules
- [x] Resumable automation runs
- [x] Conditional branches
- [x] Guarded automated WhatsApp actions

## Phase 7 — SaaS
- [ ] Tenant/workspace isolation hardening
- [ ] Organizations
- [ ] Users
- [ ] Roles
- [ ] Permissions
- [ ] Subscription plans
- [ ] Usage limits
- [ ] Billing integration
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
- [ ] Follow-ups
- [ ] WhatsApp
- [x] Calling
- [x] Device registration foundation
- [x] Dashboard summary API + native dashboard
- [ ] Sales documents
- [ ] Broadcasts
- [ ] Offline sync/conflict handling
- [ ] Push delivery

## Remaining before production SaaS launch

- External billing processor webhooks and live checkout.
- Invitation email delivery and workspace/member administration UI.
- Production Meta/Google lead-source adapters.
- Public form security hardening.
- iOS APNs push delivery.
- Native mobile sales document and broadcast screens.
- Offline sync, background refresh and conflict handling.
- PDF quote/invoice generation.
- Production Docker secrets, HTTPS/reverse proxy, backup/restore and migration runbooks.
- Full integration/E2E tests against real EspoCRM + MariaDB and a WhatsApp provider sandbox.

The active architecture is EspoCRM + OmniGoCRM custom modules + native clients.
