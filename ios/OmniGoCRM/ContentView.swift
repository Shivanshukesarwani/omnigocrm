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
    @State private var username = ""
    @State private var password = ""
    @State private var busy = false
    @State private var error = ""

    var body: some View {
        NavigationStack {
            Form {
                Section("OmniGoCRM") {
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
                                try await api.login(username: username, password: password)
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
        }
    }
}

struct DashboardView: View {
    @ObservedObject var session: SessionStore
    @State private var selected = 0

    var body: some View {
        TabView(selection: $selected) {
            RecordListView(session: session, title: "Leads", entityType: "Lead", select: "id,firstName,lastName,accountName,phoneNumber,whatsappNumber,status,leadStage", kind: .leads)
                .tabItem { Label("Leads", systemImage: "person.2") }
                .tag(0)

            RecordListView(session: session, title: "Contacts", entityType: "Contact", select: "id,name,phoneNumber,emailAddress", kind: .generic)
                .tabItem { Label("Contacts", systemImage: "person.crop.circle") }
                .tag(1)

            RecordListView(session: session, title: "Accounts", entityType: "Account", select: "id,name,phoneNumber,emailAddress", kind: .generic)
                .tabItem { Label("Accounts", systemImage: "building.2") }
                .tag(2)

            RecordListView(session: session, title: "Tasks", entityType: "Task", select: "id,name,status,priority,dateEnd", kind: .tasks)
                .tabItem { Label("Tasks", systemImage: "checklist") }
                .tag(3)

            RecordListView(session: session, title: "WhatsApp", entityType: "WhatsAppMessage", select: "id,name,direction,status,messageType,fromNumber,toNumber,textBody,receivedAt,sentAt", kind: .generic)
                .tabItem { Label("WhatsApp", systemImage: "message") }
                .tag(4)
        }
    }
}

enum RecordListKind: Equatable {
    case leads
    case tasks
    case generic
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
                            Text(recordTitle(record))
                                .font(.headline)
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
            error = error.localizedDescription
        }
    }

    private func recordTitle(_ record: Record) -> String {
        let person = [record.firstName, record.lastName]
            .compactMap { $0 }
            .filter { !$0.isEmpty }
            .joined(separator: " ")

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

            if entityType == "Lead" {
                Section("Actions") {
                    Button("Send WhatsApp message") {
                        showingMessage = true
                    }
                    .disabled(!(record.whatsappOptIn ?? false))

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
    @Environment(.dismiss) private var dismiss
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
                    Button("Cancel") { dismiss() }
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
                                dismiss()
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
    @Environment(.dismiss) private var dismiss
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
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            do {
                                try await APIClient(session: session).createTask(
                                    name: name,
                                    description: description,
                                    priority: priority
                                )
                                dismiss()
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

struct SendWhatsAppView: View {
    @Environment(.dismiss) private var dismiss
    @ObservedObject var session: SessionStore
    let leadId: String
    @State private var body = ""
    @State private var error = ""

    var bodyView: some View {
        Form {
            TextEditor(text: $body).frame(minHeight: 140)
            if !error.isEmpty { Text(error).foregroundStyle(.red) }
        }
    }

    var body: some View {
        NavigationStack {
            bodyView
                .navigationTitle("WhatsApp")
                .toolbar {
                    ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                    ToolbarItem(placement: .confirmationAction) {
                        Button("Send") {
                            Task {
                                do {
                                    try await APIClient(session: session).sendWhatsApp(
                                        leadId: leadId,
                                        body: body
                                    )
                                    dismiss()
                                } catch {
                                    self.error = error.localizedDescription
                                }
                            }
                        }
                        .disabled(body.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                    }
                }
        }
    }
}
