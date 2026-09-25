import SwiftUI

struct RootView: View {
    @ObservedObject var session: SessionStore

    var body: some View {
        if session.isAuthenticated {
            DashboardView(session: session)
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
                                let api = await APIClient(session: session)
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

            RecordListView(session: session, title: "WhatsApp", entityType: "WhatsAppConversation", select: "id,name,waId,phoneNumber,customerDisplayName,status,unreadCount,lastMessageAt,lastMessagePreview,leadId,contactId", kind: .whatsappConversations)
                .tabItem { Label("WhatsApp", systemImage: "message") }
                .tag(5)
        }
        .task {
            await ensureWorkspace()
        }
        .sheet(isPresented: $showWorkspace) {
            WorkspaceChooserView(session: session) {
                workspaceName = $0
            }
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
}

struct DashboardHomeView: View {
    @ObservedObject var session: SessionStore
    let selectWorkspace: () -> Void
    @State private var counts: [String: Any] = [:]
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

    private func load() async {
        do {
            let result = try await APIClient(session: session).dashboardSummary()
            counts = result["counts"] as? [String: Any] ?? [:]
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
    @State private var message = ""

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
