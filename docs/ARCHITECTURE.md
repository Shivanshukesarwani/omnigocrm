# OmniGoCRM Architecture

OmniGoCRM owns its domain model, PostgreSQL schema, API, authentication, authorization, business logic and interfaces. There is no EspoCRM or Laravel runtime dependency.

## Runtime stack
- **Node.js** is the server runtime.
- **Fastify** provides the HTTP API.
- **PostgreSQL + PL/pgSQL** is the system of record. PL/pgSQL handles database-level invariants, timestamps, document totals, invoice payment state and atomic automation-job enqueueing.
- **React + JavaScript (JSX)** powers the web client.
- **Node.js worker** processes asynchronous automation jobs.

Core domains:
- Identity: users, workspaces, members, RBAC, audit logs
- CRM: leads, contacts, accounts, opportunities, pipelines, tasks, notes, tags
- Omnichannel: conversations, messages, calls, campaigns
- Automation: workflows and queued jobs
- Platform: notifications and future integrations/billing

Every business record is workspace-scoped. The active workspace is derived from the signed JWT and verified against workspace membership.

The web app and future native Android/iOS clients use the same versioned API. Provider integrations plug into OmniGoCRM rather than defining its core data model.