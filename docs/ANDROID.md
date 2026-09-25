# Android App

The canonical Android project is `android/`.

## Setup

Open the `android/` directory in Android Studio.

The app uses the same Laravel REST API as the web CRM. Set the backend address in:

```text
android/app/src/main/java/com/shivanshu/crm/AppConfig.kt
```

Use HTTPS for production.

## Included screens

- Dashboard
- Leads
- Contacts
- Customers
- Follow-ups
- Companies
- Tasks
- Products / Services
- Quotations
- Orders / Sales
- Payments
- Notifications

Record-level actions include calling, WhatsApp click-to-chat, lead conversion and customer conversion.

## Calling

The app starts its CRM call-tracking foreground service before launching the native dialer. It records call activity after the phone reports the call state.

Two-way cellular recording is not guaranteed by Android. Test the exact devices used by your team.

## Permissions

The application may request phone call, phone state, microphone, foreground microphone service and notification permissions.

Grant only the permissions needed for the features you enable.

## Build

Repository CI builds the app from `android/` with Java 17 and Gradle.

For release builds, configure Android signing in Android Studio. Do not commit keystores or signing passwords to GitHub.
