# OmniGoCRM Development Roadmap

## Phase 0 — Foundation
- [x] Existing OmniGoCRM repository retained
- [x] EspoCRM upstream identified
- [x] AGPLv3 compliance plan documented
- [x] OmniGoCRM custom module namespace created
- [ ] Vendor EspoCRM source into the development branch
- [ ] Build EspoCRM successfully
- [ ] Docker development environment
- [ ] CI build/test pipeline

## Phase 1 — Core CRM
- [ ] Leads
- [ ] Contacts
- [ ] Accounts/companies
- [ ] Opportunities/deals
- [ ] Pipeline
- [ ] Tasks
- [ ] Follow-ups
- [ ] Notes
- [ ] Tags
- [ ] Custom fields
- [ ] Duplicate detection
- [ ] CSV/Excel import
- [ ] Lead assignment
- [ ] Lead source tracking

## Phase 2 — OmniGoCRM Sales Layer
- [ ] Products/services
- [ ] Quotations
- [ ] Orders
- [ ] Payments
- [ ] Sales dashboard
- [ ] Customer timeline

## Phase 3 — WhatsApp
- [ ] WhatsApp provider abstraction
- [ ] Meta WhatsApp Cloud API
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
- [ ] Click-to-call
- [ ] Incoming call events
- [ ] Outgoing call events
- [ ] Call logs
- [ ] Call disposition
- [ ] Recording metadata
- [ ] Provider-hosted recordings
- [ ] Missed-call lead creation
- [ ] IVR integration

## Phase 5 — Lead Acquisition
- [ ] Public lead forms
- [ ] Website webhook
- [ ] Meta lead integration
- [ ] Google lead integration
- [ ] API lead ingestion
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
- [ ] Dashboard

## Migration principle

The Laravel prototype is not deleted until the corresponding OmniGoCRM/EspoCRM functionality is implemented and tested.
