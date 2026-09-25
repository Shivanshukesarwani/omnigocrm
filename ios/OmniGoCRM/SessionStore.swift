import Foundation
import Security

@MainActor
final class SessionStore: ObservableObject {
    @Published private(set) var token: String?
    @Published private(set) var username: String?
    @Published private(set) var userName: String?

    init() {
        token = Keychain.read("omnigocrm.token")
        username = UserDefaults.standard.string(forKey: "omnigocrm.username")
        userName = UserDefaults.standard.string(forKey: "omnigocrm.userName")
    }

    var isAuthenticated: Bool {
        !(token?.isEmpty ?? true)
    }

    func save(token: String, username: String, userName: String) {
        Keychain.write(token, key: "omnigocrm.token")
        UserDefaults.standard.set(username, forKey: "omnigocrm.username")
        UserDefaults.standard.set(userName, forKey: "omnigocrm.userName")
        self.token = token
        self.username = username
        self.userName = userName
    }

    func clear() {
        Keychain.delete("omnigocrm.token")
        UserDefaults.standard.removeObject(forKey: "omnigocrm.username")
        UserDefaults.standard.removeObject(forKey: "omnigocrm.userName")
        token = nil
        username = nil
        userName = nil
    }
}

enum Keychain {
    static func write(_ value: String, key: String) {
        let data = Data(value.utf8)
        let query: [CFString: Any] = [
            kSecClass: kSecClassGenericPassword,
            kSecAttrAccount: key,
            kSecValueData: data,
        ]
        SecItemDelete(query as CFDictionary)
        SecItemAdd(query as CFDictionary, nil)
    }

    static func read(_ key: String) -> String? {
        let query: [CFString: Any] = [
            kSecClass: kSecClassGenericPassword,
            kSecAttrAccount: key,
            kSecReturnData: true,
            kSecMatchLimit: kSecMatchLimitOne,
        ]
        var result: AnyObject?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else {
            return nil
        }
        return String(data: data, encoding: .utf8)
    }

    static func delete(_ key: String) {
        let query: [CFString: Any] = [
            kSecClass: kSecClassGenericPassword,
            kSecAttrAccount: key,
        ]
        SecItemDelete(query as CFDictionary)
    }
}
