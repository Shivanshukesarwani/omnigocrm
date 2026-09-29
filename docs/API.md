# API v1

Base: /api/v1
Auth: Authorization: Bearer TOKEN

## Authentication
POST /auth/register
POST /auth/login
GET /auth/me
POST /auth/switch-workspace

## Workspace
GET /workspaces
POST /workspaces

## CRM CRUD
leads, contacts, accounts, opportunities, tasks, notes
GET POST /resource
GET PATCH DELETE /resource/:id

## Sales
GET POST /pipelines
products, quotes, orders, invoices, payments use the same workspace-scoped CRUD pattern.

## Marketing and automation
campaigns, automations and integrations use the same workspace-scoped CRUD pattern.

## Omnichannel
GET POST /conversations
GET /conversations/:id/messages
POST /conversations/:id/messages

Channels in the core model: WhatsApp, SMS, email, calls and web.

## Analytics and platform
GET /dashboard
GET /reports/summary
GET /notifications
GET /audit-logs
GET /plans

## Lead capture
POST /public/leads/:workspaceSlug

Provider credentials must be kept in external secret storage; the integrations table stores configuration metadata and a secret reference rather than requiring plaintext credentials.