define('module:omni-go-crm/views/home', ['view'], (View) => {
    return class extends View {
        templateContent = `
            <div class="omni-m3-page">
                <header class="omni-m3-appbar">
                    <a class="omni-m3-brand" href="#OmniGoCRM" aria-label="OmniGoCRM home">
                        <span class="omni-m3-logo" aria-hidden="true">OG</span>
                        <span>
                            <span class="omni-m3-brand-name">OmniGoCRM</span>
                            <span class="omni-m3-brand-subtitle">Customer relationships, all in one place</span>
                        </span>
                    </a>
                    <div class="omni-m3-appbar-actions">
                        <a class="omni-m3-icon-button" href="#Notification" title="Notifications" aria-label="Notifications">
                            <span aria-hidden="true">●</span>
                        </a>
                    </div>
                </header>

                <main class="omni-m3-content">
                    <section class="omni-m3-hero">
                        <div>
                            <p class="omni-m3-eyebrow">OVERVIEW</p>
                            <h1>Your CRM at a glance</h1>
                            <p class="omni-m3-supporting">
                                Keep customers, conversations, campaigns and sales moving from one connected place.
                            </p>
                        </div>
                        <a class="omni-m3-button omni-m3-button-filled" href="#Lead/create">＋ New lead</a>
                    </section>

                    {{#if errorMessage}}
                        <section class="omni-m3-card omni-m3-card-error" role="alert">
                            <span class="omni-m3-status-icon" aria-hidden="true">!</span>
                            <div class="omni-m3-card-body">
                                <strong>We couldn't load your CRM</strong>
                                <p>{{errorMessage}}</p>
                            </div>
                            <button class="omni-m3-button omni-m3-button-tonal" data-action="reload-dashboard">Try again</button>
                        </section>
                    {{/if}}

                    {{#if isLoading}}
                        <section class="omni-m3-loading" role="status" aria-live="polite">
                            <span class="omni-m3-progress" aria-hidden="true"></span>
                            <span>Preparing your CRM…</span>
                        </section>
                    {{else}}
                        {{#if hasActiveWorkspace}}
                            <section class="omni-m3-metrics" aria-label="CRM summary">
                                <article class="omni-m3-metric omni-m3-metric-primary">
                                    <span class="omni-m3-metric-icon" aria-hidden="true">◎</span>
                                    <span class="omni-m3-metric-label">Leads</span>
                                    <strong>{{metrics.leads}}</strong>
                                    <a href="#Lead">View leads <span aria-hidden="true">→</span></a>
                                </article>
                                <article class="omni-m3-metric">
                                    <span class="omni-m3-metric-icon omni-m3-metric-icon-blue" aria-hidden="true">↗</span>
                                    <span class="omni-m3-metric-label">Opportunities</span>
                                    <strong>{{metrics.opportunities}}</strong>
                                    <a href="#Opportunity">View pipeline <span aria-hidden="true">→</span></a>
                                </article>
                                <article class="omni-m3-metric">
                                    <span class="omni-m3-metric-icon omni-m3-metric-icon-green" aria-hidden="true">✓</span>
                                    <span class="omni-m3-metric-label">Open tasks</span>
                                    <strong>{{metrics.openTasks}}</strong>
                                    <a href="#Task">View tasks <span aria-hidden="true">→</span></a>
                                </article>
                                <article class="omni-m3-metric">
                                    <span class="omni-m3-metric-icon omni-m3-metric-icon-amber" aria-hidden="true">◉</span>
                                    <span class="omni-m3-metric-label">Unread conversations</span>
                                    <strong>{{metrics.unreadWhatsApp}}</strong>
                                    <a href="#WhatsAppConversation">Open inbox <span aria-hidden="true">→</span></a>
                                </article>
                            </section>

                            <section class="omni-m3-section">
                                <div class="omni-m3-section-header">
                                    <div>
                                        <p class="omni-m3-eyebrow">WORKFLOW</p>
                                        <h2>Keep work moving</h2>
                                        <p>Pick up where your team left off.</p>
                                    </div>
                                </div>
                                <div class="omni-m3-grid omni-m3-grid-features">
                                    <a class="omni-m3-feature" href="#WhatsAppConversation">
                                        <span class="omni-m3-feature-icon omni-icon-whatsapp" aria-hidden="true">WA</span>
                                        <span><strong>WhatsApp Inbox</strong><small>Shared conversations, assignments and replies.</small></span>
                                        <span class="omni-m3-feature-arrow" aria-hidden="true">→</span>
                                    </a>
                                    <a class="omni-m3-feature" href="#BroadcastCampaign">
                                        <span class="omni-m3-feature-icon omni-m3-feature-icon-blue" aria-hidden="true">↗</span>
                                        <span><strong>Broadcasts</strong><small>Reach customers with approved templates.</small></span>
                                        <span class="omni-m3-feature-arrow" aria-hidden="true">→</span>
                                    </a>
                                    <a class="omni-m3-feature" href="#AutomationRule">
                                        <span class="omni-m3-feature-icon omni-m3-feature-icon-green" aria-hidden="true">⚙</span>
                                        <span><strong>Automations</strong><small>Turn repeatable tasks into reliable workflows.</small></span>
                                        <span class="omni-m3-feature-arrow" aria-hidden="true">→</span>
                                    </a>
                                    <a class="omni-m3-feature" href="#Quote">
                                        <span class="omni-m3-feature-icon omni-m3-feature-icon-amber" aria-hidden="true">▤</span>
                                        <span><strong>Quotes & orders</strong><small>Move sales from proposal to payment.</small></span>
                                        <span class="omni-m3-feature-arrow" aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </section>

                            <section class="omni-m3-section">
                                <div class="omni-m3-section-header">
                                    <div>
                                        <p class="omni-m3-eyebrow">CUSTOMER RECORDS</p>
                                        <h2>Find your people</h2>
                                        <p>Keep every relationship and next step close at hand.</p>
                                    </div>
                                </div>
                                <div class="omni-m3-grid omni-m3-grid-compact">
                                    <a class="omni-m3-chip-card" href="#Lead"><span>Leads</span><small>Capture & qualify</small></a>
                                    <a class="omni-m3-chip-card" href="#Contact"><span>Contacts</span><small>People & customers</small></a>
                                    <a class="omni-m3-chip-card" href="#Account"><span>Accounts</span><small>Companies</small></a>
                                    <a class="omni-m3-chip-card" href="#Opportunity"><span>Opportunities</span><small>Sales pipeline</small></a>
                                    <a class="omni-m3-chip-card" href="#Call"><span>Calls</span><small>Phone activity</small></a>
                                    <a class="omni-m3-chip-card" href="#Meeting"><span>Meetings</span><small>Appointments</small></a>
                                    <a class="omni-m3-chip-card" href="#Quote"><span>Quotes</span><small>Sales documents</small></a>
                                    <a class="omni-m3-chip-card" href="#Order"><span>Orders</span><small>Fulfilment</small></a>
                                    <a class="omni-m3-chip-card" href="#Payment"><span>Payments</span><small>Collections</small></a>
                                </div>
                            </section>

                            <section class="omni-m3-section omni-m3-quick-actions">
                                <div class="omni-m3-section-header">
                                    <div>
                                        <p class="omni-m3-eyebrow">QUICK ACTIONS</p>
                                        <h2>Make your next move</h2>
                                    </div>
                                </div>
                                <div class="omni-m3-actions-row">
                                    <a class="omni-m3-button omni-m3-button-filled" href="#Lead/create">＋ New lead</a>
                                    <a class="omni-m3-button omni-m3-button-tonal" href="#Contact/create">New contact</a>
                                    <a class="omni-m3-button omni-m3-button-tonal" href="#Opportunity/create">New opportunity</a>
                                    <a class="omni-m3-button omni-m3-button-outlined" href="#Task/create">Add a task</a>
                                </div>
                            </section>
                        {{else}}
                            {{#unless errorMessage}}
                                <section class="omni-m3-empty-state">
                                    <span class="omni-m3-empty-icon" aria-hidden="true">◎</span>
                                    <p class="omni-m3-eyebrow">GETTING THINGS READY</p>
                                    <h2>Your CRM is almost ready</h2>
                                    <p>Customer data will appear here as soon as setup finishes.</p>
                                </section>
                            {{/unless}}
                        {{/if}}
                    {{/if}}
                </main>
            </div>
        `;

        setup() {
            this.workspaces = [];
            this.summary = null;
            this.errorMessage = '';
            this.isLoading = true;
            this.wait(this.loadDashboard());
        }

        data() {
            const active = this.workspaces.find(item => item.active);
            const counts = this.summary && this.summary.counts ? this.summary.counts : {};

            return {
                hasActiveWorkspace: Boolean(active),
                isLoading: this.isLoading,
                errorMessage: this.errorMessage,
                metrics: {
                    leads: this.formatCount(counts.leads),
                    opportunities: this.formatCount(counts.opportunities),
                    openTasks: this.formatCount(counts.openTasks),
                    unreadWhatsApp: this.formatCount(counts.unreadWhatsApp),
                },
            };
        }

        async loadDashboard() {
            this.isLoading = true;
            this.errorMessage = '';

            try {
                const response = await Espo.Ajax.getRequest('OmniGoCRM/Workspace/mine');
                this.workspaces = Array.isArray(response.list) ? response.list : [];

                if (this.workspaces.length === 0) {
                    throw new Error('No active CRM context was returned.');
                }

                if (!this.workspaces.some(item => item.active)) {
                    await Espo.Ajax.postRequest('OmniGoCRM/Workspace/switch', {
                        workspaceId: this.workspaces[0].id,
                    });
                    this.workspaces = this.workspaces.map((item, index) => ({
                        ...item,
                        active: index === 0,
                    }));
                }

                this.summary = await Espo.Ajax.getRequest('OmniGoCRM/Dashboard/summary');
            } catch (xhr) {
                this.workspaces = [];
                this.summary = null;
                this.errorMessage = this.getErrorMessage(xhr, 'Your CRM could not be initialized. Please try again.');
            } finally {
                this.isLoading = false;
            }
        }

        formatCount(value) {
            const count = Number(value);

            return Number.isFinite(count)
                ? new Intl.NumberFormat().format(count)
                : '—';
        }

        getErrorMessage(xhr, fallback) {
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr && xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message) {
                return xhr.responseJSON.error.message;
            }

            return fallback;
        }

        afterRender() {
            super.afterRender();

            this.$el.off('click.omniDashboard', '[data-action="reload-dashboard"]');
            this.$el.on('click.omniDashboard', '[data-action="reload-dashboard"]', () => {
                this.isLoading = true;
                this.errorMessage = '';
                this.reRender();
                this.loadDashboard().then(() => this.reRender());
            });
        }
    };
});
