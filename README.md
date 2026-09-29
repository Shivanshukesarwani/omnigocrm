# OmniGoCRM

Open-source, self-hostable omnichannel CRM built entirely by OmniGoCRM.

Independent platform: no EspoCRM, no Laravel, and no other CRM backend.

## Included
- Authentication and workspace isolation
- RBAC: owner, admin, manager, agent, viewer
- Leads, contacts, accounts, opportunities and pipelines
- Tasks, notes, tags and audit logs
- Conversations and messages
- Notifications
- Public lead capture API
- React web dashboard
- Background worker foundation
- Docker and Kubernetes deployment
- PostgreSQL database and versioned API

## Development
1. Copy .env.example to .env and set JWT_SECRET.
2. Start PostgreSQL/Redis: docker compose -f infra/docker/docker-compose.dev.yml up -d postgres redis
3. Install: pnpm install
4. Migrate: pnpm db:migrate
5. Start: pnpm dev

API: http://localhost:3000
Web: http://localhost:5173

The first registered account creates a workspace and becomes its owner.

See docs/ARCHITECTURE.md, docs/API.md, docs/SECURITY.md and docs/DEPLOYMENT.md.