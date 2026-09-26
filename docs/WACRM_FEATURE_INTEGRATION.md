# WACRM feature integration plan

This plan ports product capabilities from [ArnasDon/wacrm](https://github.com/ArnasDon/wacrm) into OmniGoCRM without importing its source code or replacing OmniGoCRM's current PHP, JavaScript, and Kotlin stack.

## Feature map

| WACRM capability | OmniGoCRM status | Integration direction |
| --- | --- | --- |
| CRM contacts, tags, custom fields, import, and sales pipeline | Core CRM capabilities already exist in the repository's CRM platform. | Reuse the existing CRM data model and access controls. |
| Shared WhatsApp inbox | Conversation and message entities, signed inbound webhooks, read/open/close actions, unread counts, and assignment fields exist. | Complete assignment, internal notes, media access, and inbox workflows in Phase 3. |
| Template broadcasts | Campaigns, recipients, approved-template sending, opt-in checks, and delivery status storage exist. | Add per-recipient template variable rendering and stronger campaign controls in Phase 3. |
| No-code automation | Queued inbound WhatsApp rules, grouped conditions, branches, waits, workspace checks, resumable runs, tasks, record updates, and guarded WhatsApp replies are implemented. | Phase 1 is complete. Phase 2 adds a visual builder and more triggers/actions. |
| AI reply assistant and knowledge base | Not implemented. | Add optional, administrator-configured provider keys and workspace-scoped retrieval in Phase 4. |
| Team accounts and roles | Workspace membership and role-management APIs exist. | Align inbox assignment and team workflows with the existing workspace model in Phase 3. |
| Dashboard and activity metrics | A dashboard summary and customer timeline API exist. | Add response-time and inbox activity metrics with the inbox work in Phase 3. |
| Public API and MCP | The CRM has its own REST API; a WACRM-style scoped public-key layer and MCP server are not present. | Audit existing API authentication before extending it; build optional read-first MCP integration in Phase 5. |

## Delivery phases

### Phase 1 — WhatsApp automation execution

- Queue matching inbound-triggered rules; provider actions run in the scheduled automation job, not in the webhook request.
- Evaluate exact and text conditions, grouped `all`/`any` conditions, and case-insensitive operators.
- Support conditional branches and resumable wait steps. Persist action snapshots and progress; runs interrupted for more than 15 minutes are re-queued from their last checkpoint.
- Keep outbound text inside WhatsApp's 24-hour service window; require workspace-matched opt-in and an active, approved template for template sends. Template language selection must also be approved.
- Enforce workspace equality for trigger records, task parents, WhatsApp recipients, templates, and conversations; automation actions cannot change record workspace ownership.
- Failed actions remain inspectable as failed runs. Recovery is at-least-once for an action interrupted before its checkpoint; administrators should account for possible repeated side effects.

### Phase 2 — No-code automation authoring

- Add a native OmniGoCRM visual automation builder in the existing JavaScript client.
- Add keyword, record lifecycle, and scheduled triggers through supported CRM hooks/jobs.
- Add safe tag and webhook actions, with URL and network-target validation to prevent server-side request forgery.
- Make action schemas and previews available in the editor; retain JSON as a stable API representation.

### Phase 3 — Shared inbox and campaign workflows

- Add workspace-safe assignment and internal conversation notes.
- Complete supported media retrieval and display without slowing webhook acknowledgement.
- Render approved template variables per recipient and improve campaign progress, retry, and opt-out handling.
- Add team response-time and inbox-volume reporting to existing dashboard APIs.

### Phase 4 — AI reply assistance and knowledge base

- Provide optional bring-your-own-key providers; AI features remain disabled until configured.
- Add workspace-scoped knowledge articles and retrieval, with citations in generated drafts.
- Start with agent-approved reply drafts; add automatic replies only with explicit opt-in, bounded retries, and human handoff.
- Never send conversation or knowledge data to an AI provider without administrator configuration and user-visible controls.

### Phase 5 — Public integrations

- Audit the existing CRM API authentication and add revocable, least-privilege integration credentials only where needed.
- Add an optional MCP server that is read-only by default; writes require explicit enablement and scoped permissions.
- Document deployment, credential rotation, auditing, and workspace isolation.

## Current implementation checkpoint

Phase 1 is complete. Inbound webhooks queue `WhatsAppReceived` runs, and the server-side runner supports action snapshots, wait/recovery, branches, grouped conditions, workspace isolation, and guarded WhatsApp replies. Focused PHPUnit coverage and CI syntax/module validation cover this implementation. The visual builder, additional triggers, AI, and MCP remain later phases. Validate against a live Meta webhook/send setup before production use.
