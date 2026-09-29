# WACRM feature integration plan

This plan ports product capabilities from [ArnasDon/wacrm](https://github.com/ArnasDon/wacrm) into OmniGoCRM without importing its source code or replacing OmniGoCRM's current PHP, JavaScript, and Kotlin stack.

## Feature map

| WACRM capability | OmniGoCRM status | Integration direction |
| --- | --- | --- |
| CRM contacts, tags, custom fields, import, and sales pipeline | Core CRM capabilities already exist in the repository's CRM platform. | Reuse the existing CRM data model and access controls. |
| Shared WhatsApp inbox | Conversation and message entities, signed inbound webhooks, read/open/close actions, unread counts, workspace-safe assignment, internal notes, authenticated media retrieval, image previews, and mobile audio/document open/share flows exist. | Add response-time metrics and complete inbox workflows in Phase 3. |
| Template broadcasts | Campaigns, recipients, approved-template sending, dispatch-time opt-in checks, delivery status storage, per-recipient template variables, live recipient-state counts, and bounded manual retry for failed recipients exist. | Continue with richer campaign controls and opt-out handling in Phase 3. |
| No-code automation | Queued inbound WhatsApp rules, grouped conditions, branches, waits, workspace checks, resumable runs, tasks, record updates, and guarded WhatsApp replies are implemented. | Phase 1 is complete. Phase 2 adds a visual builder and more triggers/actions. |
| Team accounts and roles | Workspace membership and role-management APIs exist. | Align inbox assignment and team workflows with the existing workspace model in Phase 3. |
| Dashboard and activity metrics | Workspace dashboard includes 30-day inbound/outbound volume and daily series, open/unassigned/waiting inbox counts, average tracked latest reply time, and per-active-member workload and reply metrics attributed to each conversation's current assignee. Reply and backlog tracking starts on new inbound events; historical conversations are not backfilled. | Extend reporting with team and longer-range comparisons in Phase 3. |
| Public API | The CRM has its own REST API; a WACRM-style scoped public-key layer is not present. | Audit existing API authentication before adding least-privilege integration credentials in Phase 4. |

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

- Add daily inbound/outbound volume for the rolling 30-day dashboard window. Per-assignee workload and reply metrics are included in dashboard summaries; mobile dashboards show the latest seven daily points.
- Render approved template variables per recipient (implemented through `POST /OmniGoCRM/Broadcast/recipient` using a `variables` object and `{{key}}` placeholders); manual failed-recipient retry is available at `POST /OmniGoCRM/Broadcast/retryRecipient`, limited to three retries, rechecked against workspace membership, current opt-in, approved template, and quota. `GET /OmniGoCRM/Broadcast/campaign` returns live queued/sent/failed/skipped counts. Retries are explicit because a provider timeout can leave delivery outcome uncertain; a retry can duplicate a message accepted by the provider before the timeout.

### Phase 4 — Public integrations

- Audit the existing CRM API authentication and add revocable, least-privilege integration credentials only where needed.
- Document deployment, credential rotation, auditing, and workspace isolation.

## Current implementation checkpoint

Phase 1 is complete. Inbound webhooks queue `WhatsAppReceived` runs, and the server-side runner supports action snapshots, wait/recovery, branches, grouped conditions, workspace isolation, and guarded WhatsApp replies. Focused PHPUnit coverage and CI syntax/module validation cover this implementation. The visual builder and additional triggers remain later phases. Validate against a live Meta webhook/send setup before production use.
