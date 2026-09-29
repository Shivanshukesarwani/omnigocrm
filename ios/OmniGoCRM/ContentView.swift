import SwiftUI
import Combine
import UIKit
import UserNotifications

struct RootView: View {
    @ObservedObject var session: SessionStore
    let pushDelegate: FCMPushAppDelegate

    var body: some View {
        if session.isAuthenticated {
            DashboardView(session: session, pushDelegate: pushDelegate)
        } else {
            LoginView(session: session)
        }
    }
}

struct LoginView: View {
    @ObservedObject var session: SessionStore
    @State private var serverURL = ""
    @State private var username = ""
    @State private var password = ""
    @State private var busy = false
    @State private var error = ""

    var body: some View {
        NavigationStack {
            Form {
                Section("OmniGoCRM") {
                    TextField("CRM server URL", text: $serverURL)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                    TextField("Username or email", text: $username)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                    SecureField("Password", text: $password)
                    Button(busy ? "Signing in…" : "Sign in") {
                        busy = true
                        error = ""
                        Task {
                            do {
                                let api = APIClient(session: session)
                                try await api.login(
                                    baseURL: serverURL,
                                    username: username,
                                    password: password
                                )
                            } catch {
                                self.error = error.localizedDescription
                            }
                            busy = false
                        }
                    }
                    .disabled(busy || username.isEmpty || password.isEmpty)
                }

                if !error.isEmpty {
                    Text(error).foregroundStyle(.red)
                }
            }
            .navigationTitle("OmniGoCRM")
            .onAppear {
                if serverURL.isEmpty {
                    serverURL = session.baseURL
                }
            }
        }
    }
}

struct DashboardView: View {
    @ObservedObject var session: SessionStore
    let pushDelegate: FCMPushAppDelegate
    @State private var selected = 0
    @State private var showWorkspace = false
    @State private var workspaceName = ""
    @State private var workspaceError = ""

    var body: some View {
        TabView(selection: $selected) {
            DashboardHomeView(session: session) {
                showWorkspace = true
            }
            .tabItem { Label("Dashboard", systemImage: "rectangle.3.group") }
            .tag(0)

            RecordListView(session: session, title: "Leads", entityType: "Lead", select: "id,firstName,lastName,accountName,phoneNumber,whatsappNumber,status,leadStage,whatsappOptIn", kind: .leads)
                .tabItem { Label("Leads", systemImage: "person.2") }
                .tag(1)

            RecordListView(session: session, title: "Contacts", entityType: "Contact", select: "id,name,phoneNumber,emailAddress", kind: .generic)
                .tabItem { Label("Contacts", systemImage: "person.crop.circle") }
                .tag(2)

            RecordListView(session: session, title: "Accounts", entityType: "Account", select: "id,name,phoneNumber,emailAddress", kind: .generic)
                .tabItem { Label("Accounts", systemImage: "building.2") }
                .tag(3)

            RecordListView(session: session, title: "Tasks", entityType: "Task", select: "id,name,status,priority,dateEnd", kind: .tasks)
                .tabItem { Label("Tasks", systemImage: "checklist") }
                .tag(4)

            FollowUpListView(session: session)
                .tabItem { Label("Follow-ups", systemImage: "calendar.badge.clock") }
                .tag(5)

            RecordListView(session: session, title: "WhatsApp", entityType: "WhatsAppConversation", select: "id,name,waId,phoneNumber,customerDisplayName,status,unreadCount,lastMessageAt,lastMessagePreview,leadId,contactId", kind: .whatsappConversations)
                .tabItem { Label("WhatsApp", systemImage: "message") }
                .tag(6)

            SalesHubView(session: session)
                .tabItem { Label("Sales", systemImage: "doc.text") }
                .tag(7)

            BroadcastsView(session: session)
                .tabItem { Label("Broadcasts", systemImage: "megaphone") }
                .tag(8)
        }
        .task {
            await ensureWorkspace()
            await registerCurrentPushToken()
        }
        .onChange(of: workspaceName) { _, newValue in
            guard !newValue.isEmpty else { return }
            Task { await registerCurrentPushToken() }
        }
        .onReceive(NotificationCenter.default.publisher(for: FCMPushAppDelegate.tokenDidChange)) { notification in
            guard let token = notification.object as? String else { return }
            Task { await registerPushToken(token) }
        }
        .sheet(isPresented: $showWorkspace) {
            WorkspaceChooserView(session: session) {
                workspaceName = $0
            }
        }
        .alert("Workspace", isPresented: Binding(
            get: { !workspaceError.isEmpty },
            set: { if !$0 { workspaceError = "" } }
        )) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(workspaceError)
        }
    }

    private func ensureWorkspace() async {
        do {
            let list = try await APIClient(session: session).myWorkspaces()

            if list.isEmpty {
                showWorkspace = true
                return
            }

            if let active = list.first(where: { ($0["active"] as? Bool) == true }) {
                workspaceName = active["name"] as? String ?? ""
                return
            }

            if let first = list.first, let id = first["id"] as? String {
                try await APIClient(session: session).switchWorkspace(id: id)
                workspaceName = first["name"] as? String ?? ""
            }
        } catch {
            workspaceError = error.localizedDescription
        }
    }

    private func registerCurrentPushToken() async {
        guard !workspaceName.isEmpty, await pushDelegate.requestAuthorization() else { return }
        guard let token = UserDefaults.standard.string(forKey: FCMPushAppDelegate.savedTokenKey) else { return }
        await registerPushToken(token)
    }

    private func registerPushToken(_ token: String) async {
        guard !workspaceName.isEmpty else { return }
        let settings = await UNUserNotificationCenter.current().notificationSettings()
        guard settings.authorizationStatus == .authorized || settings.authorizationStatus == .provisional else { return }
        do {
            try await APIClient(session: session).registerPushToken(token)
        } catch {
            workspaceError = error.localizedDescription
        }
    }
}

struct BroadcastsView: View {
    @ObservedObject var session: SessionStore
    @State private var records: [Record] = []
    @State private var error = ""
    @State private var showCreate = false

    var body: some View {
        NavigationStack {
            List {
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
                ForEach(records) { record in
                    NavigationLink {
                        BroadcastCampaignView(session: session, campaign: record)
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(record.name ?? "Campaign").font(.headline)
                            Text([record.status, record.templateName, "\(record.sentCount ?? 0)/\(record.totalRecipients ?? 0) sent"]
                                .compactMap { $0 }.joined(separator: " • "))
                                .font(.subheadline).foregroundStyle(.secondary)
                        }
                    }
                }
            }
            .navigationTitle("Broadcasts")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) { Button { showCreate = true } label: { Image(systemName: "plus") } }
                ToolbarItem(placement: .topBarLeading) { Button("Logout") { session.clear() } }
            }
            .sheet(isPresented: $showCreate) { CreateBroadcastView(session: session) { await load() } }
            .task { await load() }
            .refreshable { await load() }
        }
    }

    private func load() async {
        do {
            records = try await APIClient(session: session).list(
                entityType: "BroadcastCampaign",
                select: "id,name,status,templateName,scheduledAt,totalRecipients,sentCount,failedCount"
            )
            error = ""
        } catch { self.error = error.localizedDescription }
    }
}

struct CreateBroadcastView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var session: SessionStore
    let onSaved: () async -> Void
    @State private var name = ""
    @State private var templateName = ""
    @State private var languageCode = "en_US"
    @State private var error = ""
    @State private var busy = false

    var body: some View {
        NavigationStack {
            Form {
                TextField("Campaign name", text: $name)
                TextField("Approved WhatsApp template", text: $templateName)
                TextField("Language", text: $languageCode)
                Text("Scheduling checks template approval, recipient consent, workspace ownership and monthly plan quota.")
                    .font(.footnote).foregroundStyle(.secondary)
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
                Button(busy ? "Creating…" : "Create draft") { Task { await save() } }
                    .disabled(busy || name.isEmpty || templateName.isEmpty)
            }
            .navigationTitle("New Broadcast")
            .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } } }
        }
    }

    private func save() async {
        busy = true
        defer { busy = false }
        do {
            _ = try await APIClient(session: session).createBroadcastCampaign(
                name: name,
                templateName: templateName,
                languageCode: languageCode.isEmpty ? "en_US" : languageCode
            )
            await onSaved()
            dismiss()
        } catch { self.error = error.localizedDescription }
    }
}

struct BroadcastCampaignView: View {
    @ObservedObject var session: SessionStore
    let campaign: Record
    @State private var recipients: [[String: Any]] = []
    @State private var currentStatus = ""
    @State private var showLeadPicker = false
    @State private var error = ""
    @State private var message = ""
    @State private var busy = false

    var body: some View {
        List {
            Section("Campaign") {
                Text(campaign.name ?? campaign.id).font(.headline)
                Text("Status: " + (currentStatus.isEmpty ? campaign.status ?? "Draft" : currentStatus))
                Text("Template: " + (campaign.templateName ?? ""))
                Text("Recipients: \(campaign.totalRecipients ?? recipients.count) • Sent: \(campaign.sentCount ?? 0) • Failed: \(campaign.failedCount ?? 0)")
                if let date = campaign.scheduledAt { Text("Scheduled: " + date) }
            }
            Section("Recipients") {
                if recipients.isEmpty { Text("No recipients added.").foregroundStyle(.secondary) }
                ForEach(recipients.indices, id: \.self) { index in
                    let recipient = recipients[index]
                    VStack(alignment: .leading) {
                        Text(recipient["name"] as? String ?? recipient["phoneNumber"] as? String ?? "Recipient")
                        Text([recipient["status"] as? String, recipient["errorMessage"] as? String]
                            .compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " • "))
                            .font(.caption).foregroundStyle(.secondary)
                    }
                }
            }
            if !error.isEmpty { Text(error).foregroundStyle(.red) }
            if !message.isEmpty { Text(message).foregroundStyle(.secondary) }
            Section("Actions") {
                Button("Add lead recipient") { showLeadPicker = true }
                    .disabled((currentStatus.isEmpty ? campaign.status : currentStatus) != "Draft")
                Button(busy ? "Scheduling…" : "Schedule now") { Task { await schedule() } }
                    .disabled(busy || !canSchedule || recipients.isEmpty)
            }
        }
        .navigationTitle("Campaign")
        .sheet(isPresented: $showLeadPicker) {
            BroadcastLeadPickerView(session: session) { lead in
                Task { await addRecipient(lead) }
            }
        }
        .task { await load() }
        .refreshable { await load() }
    }

    private var canSchedule: Bool {
        let status = currentStatus.isEmpty ? campaign.status : currentStatus
        return status == nil || status == "Draft" || status == "Scheduled"
    }

    private func load() async {
        do {
            let result = try await APIClient(session: session).broadcastCampaign(id: campaign.id)
            if let details = result["campaign"] as? [String: Any] {
                currentStatus = details["status"] as? String ?? currentStatus
            }
            recipients = result["recipients"] as? [[String: Any]] ?? []
            error = ""
        } catch { self.error = error.localizedDescription }
    }

    private func addRecipient(_ lead: Record) async {
        do {
            let result = try await APIClient(session: session).addBroadcastRecipient(campaignId: campaign.id, leadId: lead.id)
            message = "Recipient: \(result["status"] as? String ?? "added")"
            await load()
        } catch { self.error = error.localizedDescription }
    }

    private func schedule() async {
        busy = true
        defer { busy = false }
        do {
            try await APIClient(session: session).scheduleBroadcast(campaignId: campaign.id)
            message = "Campaign scheduled. The server validates approval and plan limits before sending."
            await load()
        } catch { self.error = error.localizedDescription }
    }
}

struct BroadcastLeadPickerView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var session: SessionStore
    let onSelect: (Record) -> Void
    @State private var leads: [Record] = []
    @State private var error = ""

    var body: some View {
        NavigationStack {
            List {
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
                ForEach(leads) { lead in
                    Button {
                        onSelect(lead)
                        dismiss()
                    } label: {
                        VStack(alignment: .leading) {
                            Text([lead.firstName, lead.lastName].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " ").ifEmpty(lead.name ?? "Lead"))
                            Text([lead.whatsappNumber, lead.whatsappOptIn == true ? "Opted in" : "No opt-in"].compactMap { $0 }.joined(separator: " • "))
                                .font(.caption).foregroundStyle(.secondary)
                        }
                    }
                }
            }
            .navigationTitle("Choose Lead")
            .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } } }
            .task { await load() }
        }
    }

    private func load() async {
        do {
            leads = try await APIClient(session: session).list(
                entityType: "Lead",
                select: "id,name,firstName,lastName,whatsappNumber,whatsappOptIn"
            )
            error = ""
        } catch { self.error = error.localizedDescription }
    }
}

private extension String {
    func ifEmpty(_ value: String) -> String { isEmpty ? value : self }
}

struct SalesHubView: View {
    @ObservedObject var session: SessionStore
    @State private var entityType = "Quote"
    @State private var records: [Record] = []
    @State private var error = ""
    @State private var showCreate = false

    private let types = [("Quote", "Quotes"), ("Order", "Orders"), ("Payment", "Payments")]

    var body: some View {
        NavigationStack {
            List {
                Picker("Documents", selection: $entityType) {
                    ForEach(types.indices, id: \.self) { index in Text(types[index].1).tag(types[index].0) }
                }
                .pickerStyle(.segmented)

                if !error.isEmpty { Text(error).foregroundStyle(.red) }

                ForEach(records) { record in
                    NavigationLink {
                        SalesRecordDetailView(session: session, entityType: entityType, record: record) {
                            await load()
                        }
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(record.name ?? record.quoteNumber ?? record.orderNumber ?? record.paymentNumber ?? record.id)
                                .font(.headline)
                            Text([
                                record.quoteNumber ?? record.orderNumber ?? record.paymentNumber,
                                record.status,
                                amountText(record),
                            ].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " • "))
                            .font(.subheadline).foregroundStyle(.secondary)
                        }
                    }
                }
            }
            .navigationTitle("Sales")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    if entityType != "Order" {
                        Button { showCreate = true } label: { Image(systemName: "plus") }
                    }
                }
                ToolbarItem(placement: .topBarLeading) { Button("Logout") { session.clear() } }
            }
            .sheet(isPresented: $showCreate) {
                if entityType == "Quote" {
                    CreateQuoteView(session: session) { await load() }
                } else {
                    CreatePaymentView(session: session, orderId: "") { await load() }
                }
            }
            .task(id: entityType) { await load() }
            .refreshable { await load() }
        }
    }

    private func amountText(_ record: Record) -> String? {
        guard let amount = record.totalAmount ?? record.amount else { return nil }
        return (record.currency ?? "INR") + " " + String(format: "%.2f", amount)
    }

    private func load() async {
        do {
            records = try await APIClient(session: session).list(
                entityType: entityType,
                select: "id,name,status,quoteNumber,orderNumber,paymentNumber,totalAmount,amount,currency,issueDate,orderDate,paymentDate,notes"
            )
            error = ""
        } catch { self.error = error.localizedDescription }
    }
}

struct SalesRecordDetailView: View {
    @ObservedObject var session: SessionStore
    let entityType: String
    let record: Record
    let onChanged: () async -> Void
    @State private var showItem = false
    @State private var showPayment = false
    @State private var message = ""
    @State private var busy = false

    var body: some View {
        Form {
            Section("Document") {
                Text(record.name ?? record.id)
                if let number = record.quoteNumber ?? record.orderNumber ?? record.paymentNumber { Text(number) }
                if let status = record.status { Text("Status: " + status) }
                if let total = record.totalAmount ?? record.amount { Text("Amount: \(record.currency ?? "INR") \(total, specifier: "%.2f")") }
                if let date = record.issueDate ?? record.orderDate ?? record.paymentDate { Text("Date: " + date) }
                if let notes = record.notes, !notes.isEmpty { Text(notes) }
            }
            if !message.isEmpty { Text(message).foregroundStyle(.secondary) }
            if entityType == "Quote" {
                Section("Quote actions") {
                    Button("Add line item") { showItem = true }
                    Button(busy ? "Converting…" : "Convert to order") { Task { await convert() } }
                        .disabled(busy || record.status == "Converted")
                }
            }
            if entityType == "Order" {
                Section("Order actions") { Button("Record payment") { showPayment = true } }
            }
        }
        .navigationTitle(entityType)
        .sheet(isPresented: $showItem) {
            AddQuoteItemView(session: session, quoteId: record.id) { await onChanged() }
        }
        .sheet(isPresented: $showPayment) {
            CreatePaymentView(session: session, orderId: record.id) { await onChanged() }
        }
    }

    private func convert() async {
        busy = true
        defer { busy = false }
        do {
            let result = try await APIClient(session: session).convertQuote(id: record.id)
            message = "Order created: " + (result["orderNumber"] as? String ?? "")
            await onChanged()
        } catch { message = error.localizedDescription }
    }
}

struct CreateQuoteView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var session: SessionStore
    let onSaved: () async -> Void
    @State private var name = ""
    @State private var currency = "INR"
    @State private var error = ""
    @State private var busy = false

    var body: some View {
        NavigationStack {
            Form {
                TextField("Quote name", text: $name)
                TextField("Currency", text: $currency).textInputAutocapitalization(.characters)
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
                Button(busy ? "Creating…" : "Create quote") { Task { await save() } }
                    .disabled(busy || name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
            .navigationTitle("New Quote")
            .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } } }
        }
    }

    private func save() async {
        busy = true
        defer { busy = false }
        do {
            _ = try await APIClient(session: session).createQuote(name: name, currency: currency.isEmpty ? "INR" : currency)
            await onSaved()
            dismiss()
        } catch { self.error = error.localizedDescription }
    }
}

struct AddQuoteItemView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var session: SessionStore
    let quoteId: String
    let onSaved: () async -> Void
    @State private var name = ""
    @State private var quantity = "1"
    @State private var unitPrice = ""
    @State private var error = ""
    @State private var busy = false

    var body: some View {
        NavigationStack {
            Form {
                TextField("Item name", text: $name)
                TextField("Quantity", text: $quantity).keyboardType(.decimalPad)
                TextField("Unit price", text: $unitPrice).keyboardType(.decimalPad)
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
                Button(busy ? "Adding…" : "Add item") { Task { await save() } }
                    .disabled(busy || name.isEmpty || Double(quantity) == nil || Double(unitPrice) == nil)
            }
            .navigationTitle("Quote Item")
            .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } } }
        }
    }

    private func save() async {
        guard let count = Double(quantity), count > 0, let price = Double(unitPrice), price >= 0 else { error = "Enter a positive quantity and non-negative price."; return }
        busy = true
        defer { busy = false }
        do {
            try await APIClient(session: session).addQuoteItem(quoteId: quoteId, name: name, quantity: count, unitPrice: price)
            await onSaved()
            dismiss()
        } catch { self.error = error.localizedDescription }
    }
}

struct CreatePaymentView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject var session: SessionStore
    let orderId: String
    let onSaved: () async -> Void
    @State private var name = ""
    @State private var amount = ""
    @State private var method = "Other"
    @State private var error = ""
    @State private var busy = false

    var body: some View {
        NavigationStack {
            Form {
                TextField("Payment reference", text: $name)
                TextField("Amount", text: $amount).keyboardType(.decimalPad)
                Picker("Method", selection: $method) {
                    ForEach(["Other", "Cash", "Bank Transfer", "Card", "UPI", "Cheque"], id: \.self) { Text($0) }
                }
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
                Button(busy ? "Saving…" : "Record payment") { Task { await save() } }
                    .disabled(busy || Double(amount) == nil)
            }
            .navigationTitle("Payment")
            .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } } }
        }
    }

    private func save() async {
        guard let value = Double(amount), value > 0 else { error = "Enter an amount greater than zero."; return }
        busy = true
        defer { busy = false }
        do {
            try await APIClient(session: session).createPayment(name: name.isEmpty ? "Payment" : name, amount: value, method: method, orderId: orderId)
            await onSaved()
            dismiss()
        } catch { self.error = error.localizedDescription }
    }
}

struct DashboardHomeView: View {
    @ObservedObject var session: SessionStore
    let selectWorkspace: () -> Void
    @State private var counts: [String: Any] = [:]
    @State private var inbox: [String: Any] = [:]
    @State private var error = ""

    var body: some View {
        NavigationStack {
            List {
                if !error.isEmpty {
                    Text(error).foregroundStyle(.red)
                }

                Section("CRM") {
                    metric("Leads", "leads")
                    metric("Contacts", "contacts")
                    metric("Accounts", "accounts")
                    metric("Opportunities", "opportunities")
                }

                Section("Work") {
                    metric("Open Tasks", "openTasks")
                    metric("Unread WhatsApp", "unreadWhatsApp")
                }

                Section("WhatsApp inbox · last 30 days") {
                    inboxMetric("Open conversations", "openConversations")
                    inboxMetric("Unassigned", "unassignedOpenConversations")
                    inboxMetric("Waiting for reply", "waitingForResponse")
                    inboxMetric("Waiting over 24 hours", "waitingOver24Hours")
                    inboxMetric("Inbound messages", "inboundMessages30d")
                    inboxMetric("Outbound messages", "outboundMessages30d")
                    inboxDurationMetric("Average tracked latest reply", "averageLatestReplySeconds30d")
                    inboxMetric("Tracked reply samples", "replySamples30d")
                }

                if let assignees = inbox["byAssignee"] as? [[String: Any]], !assignees.isEmpty {
                    Section("Inbox by assignee") {
                        ForEach(assignees.indices, id: \.self) { index in
                            inboxAssigneeMetric(assignees[index])
                        }
                    }
                }

                if let trends = inbox["messageTrend30d"] as? [String: Any],
                   let inbound = trends["inbound"] as? [[String: Any]],
                   let outbound = trends["outbound"] as? [[String: Any]],
                   !inbound.isEmpty {
                    Section("Daily WhatsApp messages · last 7 days") {
                        ForEach(max(0, inbound.count - 7)..<inbound.count, id: \.self) { index in
                            let day = inbound[index]["date"] as? String ?? ""
                            let received = (inbound[index]["count"] as? NSNumber)?.intValue ?? 0
                            let sent = (outbound[index]["count"] as? NSNumber)?.intValue ?? 0
                            HStack {
                                Text(String(day.suffix(5)))
                                Spacer()
                                Text("\(received) in · \(sent) out").font(.subheadline)
                            }
                        }
                    }
                }

                Section("Sales") {
                    metric("Quotes", "quotes")
                    metric("Orders", "orders")
                    metric("Pending Payments", "pendingPayments")
                }

                Button("Switch / create workspace") {
                    selectWorkspace()
                }
            }
            .navigationTitle("Dashboard")
            .refreshable { await load() }
            .task { await load() }
        }
    }

    private func metric(_ title: String, _ key: String) -> some View {
        HStack {
            Text(title)
            Spacer()
            Text(String((counts[key] as? NSNumber)?.intValue ?? 0))
                .font(.headline)
        }
    }

    private func inboxMetric(_ title: String, _ key: String) -> some View {
        HStack {
            Text(title)
            Spacer()
            Text(String((inbox[key] as? NSNumber)?.intValue ?? 0)).font(.headline)
        }
    }

    private func inboxDurationMetric(_ title: String, _ key: String) -> some View {
        HStack {
            Text(title)
            Spacer()
            if let seconds = (inbox[key] as? NSNumber)?.intValue {
                Text(seconds < 60 ? "\(seconds)s" : "\(seconds / 60)m \(seconds % 60)s").font(.headline)
            } else {
                Text("—").font(.headline)
            }
        }
    }

    private func inboxAssigneeMetric(_ assignee: [String: Any]) -> some View {
        let name = assignee["name"] as? String ?? "Workspace member"
        let open = (assignee["openConversations"] as? NSNumber)?.intValue ?? 0
        let waiting = (assignee["waitingForResponse"] as? NSNumber)?.intValue ?? 0
        let overdue = (assignee["waitingOver24Hours"] as? NSNumber)?.intValue ?? 0
        let average = (assignee["averageLatestReplySeconds30d"] as? NSNumber)?.intValue
        let samples = (assignee["latestReplySamples30d"] as? NSNumber)?.intValue ?? 0

        return VStack(alignment: .leading, spacing: 4) {
            Text(name).font(.headline)
            Text("\(open) open · \(waiting) waiting · \(overdue) over 24h")
                .font(.caption)
                .foregroundStyle(.secondary)
            Text("Avg tracked latest reply: \(average.map { $0 < 60 ? "\($0)s" : "\($0 / 60)m \($0 % 60)s" } ?? "—") · \(samples) samples")
                .font(.caption)
                .foregroundStyle(.secondary)
        }
    }

    private func load() async {
        do {
            let result = try await APIClient(session: session).dashboardSummary()
            counts = result["counts"] as? [String: Any] ?? [:]
            inbox = result["inbox"] as? [String: Any] ?? [:]
            error = ""
        } catch {
            self.error = error.localizedDescription
        }
    }
}


struct WorkspaceChooserView: View {
    @Environment(\.presentationMode) private var presentationMode
    @ObservedObject var session: SessionStore
    let onSelected: (String) -> Void
    @State private var workspaces: [[String: Any]] = []
    @State private var newName = ""
    @State private var error = ""
    @State private var busy = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Your workspaces") {
                    ForEach(workspaces.indices, id: \.self) { index in
                        let workspace = workspaces[index]
                        Button {
                            Task {
                                await choose(workspace)
                            }
                        } label: {
                            HStack {
                                Text(workspace["name"] as? String ?? "Workspace")
                                Spacer()
                                if workspace["active"] as? Bool == true {
                                    Image(systemName: "checkmark.circle.fill")
                                }
                            }
                        }
                    }
                }

                Section("Create workspace") {
                    TextField("Workspace name", text: $newName)
                    Button(busy ? "Creating…" : "Create workspace") {
                        Task { await create() }
                    }
                    .disabled(busy || newName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }

                if !error.isEmpty {
                    Text(error).foregroundStyle(.red)
                }
            }
            .navigationTitle("Workspace")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Close") {
                        presentationMode.wrappedValue.dismiss()
                    }
                }
            }
            .task { await load() }
        }
    }

    private func load() async {
        do {
            workspaces = try await APIClient(session: session).myWorkspaces()
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func choose(_ workspace: [String: Any]) async {
        guard let id = workspace["id"] as? String else { return }

        do {
            try await APIClient(session: session).switchWorkspace(id: id)
            onSelected(workspace["name"] as? String ?? "")
            presentationMode.wrappedValue.dismiss()
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func create() async {
        busy = true
        do {
            try await APIClient(session: session).createWorkspace(name: newName.trimmingCharacters(in: .whitespacesAndNewlines))
            let latest = try await APIClient(session: session).myWorkspaces()

            if let active = latest.first(where: { ($0["active"] as? Bool) == true }) {
                onSelected(active["name"] as? String ?? "")
            }

            presentationMode.wrappedValue.dismiss()
        } catch {
            self.error = error.localizedDescription
        }
        busy = false
    }
}

enum RecordListKind: Equatable {
    case leads
    case tasks
    case generic
    case whatsappConversations
}

struct RecordListView: View {
    @ObservedObject var session: SessionStore
    let title: String
    let entityType: String
    let select: String
    let kind: RecordListKind
    @State private var records: [Record] = []
    @State private var error = ""
    @State private var showCreate = false

    var body: some View {
        NavigationStack {
            List {
                if !error.isEmpty {
                    Text(error).foregroundStyle(.red)
                }

                ForEach(records) { record in
                    NavigationLink {
                        RecordDetailView(session: session, entityType: entityType, record: record)
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            HStack {
                                Text(recordTitle(record))
                                    .font(.headline)
                                Spacer()
                                if kind == .whatsappConversations, let unread = record.unreadCount, unread > 0 {
                                    Text(String(unread))
                                        .font(.caption.bold())
                                        .padding(6)
                                        .background(.blue.opacity(0.12))
                                        .clipShape(Capsule())
                                }
                            }
                            Text(recordSubtitle(record))
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }
                    }
                }
            }
            .navigationTitle(title)
            .toolbar {
                if kind == .leads || kind == .tasks {
                    ToolbarItem(placement: .topBarTrailing) {
                        Button {
                            showCreate = true
                        } label: {
                            Image(systemName: "plus")
                        }
                    }
                }

                ToolbarItem(placement: .topBarLeading) {
                    Button("Logout") { session.clear() }
                }
            }
            .sheet(isPresented: $showCreate) {
                if kind == .leads {
                    CreateLeadView(session: session)
                } else {
                    CreateTaskView(session: session)
                }
            }
            .task {
                await load()
            }
            .refreshable {
                await load()
            }
        }
    }

    private func load() async {
        do {
            records = try await APIClient(session: session).list(
                entityType: entityType,
                select: select
            )
            error = ""
        } catch {
            self.error = error.localizedDescription
        }
    }

    private func recordTitle(_ record: Record) -> String {
        let person = [record.firstName, record.lastName]
            .compactMap { $0 }
            .filter { !$0.isEmpty }
            .joined(separator: " ")

        if entityType == "WhatsAppConversation" {
            return record.customerDisplayName ?? record.phoneNumber ?? record.waId ?? record.name ?? record.id
        }

        return person.isEmpty ? (record.name ?? record.id) : person
    }

    private func recordSubtitle(_ record: Record) -> String {
        if entityType == "Lead" {
            return [
                record.accountName,
                record.phoneNumber,
                record.leadStage,
                record.status,
            ]
            .compactMap { $0 }
            .filter { !$0.isEmpty }
            .joined(separator: " • ")
        }

        if entityType == "WhatsAppConversation" {
            return [
                record.lastMessagePreview,
                record.status,
                record.phoneNumber,
            ]
            .compactMap { $0 }
            .filter { !$0.isEmpty }
            .joined(separator: " • ")
        }

        if entityType == "WhatsAppMessage" {
            return [
                record.direction,
                record.status,
                record.textBody,
            ]
            .compactMap { $0 }
            .filter { !$0.isEmpty }
            .joined(separator: " • ")
        }

        return [
            record.status,
            record.priority,
            record.dateEnd,
            record.phoneNumber,
        ]
        .compactMap { $0 }
        .filter { !$0.isEmpty }
        .joined(separator: " • ")
    }
}

struct RecordDetailView: View {
    @ObservedObject var session: SessionStore
    let entityType: String
    let record: Record
    @State private var showingMessage = false
    @State private var showingCallLog = false
    @State private var showingFollowUp = false
    @State private var message = ""
    @State private var threadMessages: [WhatsAppThreadMessage] = []
    @State private var internalNote = ""
    @State private var mediaFiles: [String: URL] = [:]

    var body: some View {
        Form {
            Section("Record") {
                Text(record.name ?? record.id)
                if let first = record.firstName {
                    Text(first + " " + (record.lastName ?? ""))
                }
                if let company = record.accountName { Text(company) }
                if let phone = record.phoneNumber {
                    Link(phone, destination: URL(string: "tel:" + phone)!)
                }
                if let email = record.emailAddress {
                    Link(email, destination: URL(string: "mailto:" + email)!)
                }
                if let stage = record.leadStage { Text("Stage: " + stage) }
                if let status = record.status { Text("Status: " + status) }
                if let description = record.description { Text(description) }
            }

            if entityType == "WhatsAppConversation" {
                Section("Conversation") {
                    if let preview = record.lastMessagePreview {
                        Text(preview)
                    }
                    if let unread = record.unreadCount {
                        Text("Unread: " + String(unread))
                    }
                    ForEach(threadMessages) { item in
                        VStack(alignment: .leading, spacing: 4) {
                            Text(item.direction == "Internal" ? "Internal note" : (item.direction ?? "Message"))
                                .font(.caption).foregroundStyle(.secondary)
                            Text(item.textBody ?? item.messageType ?? "")
                            Text(item.receivedAt ?? item.sentAt ?? "")
                                .font(.caption2).foregroundStyle(.secondary)
                            if item.mediaId != nil, let mimeType = item.mediaMimeType {
                                if let file = mediaFiles[item.id] {
                                    if ["image/jpeg", "image/png", "image/gif", "image/webp"].contains(mimeType),
                                       let image = UIImage(contentsOfFile: file.path) {
                                        Image(uiImage: image).resizable().scaledToFit().frame(maxHeight: 350)
                                    }
                                    ShareLink(item: file) {
                                        Label("Open or share media", systemImage: "square.and.arrow.up")
                                    }
                                } else {
                                    Button(["image/jpeg", "image/png", "image/gif", "image/webp"].contains(mimeType) ? "View image" : "Download media") {
                                        Task {
                                            do {
                                                mediaFiles[item.id] = try await APIClient(session: session)
                                                    .downloadWhatsAppMedia(messageId: item.id, mimeType: mimeType)
                                            } catch {
                                                message = error.localizedDescription
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    TextField("Internal note", text: $internalNote, axis: .vertical)
                    Button("Add internal note") {
                        Task {
                            do {
                                try await APIClient(session: session).actOnWhatsAppConversation(
                                    conversationId: record.id,
                                    action: "note",
                                    note: internalNote
                                )
                                internalNote = ""
                                threadMessages = try await APIClient(session: session)
                                    .listWhatsAppConversationMessages(conversationId: record.id)
                            } catch {
                                message = error.localizedDescription
                            }
                        }
                    }
                    .disabled(internalNote.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                    Button("Mark as read") {
                        Task {
                            do {
                                try await APIClient(session: session).actOnWhatsAppConversation(
                                    conversationId: record.id,
                                    action: "read"
                                )
                                message = "Conversation marked as read."
                            } catch {
                                message = error.localizedDescription
                            }
                        }
                    }
                    Button(record.status == "Closed" ? "Reopen" : "Close conversation") {
                        Task {
                            do {
                                try await APIClient(session: session).actOnWhatsAppConversation(
                                    conversationId: record.id,
                                    action: record.status == "Closed" ? "open" : "close"
                                )
                                message = record.status == "Closed" ? "Conversation reopened." : "Conversation closed."
                            } catch {
                                message = error.localizedDescription
                            }
                        }
                    }
                }
            }

            if entityType == "Lead" {
                Section("Actions") {
                    Button("Send WhatsApp message") {
                        showingMessage = true
                    }
                    .disabled(!(record.whatsappOptIn ?? false))

                    Button("Log completed call") {
                        showingCallLog = true
                    }

                    Button("Schedule follow-up") {
                        showingFollowUp = true
                    }

                    Button("Convert to Contact") {
                        Task {
                            do {
                                try await APIClient(session: session).convertLead(id: record.id, record: record)
                                message = "Lead converted."
                            } catch {
                                message = error.localizedDescription
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle(entityType)
        .task(id: record.id) {
            guard entityType == "WhatsAppConversation" else { return }
            do {
                threadMessages = try await APIClient(session: session)
                    .listWhatsAppConversationMessages(conversationId: record.id)
            } catch {
                message = error.localizedDescription
            }
        }
        .sheet(isPresented: $showingMessage) {
            SendWhatsAppView(session: session, leadId: record.id)
        }
        .sheet(isPresented: $showingCallLog) {
            LogCallView(
                session: session,
                leadId: record.id,
                phoneNumber: record.whatsappNumber ?? record.phoneNumber ?? ""
            )
        }
        .sheet(isPresented: $showingFollowUp) {
            CreateFollowUpView(session: session, parentType: entityType, parentId: record.id)
        }
        .alert("OmniGoCRM", isPresented: Binding(
            get: { !message.isEmpty },
            set: { if !$0 { message = "" } }
        )) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(message)
        }
    }
}

struct FollowUpListView: View {
    @ObservedObject var session: SessionStore
    @State private var followUps: [FollowUpRecord] = []
    @State private var error = ""

    var body: some View {
        NavigationStack {
            List {
                if !error.isEmpty {
                    Text(error).foregroundStyle(.red)
                }

                ForEach(followUps) { followUp in
                    VStack(alignment: .leading, spacing: 4) {
                        Text(followUp.name ?? "Follow-up").font(.headline)
                        Text([followUp.priority, followUp.status, followUp.dateStart].compactMap { $0 }.joined(separator: " • "))
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                    .swipeActions {
                        if followUp.status != "Completed" {
                            Button("Complete") {
                                Task {
                                    do {
                                        try await APIClient(session: session).completeTask(id: followUp.id)
                                        await load()
                                    } catch {
                                        self.error = error.localizedDescription
                                    }
                                }
                            }
                            .tint(.green)
                        }
                    }
                }
            }
            .navigationTitle("Follow-ups")
            .task { await load() }
            .refreshable { await load() }
        }
    }

    private func load() async {
        do {
            followUps = try await APIClient(session: session).listFollowUps()
            error = ""
        } catch {
            self.error = error.localizedDescription
        }
    }
}

struct CreateFollowUpView: View {
    @Environment(\.presentationMode) private var presentationMode
    @ObservedObject var session: SessionStore
    let parentType: String
    let parentId: String
    @State private var name = ""
    @State private var description = ""
    @State private var priority = "Normal"
    @State private var dateStart = Calendar.current.date(byAdding: .day, value: 1, to: .now) ?? .now
    @State private var error = ""

    var body: some View {
        NavigationStack {
            Form {
                TextField("Follow-up", text: $name)
                DatePicker("Due", selection: $dateStart)
                Picker("Priority", selection: $priority) {
                    ForEach(["Low", "Normal", "High", "Urgent"], id: \.self) { Text($0) }
                }
                TextField("Notes", text: $description, axis: .vertical)
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
            }
            .navigationTitle("New Follow-up")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { presentationMode.wrappedValue.dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            do {
                                try await APIClient(session: session).createFollowUp(
                                    name: name,
                                    dateStart: dateStart,
                                    parentType: parentType,
                                    parentId: parentId,
                                    description: description,
                                    priority: priority
                                )
                                presentationMode.wrappedValue.dismiss()
                            } catch {
                                self.error = error.localizedDescription
                            }
                        }
                    }
                    .disabled(name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
            }
        }
    }
}

struct CreateLeadView: View {
    @Environment(\.presentationMode) private var presentationMode
    @ObservedObject var session: SessionStore
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var company = ""
    @State private var phone = ""
    @State private var whatsapp = ""
    @State private var source = ""
    @State private var description = ""
    @State private var error = ""

    var body: some View {
        NavigationStack {
            Form {
                TextField("First name", text: $firstName)
                TextField("Last name", text: $lastName)
                TextField("Company", text: $company)
                TextField("Phone", text: $phone)
                TextField("WhatsApp", text: $whatsapp)
                TextField("Source detail", text: $source)
                TextField("Requirement", text: $description, axis: .vertical)
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
            }
            .navigationTitle("New Lead")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { presentationMode.wrappedValue.dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            do {
                                try await APIClient(session: session).createLead(
                                    firstName: firstName,
                                    lastName: lastName,
                                    company: company,
                                    phone: phone,
                                    whatsapp: whatsapp,
                                    sourceDetail: source,
                                    description: description
                                )
                                presentationMode.wrappedValue.dismiss()
                            } catch {
                                self.error = error.localizedDescription
                            }
                        }
                    }
                    .disabled(firstName.isEmpty)
                }
            }
        }
    }
}

struct CreateTaskView: View {
    @Environment(\.presentationMode) private var presentationMode
    @ObservedObject var session: SessionStore
    @State private var name = ""
    @State private var description = ""
    @State private var priority = "Normal"
    @State private var error = ""

    var body: some View {
        NavigationStack {
            Form {
                TextField("Task", text: $name)
                TextField("Description", text: $description, axis: .vertical)
                TextField("Priority", text: $priority)
                if !error.isEmpty { Text(error).foregroundStyle(.red) }
            }
            .navigationTitle("New Task")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { presentationMode.wrappedValue.dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            do {
                                try await APIClient(session: session).createTask(
                                    name: name,
                                    description: description,
                                    priority: priority
                                )
                                presentationMode.wrappedValue.dismiss()
                            } catch {
                                self.error = error.localizedDescription
                            }
                        }
                    }
                    .disabled(name.isEmpty)
                }
            }
        }
    }
}


struct LogCallView: View {
    @Environment(\.presentationMode) private var presentationMode
    @ObservedObject var session: SessionStore
    let leadId: String
    let phoneNumber: String
    @State private var duration = "0"
    @State private var error = ""

    var body: some View {
        NavigationStack {
            Form {
                Text(phoneNumber.isEmpty ? "No phone number stored" : phoneNumber)
                TextField("Duration in seconds", text: $duration)
                    .keyboardType(.numberPad)
                if !error.isEmpty {
                    Text(error).foregroundStyle(.red)
                }
            }
            .navigationTitle("Log Call")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { presentationMode.wrappedValue.dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            do {
                                try await APIClient(session: session).logCompletedCall(
                                    leadId: leadId,
                                    phoneNumber: phoneNumber,
                                    durationSeconds: Int(duration) ?? 0
                                )
                                presentationMode.wrappedValue.dismiss()
                            } catch {
                                self.error = error.localizedDescription
                            }
                        }
                    }
                }
            }
        }
    }
}

struct SendWhatsAppView: View {
    @Environment(\.presentationMode) private var presentationMode
    @ObservedObject var session: SessionStore
    let leadId: String
    @State private var messageBody = ""
    @State private var error = ""

    var bodyView: some View {
        Form {
            TextEditor(text: $messageBody).frame(minHeight: 140)
            if !error.isEmpty { Text(error).foregroundStyle(.red) }
        }
    }

    var body: some View {
        NavigationStack {
            bodyView
                .navigationTitle("WhatsApp")
                .toolbar {
                    ToolbarItem(placement: .cancellationAction) { Button("Cancel") { presentationMode.wrappedValue.dismiss() } }
                    ToolbarItem(placement: .confirmationAction) {
                        Button("Send") {
                            Task {
                                do {
                                    try await APIClient(session: session).sendWhatsApp(
                                        leadId: leadId,
                                        body: messageBody
                                    )
                                    presentationMode.wrappedValue.dismiss()
                                } catch {
                                    self.error = error.localizedDescription
                                }
                            }
                        }
                        .disabled(messageBody.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                    }
                }
        }
    }
}
