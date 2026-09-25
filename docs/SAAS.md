# OmniGoCRM SaaS architecture

The product uses a workspace model so multiple businesses can use one Laravel application while keeping CRM records separated by workspace.

## Roles

- super_admin
- admin
- manager
- sales

## Business modules

- Companies
- Leads, contacts and customers
- Tags
- Follow-ups and tasks
- Products and services
- Quotations
- Orders / sales
- Payments
- Audit log
- Custom fields
- Bulk lead import API

## Mobile parity

The Android client shares the same API and can access the dashboard, leads, contacts, customers, follow-ups, companies, tasks, products, quotations, orders, payments, WhatsApp and calling.

## WhatsApp

The initial integration is click-to-chat with editable situation-based templates. It does not automate WhatsApp Web and does not use a Chrome extension.

## Call recordings

Recordings are stored in private application storage and the web playback route is restricted to admin roles. Android cellular-call recording remains device/OEM/carrier dependent.
