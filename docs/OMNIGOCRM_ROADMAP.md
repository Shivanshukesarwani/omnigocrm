# OmniGoCRM Development Roadmap

## Phase 0 — Foundation
- [x] Existing OmniGoCRM repository retained
- [x] Core CRM platform selected
- [x] AGPLv3 compliance plan documented
- [x] OmniGoCRM custom module namespace created
- [x] Add the CRM platform source to the development branch
- [x] Build platform dependencies and validate custom module code in CI
- [x] Docker development environment (CRM + MariaDB baseline)
- [x] CI build/test pipeline

## Phase 1 — Core CRM
- [x] Leads (native Lead + OmniGoCRM fields)
- [x] Contacts (native Contact)
- [x] Accounts/companies (native Account)
- [x] Opportunities/deals (native Opportunity)
- [x] Pipeline
- [x] Tasks (native Task)
- [ ] Follow-ups
- [x] Notes (native Note)
- [x] Tags (native Tag)
- [x] Custom fields
- [x] externalLeadId idempotency
- [x] CSV/Excel import (native import + documented OmniGoCRM field mapping)
- [x] Lead assignment metadata foundation
- [x] Lead source tracking

## Phase 2 — OmniGoCRM Sales Layer
- [x] Products/services
- [x] Quotations
- [x] Orders
- [x] Payments
- [x] Sales dashboard
- [x] Customer timeline

## Phase 3 — WhatsApp
- [x] WhatsApp provider abstraction (Cloud API service boundary)
- [x] Meta WhatsApp Cloud API outbound text + signed webhook foundation
- [x] Shared inbox conversation model + unread/read/close actions
- [x] Conversation assignment metadata
- [x] Templates + approval-gated sending
- [x] Media metadata/webhook ingestion
- [x] Delivery/read states
- [ ] Broadcasts
- [x] WhatsApp Flows
- [ ] Automated replies
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
- [x] Public lead forms (Espo native Lead Capture + OmniGoCRM gateway)
- [x] Website/server lead-capture endpoint
- [x] Meta lead webhook + Graph lead retrieval adapter
- [x] Google lead webhook adapter
- [x] API lead ingestion with externalLeadId idempotency
- [x] Lead assignment metadata foundation

## Phase 6 — Automation
- [x] Trigger engine
- [x] Conditions
- [x] Actions (task creation + record updates)
- [x] Delays
- [x] Scheduled jobs
- [ ] Webhooks
- [ ] Assignment rules
- [x] Automation runs can create follow-up tasks

## Phase 7 — SaaS
- [ ] Tenant/workspace isolation
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
- [x] Dashboard summary API + native mobile dashboard

## Migration principle

The Laravel prototype is not deleted until the corresponding OmniGoCRM functionality is implemented and tested.


## Remaining before production SaaS launch

The repository is substantially implemented, but these items still require final integration/testing before calling the product production-complete:

- External billing processor webhooks and live checkout (Stripe/Razorpay/etc.).
- Invitation email delivery and polished workspace/member administration UI.
- Meta/Google lead-source adapters beyond the generic lead-capture API.
- Public form security hardening (rate limiting, origin controls and deployment-key strategy).
- iOS APNs device registration/push delivery.
- Native mobile sales document and broadcast management screens.
- Offline sync, background refresh and conflict handling.
- PDF quote/invoice generation and production document templates.
- Production Docker secrets, HTTPS/reverse-proxy, backup/restore and migration runbooks.
- Full integration/E2E tests against a real CRM + MariaDB instance and real WhatsApp provider sandbox.


## Implementation status — 2026-09-27

The active CRM branch includes the shared WhatsApp conversation/inbox layer, media metadata capture, approved template sending, sales dashboard metrics, customer timeline API, Meta Lead Ads webhook/Graph retrieval adapter, Google lead ingestion adapter, and a scheduled automation engine. WhatsApp-triggered automation is being extended with queued, resumable runs, condition groups, branches, waits, and guarded replies. The WACRM feature comparison and phased integration plan is tracked in `docs/WACRM_FEATURE_INTEGRATION.md`; the visual builder, AI/knowledge base, expanded inbox and API/MCP integrations remain later phases. Production billing checkout, push delivery, offline sync, and deployment hardening remain integration work rather than placeholder claims.
