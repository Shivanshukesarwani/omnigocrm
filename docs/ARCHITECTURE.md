# OmniGoCRM Architecture

OmniGoCRM owns its domain model, database schema, API, authentication, authorization, business logic and interfaces.

There is no EspoCRM or Laravel runtime dependency.

## Monorepo
`apps/api` backend · `apps/web` web · `apps/worker` jobs · `packages/*` shared code · `database` PostgreSQL · `mobile` Android/iOS · `infra` deployment.

## Core domain
Workspace, User, Role, Lead, Contact, Account, Opportunity, Pipeline, Activity, Conversation, Message, Call, Campaign, Automation, Task, Quote, Order, Payment, Notification, Integration and AuditLog.

Every completed feature should include its data model, migration, API, authorization, UI, tests and documentation.