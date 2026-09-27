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
- Leads
- Contacts
- Pipeline
- Tasks
- Calling
- Device registration

Additional follow-up, WhatsApp, sales-document, broadcast, notification and offline-sync workflows remain on the implementation roadmap.

## Calling

The app starts its CRM call-tracking foreground service before launching the native dialer. It records call activity after the phone reports the call state.

Two-way cellular recording is not guaranteed by Android. Test the exact devices used by your team.

## Permissions

The application may request phone call, phone state, microphone, foreground microphone service and notification permissions.

Grant only the permissions needed for the features you enable.

## Build

Repository CI builds the app from `android/` with Java 17 and Gradle.

For release builds, configure Android signing in Android Studio. Do not commit keystores or signing passwords to GitHub.
