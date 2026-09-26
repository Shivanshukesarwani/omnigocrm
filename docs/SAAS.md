# OmniGoCRM SaaS / Workspace Foundation

OmniGoCRM uses an explicit Workspace + WorkspaceMember model for SaaS tenancy.

## Workspace

A Workspace stores:

- name and slug
- lifecycle status
- plan
- subscription status
- billing customer/subscription IDs
- trial end date
- owner
- notes

New workspaces created through OmniGoCRM start on the Free plan with a 14-day Trialing status.

## Membership

Workspace members have:

- Owner
- Admin
- Manager
- Agent
- Viewer

Only active memberships can switch a user into a workspace.

The authenticated user's active workspace is stored as `omniGoCRMCurrentWorkspaceId`.

## Tenant-aware records

The current implementation adds `omniGoCRMWorkspaceId` to the core CRM records used by OmniGoCRM:

- Leads
- Contacts
- Accounts
- Opportunities
- Tasks
- Meetings
- Calls
- Products
- WhatsApp Messages
- WhatsApp Conversations
- WhatsApp Templates
- Mobile device registrations

A common save hook assigns the active workspace and refuses changes across workspaces.

Select access-control filters limit list/search results to the active workspace. Read and delete hooks also reject records outside the active workspace.

## Workspace API

Authenticated endpoints:

~~~text
POST /api/v1/OmniGoCRM/Workspace/create
GET  /api/v1/OmniGoCRM/Workspace/mine
POST /api/v1/OmniGoCRM/Workspace/switch
~~~

Create:

~~~json
{
  "name": "Shivanshu Enterprises",
  "slug": "shivanshu-enterprises"
}
~~~

Switch:

~~~json
{
  "workspaceId": "WORKSPACE_ID"
}
~~~

## Platform administrators

The setting `omniGoCRMSaaSAdminBypass` is intentionally explicit. When enabled, CRM administrator accounts can operate across workspace filters. It should stay disabled for normal tenant-facing deployments unless a separate platform-admin operating model is intended.

## Billing

Workspace records already have billing customer/subscription IDs and plan/subscription status, but Stripe/another payment processor is **not** connected yet. Billing webhooks, invoices, plan entitlements, usage metering and payment failure handling remain separate implementation work.

## Important isolation boundary

Do not rely on CRM teams alone as tenant isolation. OmniGoCRM's workspace enforcement is explicit in the record layer and should remain enabled in production.

Any future tenant-aware entity must receive the same workspace field, select filter and read/save/delete enforcement before it becomes available to normal SaaS users.
