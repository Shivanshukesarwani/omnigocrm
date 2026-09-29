define('module:omni-go-crm/views/home', ['view'], (View) => {
    return class extends View {
        templateContent = `
            <div class="omni-m3-page">
                <header class="omni-m3-appbar">
                    <div class="omni-m3-brand">
                        <div class="omni-m3-logo" aria-hidden="true">OG</div>
                        <div>
                            <div class="omni-m3-brand-name">OmniGoCRM</div>
                            <div class="omni-m3-brand-subtitle">CRM + WhatsApp + Automations</div>
                        </div>
                    </div>
                    <div class="omni-m3-appbar-actions">
                        <a class="omni-m3-icon-button" href="#Notification" title="Notifications" aria-label="Notifications">
                            <span aria-hidden="true">●</span>
                        </a>
                        <a class="omni-m3-avatar" href="#User" title="Profile" aria-label="Profile">U</a>
                    </div>
                </header>

                <main class="omni-m3-content">
                    <section class="omni-m3-hero">
                        <div>
                            <p class="omni-m3-eyebrow">YOUR WORKSPACE</p>
                            <h1>{{workspaceHeading}}</h1>
                            <p class="omni-m3-supporting">
                                Manage customers, conversations, campaigns, sales and automations from one workspace.
                            </p>
                        </div>
                        <div class="omni-m3-workspace-control">
                            {{#if hasWorkspaces}}
                                <label for="omni-workspace-select">Active workspace</label>
                                <select id="omni-workspace-select" class="omni-m3-select" data-action="switch-workspace">
                                    {{#each workspaces}}
                                        <option value="{{id}}"{{#if active}} selected{{/if}}>{{name}}</option>
                                    {{/each}}
                                </select>
                            {{/if}}
                        </div>
                    </section>

                    {{#if showCreateWorkspace}}
                        <section class="omni-m3-card omni-m3-card-highlight">
                            <div class="omni-m3-card-icon">＋</div>
                            <div class="omni-m3-card-body">
                                <h2>Create your first workspace</h2>
                                <p>Create a workspace to unlock CRM records, WhatsApp Inbox, broadcasts, automations and team features.</p>
                                <div class="omni-m3-inline-form">
                                    <input class="omni-m3-input" data-workspace-name placeholder="Workspace name" maxlength="255" />
                                    <button class="omni-m3-button omni-m3-button-filled" data-action="create-workspace">Create workspace</button>
                                </div>
                            </div>
                        </section>
                    {{/if}}

                    {{#if errorMessage}}
                        <section class="omni-m3-card omni-m3-card-error">
                            <strong>Workspace access needs attention</strong>
                            <span>{{errorMessage}}</span>
                            <button class="omni-m3-button omni-m3-button-tonal" data-action="reload-workspaces">Retry</button>
                        </section>
                    {{/if}}

                    <section class="omni-m3-section">
                        <div class="omni-m3-section-header">
                            <div>
                                <h2>Omnichannel workspace</h2>
                                <p>Core WACRM-style tools for your team.</p>
                            </div>
                        </div>
                        <div class="omni-m3-grid omni-m3-grid-features">
                            <a class="omni-m3-feature" href="#WhatsAppConversation">
                                <span class="omni-m3-feature-icon omni-icon-whatsapp">WA</span>
                                <span><strong>WhatsApp Inbox</strong><small>Shared inbox, assignment, replies and media.</small></span>
                                <span class="omni-m3-feature-arrow">→</span>
                            </a>
                            <a class="omni-m3-feature" href="#BroadcastCampaign">
                                <span class="omni-m3-feature-icon">↗</span>
                                <span><strong>Broadcasts</strong><small>Templates, recipients, scheduling and retry.</small></span>
                                <span class="omni-m3-feature-arrow">→</span>
                            </a>
                            <a class="omni-m3-feature" href="#AutomationRule">
                                <span class="omni-m3-feature-icon">⚙</span>
                                <span><strong>Automations</strong><small>Lead, deal, call and WhatsApp workflows.</small></span>
                                <span class="omni-m3-feature-arrow">→</span>
                            </a>
                            <a class="omni-m3-feature" href="#Workspace">
                                <span class="omni-m3-feature-icon">▦</span>
                                <span><strong>Workspaces & Team</strong><small>Members, roles, access and subscriptions.</small></span>
                                <span class="omni-m3-feature-arrow">→</span>
                            </a>
                        </div>
                    </section>

                    <section class="omni-m3-section">
                        <div class="omni-m3-section-header">
                            <div>
                                <h2>CRM</h2>
                                <p>Your everyday customer and sales records.</p>
                            </div>
                        </div>
                        <div class="omni-m3-grid omni-m3-grid-compact">
                            <a class="omni-m3-chip-card" href="#Lead"><span>Lead</span><small>Capture & qualify</small></a>
                            <a class="omni-m3-chip-card" href="#Contact"><span>Contacts</span><small>People & customers</small></a>
                            <a class="omni-m3-chip-card" href="#Account"><span>Accounts</span><small>Companies</small></a>
                            <a class="omni-m3-chip-card" href="#Opportunity"><span>Opportunities</span><small>Pipeline</small></a>
                            <a class="omni-m3-chip-card" href="#Call"><span>Calls</span><small>Phone activity</small></a>
                            <a class="omni-m3-chip-card" href="#Meeting"><span>Meetings</span><small>Appointments</small></a>
                            <a class="omni-m3-chip-card" href="#Quote"><span>Quotes</span><small>Sales documents</small></a>
                            <a class="omni-m3-chip-card" href="#Order"><span>Orders</span><small>Fulfilment</small></a>
                            <a class="omni-m3-chip-card" href="#Payment"><span>Payments</span><small>Collections</small></a>
                        </div>
                    </section>

                    <section class="omni-m3-section">
                        <div class="omni-m3-section-header">
                            <div>
                                <h2>Quick actions</h2>
                                <p>Jump straight into common work.</p>
                            </div>
                        </div>
                        <div class="omni-m3-actions-row">
                            <a class="omni-m3-button omni-m3-button-filled" href="#Lead/create">+ New Lead</a>
                            <a class="omni-m3-button omni-m3-button-tonal" href="#Contact/create">New Contact</a>
                            <a class="omni-m3-button omni-m3-button-tonal" href="#Opportunity/create">New Opportunity</a>
                            <a class="omni-m3-button omni-m3-button-outlined" href="#Workspace">Manage Workspace</a>
                        </div>
                    </section>
                </main>
            </div>
        `;

        setup() {
            this.workspaces = [];
            this.errorMessage = '';
            this.workspaceHeading = 'Choose your workspace';
            this.wait(this.loadWorkspaces());
        }

        data() {
            const active = this.workspaces.find(item => item.active);

            return {
                workspaces: this.workspaces,
                hasWorkspaces: this.workspaces.length > 0,
                showCreateWorkspace: this.workspaces.length === 0,
                workspaceHeading: active ? active.name : this.workspaceHeading,
                errorMessage: this.errorMessage,
            };
        }

        async loadWorkspaces() {
            this.errorMessage = '';

            try {
                const response = await Espo.Ajax.getRequest('OmniGoCRM/Workspace/mine');
                this.workspaces = Array.isArray(response.list) ? response.list : [];

                if (this.workspaces.length === 0) {
                    this.workspaceHeading = 'Create your first workspace';
                    return;
                }

                let active = this.workspaces.find(item => item.active);

                if (!active) {
                    active = this.workspaces[0];

                    try {
                        await Espo.Ajax.postRequest('OmniGoCRM/Workspace/switch', {
                            workspaceId: active.id,
                        });

                        this.workspaces = this.workspaces.map(item => ({
                            ...item,
                            active: item.id === active.id,
                        }));
                    } catch (xhr) {
                        this.errorMessage = this.getErrorMessage(xhr, 'Unable to activate the workspace.');
                    }
                }

                if (active) {
                    this.workspaceHeading = active.name;
                }
            } catch (xhr) {
                this.errorMessage = this.getErrorMessage(xhr, 'Unable to load your workspaces.');
            }
        }

        async createWorkspace() {
            const input = this.$el.find('[data-workspace-name]');
            const name = String(input.val() || '').trim();

            if (!name) {
                Espo.Ui.error('Enter a workspace name.');
                input.trigger('focus');
                return;
            }

            try {
                Espo.Ui.notify('Creating workspace…');

                await Espo.Ajax.postRequest('OmniGoCRM/Workspace/create', {name});

                Espo.Ui.success('Workspace created.');
                await this.loadWorkspaces();
                this.reRender();
            } catch (xhr) {
                Espo.Ui.error(this.getErrorMessage(xhr, 'Workspace could not be created.'));
            }
        }

        async switchWorkspace(workspaceId) {
            if (!workspaceId) return;

            try {
                Espo.Ui.notify('Switching workspace…');
                await Espo.Ajax.postRequest('OmniGoCRM/Workspace/switch', {workspaceId});

                this.workspaces = this.workspaces.map(item => ({
                    ...item,
                    active: item.id === workspaceId,
                }));

                const active = this.workspaces.find(item => item.id === workspaceId);
                this.workspaceHeading = active ? active.name : this.workspaceHeading;

                Espo.Ui.success('Workspace switched.');
                this.reRender();
            } catch (xhr) {
                Espo.Ui.error(this.getErrorMessage(xhr, 'Workspace could not be switched.'));
            }
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

            this.$el.off('change.omniWorkspace', '[data-action="switch-workspace"]');
            this.$el.on('change.omniWorkspace', '[data-action="switch-workspace"]', event => {
                this.switchWorkspace(event.currentTarget.value);
            });

            this.$el.off('click.omniWorkspace', '[data-action="create-workspace"]');
            this.$el.on('click.omniWorkspace', '[data-action="create-workspace"]', () => {
                this.createWorkspace();
            });

            this.$el.off('click.omniWorkspace', '[data-action="reload-workspaces"]');
            this.$el.on('click.omniWorkspace', '[data-action="reload-workspaces"]', async () => {
                await this.loadWorkspaces();
                this.reRender();
            });
        }
    };
});
