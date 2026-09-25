# OmniGoCRM Development Roadmap

## Phase 0 — Foundation
- [x] Existing OmniGoCRM repository retained
- [x] EspoCRM upstream identified
- [x] AGPLv3 compliance plan documented
- [x] OmniGoCRM custom module namespace created
- [x] Vendor EspoCRM source into the development branch
- [x] Build EspoCRM dependencies and validate custom module code in CI
- [x] Docker development environment (EspoCRM + MariaDB baseline)
- [x] CI build/test pipeline

## Phase 1 — Core CRM
- [x] Leads (native EspoCRM Lead + OmniGoCRM fields)
- [x] Contacts (native EspoCRM)
- [x] Accounts/companies (native EspoCRM)
- [x] Opportunities/deals (native EspoCRM)
- [ ] Pipeline
- [x] Tasks (native EspoCRM)
- [ ] Follow-ups
- [x] Notes (native EspoCRM)
- [x] Tags (native EspoCRM)
- [x] Custom fields
- [ ] Duplicate detection
- [x] CSV/Excel import (native EspoCRM + documented OmniGoCRM field mapping)
- [ ] Lead assignment
- [x] Lead source tracking

## Phase 2 — OmniGoCRM Sales Layer
- [ ] Products/services
- [ ] Quotations
- [x] Orders
- [x] Payments
- [ ] Sales dashboard
- [ ] Customer timeline

## Phase 3 — WhatsApp
- [x] WhatsApp provider abstraction (Cloud API service boundary)
- [x] Meta WhatsApp Cloud API outbound text + signed webhook foundation
- [ ] Shared inbox
- [ ] Conversation assignment
- [ ] Templates
- [ ] Media
- [ ] Delivery/read states
- [ ] Broadcasts
- [ ] WhatsApp Flows
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
- [ ] Meta lead integration
- [ ] Google lead integration
- [x] API lead ingestion with externalLeadId idempotency
- [ ] Automatic lead routing

## Phase 6 — Automation
- [ ] Trigger engine
- [ ] Conditions
- [ ] Actions
- [ ] Delays
- [ ] Scheduled jobs
- [ ] Webhooks
- [ ] Assignment rules
- [ ] Follow-up sequences

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
- [ ] Android app
- [ ] iOS app
- [ ] Authentication
- [ ] Leads
- [ ] Contacts
- [ ] Pipeline
- [ ] Tasks
- [ ] Follow-ups
- [ ] WhatsApp
- [ ] Calling
- [ ] Notifications
- [x] Dashboard summary API + native mobile dashboard

## Migration principle

The Laravel prototype is not deleted until the corresponding OmniGoCRM/EspoCRM functionality is implemented and tested.


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
- Full integration/E2E tests against a real EspoCRM + MariaDB instance and real WhatsApp provider sandbox.
