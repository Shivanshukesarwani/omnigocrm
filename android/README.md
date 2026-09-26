# OmniGoCRM for Android

The native Android client connects to an OmniGoCRM server.

## Before running

Open:

`app/src/main/java/com/omnigocrm/android/AppConfig.kt`

Set the base URL of your OmniGoCRM server, for example:

```kotlin
const val BASE_URL = "https://crm.example.com/"
```

Use the server root URL. The app adds the API path itself.

The Android application ID is `com.omnigocrm.android`.

## Build

Open the `android/` folder in Android Studio. Let Gradle sync. Use JDK 17.

## Features

- Login
- Dashboard
- Leads
- Add lead
- Lead details
- Lead → Contact conversion
- Contacts
- Workspace creation and switching
- WhatsApp inbox and opted-in messaging
- One-click native calling
- Call activity logging
- Tasks and follow-ups
