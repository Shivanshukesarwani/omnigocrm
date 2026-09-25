# Android CRM App

This is the native Android client for the Shivanshu CRM backend.

## Before running

Open:

`app/src/main/java/com/shivanshu/crm/AppConfig.kt`

Set the HTTPS URL of your Laravel backend, for example:

```kotlin
const val BASE_URL = "https://crm.example.com/"
```

Do not use a trailing `/api/`; the app adds `api/...` itself.

## Build

Open the `android/` folder in Android Studio. Let Gradle sync. Use JDK 17.

## Features in this MVP

- Login
- Dashboard
- Leads
- Add lead
- Lead details
- Lead → Contact conversion
- Contacts
- Contact → Customer conversion
- Customers
- Situation-based WhatsApp templates
- WhatsApp click-to-chat
- One-click native calling
- Call activity logging
- Best-effort device-dependent call recording flow
- Private recording upload to Laravel
- Follow-up list

## Recording limitation

Android/OEM/carrier restrictions mean third-party apps cannot guarantee cellular call audio recording on every device. The provided recorder uses a foreground microphone service and attempts to capture audio where the device permits it. Test on the exact deployment devices and handle consent requirements.
