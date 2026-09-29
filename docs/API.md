# API v1

Base: /api/v1
Auth: Authorization: Bearer TOKEN

Authentication:
POST /auth/register
POST /auth/login
GET /auth/me
POST /auth/switch-workspace

Workspace:
GET /workspaces
POST /workspaces

CRM CRUD:
GET POST /leads
GET PATCH DELETE /leads/:id
GET POST /contacts
GET PATCH DELETE /contacts/:id
GET POST /accounts
GET PATCH DELETE /accounts/:id
GET POST /opportunities
GET PATCH DELETE /opportunities/:id
GET POST /tasks
GET PATCH DELETE /tasks/:id
GET POST /notes
GET PATCH DELETE /notes/:id

Sales:
GET POST /pipelines

Omnichannel:
GET POST /conversations
GET /conversations/:id/messages
POST /conversations/:id/messages

Dashboard:
GET /dashboard
GET /notifications
GET /audit-logs

Lead capture:
POST /public/leads/:workspaceSlug