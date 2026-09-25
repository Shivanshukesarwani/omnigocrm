import Foundation

enum AppConfig {
    static let baseURL = URL(string: "https://crm.example.com/")!
    static let apiURL = baseURL.appendingPathComponent("api/v1/")
}
