import Foundation

struct EspoListResponse: Decodable {
    let list: [Record]
    let total: Int?
}

struct Record: Decodable, Identifiable {
    let id: String
    let name: String?
    let firstName: String?
    let lastName: String?
    let accountName: String?
    let phoneNumber: String?
    let whatsappNumber: String?
    let emailAddress: String?
    let status: String?
    let leadStage: String?
    let whatsappOptIn: Bool?
    let description: String?
    let direction: String?
    let messageType: String?
    let textBody: String?
    let priority: String?
    let dateEnd: String?
    let amount: Double?
}

struct AppUserResponse: Decodable {
    let token: String
    let user: Record
}

