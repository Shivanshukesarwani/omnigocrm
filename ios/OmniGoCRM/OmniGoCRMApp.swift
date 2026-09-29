import SwiftUI

@main
struct OmniGoCRMApp: App {
    @StateObject private var session = SessionStore()
    @UIApplicationDelegateAdaptor(FCMPushAppDelegate.self) private var pushDelegate

    var body: some Scene {
        WindowGroup {
            RootView(session: session, pushDelegate: pushDelegate)
        }
    }
}
