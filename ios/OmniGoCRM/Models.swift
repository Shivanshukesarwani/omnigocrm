import Foundation

struct EspoListResponse: Decodable {
    let list: [Record]
    let total: Int?
}

struct FollowUpListResponse: Decodable {
    let list: [FollowUpRecord]
}

struct WhatsAppMessagesResponse: Decodable {
    let items: [WhatsAppThreadMessage]
}

struct WhatsAppThreadMessage: Decodable, Identifiable {
    let id: String
    let direction: String?
    let status: String?
    let messageType: String?
    let textBody: String?
    let mediaId: String?
    let mediaMimeType: String?
    let receivedAt: String?
    let sentAt: String?
}

struct FollowUpRecord: Decodable, Identifiable {
    let id: String
    let name: String?
    let status: String?
    let priority: String?
    let dateStart: String?
    let parentType: String?
    let parentId: String?
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
    let waId: String?
    let customerDisplayName: String?
    let unreadCount: Int?
    let lastMessageAt: String?
    let lastMessagePreview: String?
    let providerPhoneNumberId: String?
    let conversationId: String?
    let mediaId: String?
    let quoteNumber: String?
    let orderNumber: String?
    let paymentNumber: String?
    let totalAmount: Double?
    let currency: String?
    let issueDate: String?
    let orderDate: String?
    let paymentDate: String?
    let notes: String?
    let templateName: String?
    let languageCode: String?
    let scheduledAt: String?
    let totalRecipients: Int?
    let sentCount: Int?
    let failedCount: Int?
}

struct AppUserResponse: Decodable {
    let token: String
    let user: Record
}

