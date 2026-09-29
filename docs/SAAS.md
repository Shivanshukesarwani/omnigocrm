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

Workspace roles are enforced server-side: Viewers cannot write CRM records; Agents cannot delete records, reassign conversations, or schedule broadcasts; Managers, Admins, and Owners can perform those operations. Only Admins and Owners can manage workspace membership.

Only active memberships can switch a user into a workspace.

The authenticated user's active workspace is stored as `omniGoCRMCurrentWorkspaceId`.

## Tenant-aware records

The implementation adds `omniGoCRMWorkspaceId` to the core CRM records used by OmniGoCRM, including automation and billing records:

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

EspoCRM Select access-control filters limit list/search results to the active workspace. Read and delete hooks also reject records outside the active workspace.

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

The setting `omniGoCRMSaaSAdminBypass` is intentionally explicit. When enabled, EspoCRM administrator accounts can operate across workspace filters. It should stay disabled for normal tenant-facing deployments unless a separate platform-admin operating model is intended.

## Billing

Workspace records have plan/subscription status and plan entitlements with lead and broadcast quotas. Signed Stripe and Razorpay subscription webhook handlers now ignore unrelated event types and deduplicate processed deliveries. Hosted checkout, invoice generation, provider price-to-plan configuration, and a complete payment-failure lifecycle remain unfinished. Manual plan changes are restricted to global EspoCRM administrators; workspace owners cannot grant themselves paid tiers through the API.

The webhook accepts Stripe `customer.subscription.created`, `updated`, `deleted`, `paused`, and `resumed`, plus Razorpay `subscription.activated`, `pending`, `halted`, `cancelled`, `paused`, `resumed`, `completed`, and `charged`. New subscription events need `workspaceId` and a valid plan (`Free`, `Starter`, `Business`, or `Enterprise`) in Stripe metadata or Razorpay notes. The provider subscription ID cannot be reassigned to another workspace. Configure only the required event types in each provider dashboard.

## Important isolation boundary

Do not rely on EspoCRM Teams alone as tenant isolation. OmniGoCRM's workspace enforcement is explicit in the record layer and should remain enabled in production.

Any future tenant-aware entity must receive the same workspace field, select filter and read/save/delete enforcement before it becomes available to normal SaaS users.
