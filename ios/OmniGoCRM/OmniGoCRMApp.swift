import SwiftUI

@main
struct OmniGoCRMApp: App {
    @StateObject private var session = SessionStore()

    var body: some Scene {
        WindowGroup {
            RootView(session: session)
        }
    }
}
