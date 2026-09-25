import Foundation
import Combine

@MainActor
final class APIClient: ObservableObject {
    private let session: SessionStore

    init(session: SessionStore) {
        self.session = session
    }

    private func authorization(username: String, secret: String) -> String {
        let raw = username + ":" + secret
        let encoded = Data(raw.utf8).base64EncodedString()
        return "Basic " + encoded
    }

    private func request(
        method: String,
        path: String,
        body: [String: Any]? = nil,
        username: String? = nil,
        secret: String? = nil,
        baseURL: String? = nil
    ) async throws -> Data {
        let root = (baseURL ?? session.baseURL).trimmingCharacters(in: .whitespacesAndNewlines)
        let normalized = root.hasSuffix("/") ? root : root + "/"
        guard let url = URL(string: normalized + "api/v1/" + path) else { throw APIError.invalidURL }
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let authUser = username ?? session.username
        let authSecret = secret ?? session.token
        if let authUser, let authSecret {
            request.setValue(
                authorization(username: authUser, secret: authSecret),
                forHTTPHeaderField: "Espo-Authorization"
            )
        }

        if let body {
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        }

        let (data, response) = try await URLSession.shared.data(for: request)

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        guard (200...299).contains(http.statusCode) else {
            throw APIError.http(status: http.statusCode, body: String(data: data, encoding: .utf8) ?? "")
        }

        return data
    }

    func login(baseURL: String, username: String, password: String) async throws {
        let cleanBaseURL = baseURL.trimmingCharacters(in: .whitespacesAndNewlines)
        guard URL(string: cleanBaseURL) != nil, cleanBaseURL.hasPrefix("http://") || cleanBaseURL.hasPrefix("https://") else {
            throw APIError.invalidURL
        }

        let data = try await request(
            method: "GET",
            path: "App/user",
            username: username,
            secret: password,
            baseURL: cleanBaseURL
        )
        let result = try JSONDecoder().decode(AppUserResponse.self, from: data)
        let name = result.user.name ?? result.user.firstName ?? username
        session.save(token: result.token, username: username, userName: name, baseURL: cleanBaseURL)
    }


    func myWorkspaces() async throws -> [[String: Any]] {
        let data = try await request(method: "GET", path: "OmniGoCRM/Workspace/mine")
        return (try JSONSerialization.jsonObject(with: data) as? [String: Any])?["list"] as? [[String: Any]] ?? []
    }

    func createWorkspace(name: String, slug: String = "") async throws {
        var body: [String: Any] = ["name": name]
        if !slug.isEmpty { body["slug"] = slug }
        _ = try await request(method: "POST", path: "OmniGoCRM/Workspace/create", body: body)
    }

    func switchWorkspace(id: String) async throws {
        _ = try await request(method: "POST", path: "OmniGoCRM/Workspace/switch", body: ["workspaceId": id])
    }

    func dashboardSummary() async throws -> [String: Any] {
        let data = try await request(method: "GET", path: "OmniGoCRM/Dashboard/summary")
        return try JSONSerialization.jsonObject(with: data) as? [String: Any] ?? [:]
    }

    func list(entityType: String, select: String, maxSize: Int = 100) async throws -> [Record] {
        var components = URLComponents(
            string: "https://placeholder.invalid/api/v1/" + entityType
        )!
        components.queryItems = [
            URLQueryItem(name: "maxSize", value: String(maxSize)),
            URLQueryItem(name: "orderBy", value: "createdAt"),
            URLQueryItem(name: "order", value: "desc"),
            URLQueryItem(name: "select", value: select),
        ]

        let path = entityType + "?" + (components.percentEncodedQuery ?? "")
        let data = try await request(method: "GET", path: path)
        return try JSONDecoder().decode(EspoListResponse.self, from: data).list
    }

    func read(entityType: String, id: String) async throws -> Record {
        let data = try await request(method: "GET", path: entityType + "/" + id)
        return try JSONDecoder().decode(Record.self, from: data)
    }


    func listWhatsAppConversations() async throws -> [Record] {
        try await list(
            entityType: "WhatsAppConversation",
            select: "id,name,waId,phoneNumber,customerDisplayName,status,unreadCount,lastMessageAt,lastMessagePreview,leadId,contactId"
        )
    }

    func actOnWhatsAppConversation(conversationId: String, action: String) async throws {
        _ = try await request(
            method: "POST",
            path: "OmniGoCRM/WhatsApp/conversationAction",
            body: [
                "conversationId": conversationId,
                "action": action,
            ]
        )
    }

    func logCompletedCall(
        leadId: String,
        phoneNumber: String,
        durationSeconds: Int,
        status: String = "completed"
    ) async throws {
        _ = try await request(
            method: "POST",
            path: "OmniGoCRM/Calling/log",
            body: [
                "leadId": leadId,
                "phoneNumber": phoneNumber,
                "duration": durationSeconds,
                "status": status,
                "direction": "Outbound",
            ]
        )
    }

    func createLead(firstName: String, lastName: String, company: String, phone: String, whatsapp: String, sourceDetail: String, description: String) async throws {
        var body: [String: Any] = [
            "firstName": firstName,
            "leadStage": "New",
            "preferredContactChannel": "WhatsApp",
        ]
        if !lastName.isEmpty { body["lastName"] = lastName }
        if !company.isEmpty { body["accountName"] = company }
        if !phone.isEmpty { body["phoneNumber"] = phone }
        if !whatsapp.isEmpty { body["whatsappNumber"] = whatsapp }
        if !sourceDetail.isEmpty { body["leadSourceDetail"] = sourceDetail }
        if !description.isEmpty { body["description"] = description }
        _ = try await request(method: "POST", path: "Lead", body: body)
    }

    func convertLead(id: String, record: Record) async throws {
        var contact: [String: Any] = [:]
        if let value = record.firstName { contact["firstName"] = value }
        if let value = record.lastName { contact["lastName"] = value }
        if let value = record.phoneNumber { contact["phoneNumber"] = value }
        if let value = record.emailAddress { contact["emailAddress"] = value }
        if let value = record.accountName { contact["accountName"] = value }

        _ = try await request(
            method: "POST",
            path: "Lead/action/convert",
            body: [
                "id": id,
                "records": ["Contact": contact],
            ]
        )
    }

    func createTask(name: String, description: String, priority: String) async throws {
        var body: [String: Any] = [
            "name": name,
            "status": "Not Started",
            "priority": priority.isEmpty ? "Normal" : priority,
        ]
        if !description.isEmpty { body["description"] = description }
        _ = try await request(method: "POST", path: "Task", body: body)
    }

    func completeTask(id: String) async throws {
        _ = try await request(method: "PUT", path: "Task/" + id, body: ["status": "Completed"])
    }

    func sendWhatsApp(leadId: String, body: String) async throws {
        _ = try await request(
            method: "POST",
            path: "OmniGoCRM/WhatsApp/sendText",
            body: ["leadId": leadId, "body": body]
        )
    }
}

enum APIError: LocalizedError {
    case invalidURL
    case invalidResponse
    case http(status: Int, body: String)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "The CRM URL is invalid."
        case .invalidResponse:
            return "The CRM returned an invalid response."
        case let .http(status, body):
            return "CRM error \(status): \(body)"
        }
    }
}
