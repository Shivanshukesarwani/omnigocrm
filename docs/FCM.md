# Firebase Cloud Messaging

OmniGoCRM has three layers for mobile notifications:

1. Database notifications are always supported by the EspoCRM backend.
2. Android and iOS clients register FCM tokens after sign-in and workspace selection.
3. The backend queues push delivery when it creates a user notification and sends it through Firebase Cloud Messaging HTTP v1.

Device tokens are registered through `POST /api/v1/OmniGoCRM/Devices/register`. Registration requires authentication and an active workspace membership; it is upserted by the app installation ID and user.

## Enable push delivery

Create a Firebase Android app whose package/application ID matches:

`com.shivanshu.crm`

Download `google-services.json` from Firebase and place it at:

`android/app/google-services.json`

Do not commit project-specific Firebase files or server credentials to a public repository.

For iOS, add an iOS app with bundle ID `com.omnigocrm.ios` to the same Firebase project, enable the Apple Push Notifications service, and upload an APNs authentication key in Firebase Project Settings. Place the downloaded `GoogleService-Info.plist` at `ios/OmniGoCRM/GoogleService-Info.plist`; XcodeGen includes it when present. The generated iOS target has development and production `aps-environment` entitlements. Use an Apple development or distribution provisioning profile that includes Push Notifications for the matching environment. Do not commit the plist.

## Server credentials

Enable the Firebase Cloud Messaging API for the project. Configure the service-account JSON in the server's protected EspoCRM configuration as `omniGoCRMFCMServiceAccountJson`. The service account needs permission to send Firebase Cloud Messaging messages. Keep this value outside Git and the public web root; rotate its key according to your organization's security policy. When the value is empty, database notifications continue to work and push jobs do nothing.

## Current notification behavior

Each new EspoCRM notification schedules a background push job. Push payloads intentionally contain generic lock-screen text plus the notification ID, not CRM record details. Invalid/unregistered FCM tokens are deactivated. Android notification permission must be granted on Android 13 and later.

Android and iOS FCM registration and notification display are implemented. iOS delivery uses Firebase to route FCM messages through APNs, so an APNs key, correctly signed app, and Firebase iOS configuration are required. Project-specific Firebase files and service-account credentials are environment configuration and are not stored in this repository.
