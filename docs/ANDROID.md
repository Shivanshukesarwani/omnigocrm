# Android App

The canonical Android project is `android/`.

## Setup

Open the `android/` directory in Android Studio.

The app uses the EspoCRM/OmniGoCRM REST API. Set the backend address in:

`android/app/src/main/java/com/shivanshu/crm/AppConfig.kt`

Use HTTPS for production.

## Included workflows

The mobile client is being brought to feature parity with the web platform. Current native workflows include:

- Dashboard
- Quotes, quote line items, quote-to-order conversion, orders and payment recording
- WhatsApp broadcast campaigns, lead recipients and scheduling
- Leads
- Contacts
- Pipeline
- Tasks
- Calling
- WhatsApp conversation inbox, thread history, read/close/reopen, and consent-checked replies
- FCM device registration and notification display (requires project Firebase configuration)

Follow-ups, core sales documents and WhatsApp broadcast workflows are available in the native clients. Notification and offline-sync workflows still have roadmap gaps.

## Calling

The app starts its CRM call-tracking foreground service before launching the native dialer. It records call activity after the phone reports the call state.

Two-way cellular recording is not guaranteed by Android. Test the exact devices used by your team.

## Permissions

The application may request phone call, phone state, microphone, foreground microphone service and notification permissions.

Grant only the permissions needed for the features you enable.

## Build

Repository CI builds the app from `android/` with Java 17 and Gradle.

For release builds, configure Android signing in Android Studio. Do not commit keystores or signing passwords to GitHub.
