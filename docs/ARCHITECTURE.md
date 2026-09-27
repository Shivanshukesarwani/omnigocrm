# OmniGoCRM Architecture

~~~text
                         OmniGoCRM
                             │
              ┌──────────────┴──────────────┐
              │                             │
        EspoCRM backend                Native clients
              │                       Android / iOS
              │
      OmniGoCRM custom module
              │
   ┌──────────┼───────────┬───────────────┐
   │          │           │               │
  CRM      WhatsApp     Calling       Automation
   │          │           │               │
   ├── Leads
   ├── Contacts
   ├── Accounts
   ├── Opportunities
   ├── Tasks
   ├── Meetings
   ├── Products
   ├── Quotes
   ├── Orders
   └── Payments
              │
              ▼
       SaaS / Workspace layer
              │
       Users / Roles / Billing
~~~

## Single backend rule

EspoCRM is the only CRM and data backend. All CRM, SaaS, WhatsApp, calling and automation functionality must use the active EspoCRM/OmniGoCRM architecture.

## Core lifecycle

~~~text
Lead sources → Lead → Assignment → WhatsApp / Calling / Tasks / Follow-up → Deal → Quote → Order → Payment → Customer timeline
~~~

## Tenant boundary

Workspace-aware records carry `omniGoCRMWorkspaceId`. OmniGoCRM hooks and access-control metadata enforce workspace boundaries during list/search, read, create/update, and delete operations.

Every new tenant-aware entity must receive a workspace identifier, workspace-aware filtering, save/update enforcement, read enforcement, delete enforcement, and cross-workspace tests.

## Provider boundary

External integrations are isolated behind provider services/adapters for WhatsApp Cloud API, Meta/Google lead ingestion, telephony, billing, and push notifications. Credentials must never be committed to Git.

## Automation boundary

Inbound events should be acknowledged quickly. Automation execution runs through scheduled jobs where possible, with persistent run state for delayed and resumable actions.
