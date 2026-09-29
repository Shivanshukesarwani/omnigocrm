import FirebaseCore
import FirebaseMessaging
import UIKit
import UserNotifications

final class FCMPushAppDelegate: NSObject, UIApplicationDelegate, UNUserNotificationCenterDelegate, MessagingDelegate {
    static let tokenDidChange = Notification.Name("OmniGoCRMFCMTokenDidChange")
    static let savedTokenKey = "OmniGoCRMFCMToken"

    private(set) var isConfigured = false

    func application(
        _ application: UIApplication,
        didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil
    ) -> Bool {
        guard Bundle.main.url(forResource: "GoogleService-Info", withExtension: "plist") != nil else {
            return true
        }

        FirebaseApp.configure()
        isConfigured = true
        UNUserNotificationCenter.current().delegate = self
        Messaging.messaging().delegate = self
        return true
    }

    func requestAuthorization() async -> Bool {
        guard isConfigured else { return false }
        let center = UNUserNotificationCenter.current()
        let settings = await center.notificationSettings()
        let authorized: Bool

        if settings.authorizationStatus == .notDetermined {
            authorized = (try? await center.requestAuthorization(options: [.alert, .badge, .sound])) ?? false
        } else {
            authorized = settings.authorizationStatus == .authorized || settings.authorizationStatus == .provisional
        }

        if authorized {
            UIApplication.shared.registerForRemoteNotifications()
        }
        return authorized
    }

    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        Messaging.messaging().apnsToken = deviceToken
    }

    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: Error) {
        NSLog("OmniGoCRM could not register for remote notifications: %@", error.localizedDescription)
    }

    func messaging(_ messaging: Messaging, didReceiveRegistrationToken fcmToken: String?) {
        guard let fcmToken, !fcmToken.isEmpty else { return }
        UserDefaults.standard.set(fcmToken, forKey: Self.savedTokenKey)
        NotificationCenter.default.post(name: Self.tokenDidChange, object: fcmToken)
    }

    func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        willPresent notification: UNNotification,
        withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void
    ) {
        completionHandler([.banner, .sound, .badge])
    }
}
