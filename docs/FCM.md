# Firebase Cloud Messaging

OmniGoCRM has three layers for mobile notifications:

1. Database notifications are always supported.
2. The Android client contains an optional Firebase Messaging service.
3. The Laravel API stores per-user Android device tokens and includes an optional FCM HTTP v1 sender.

## Enable push delivery

Create a Firebase Android app whose package/application ID matches:

`com.shivanshu.crm`

Download `google-services.json` from Firebase and place it at:

`android/app/google-services.json`

Do not commit project-specific Firebase files or server credentials to a public repository unless your security policy explicitly allows it.

On the Laravel server configure:

```env
FCM_PROJECT_ID=your-firebase-project-id
GOOGLE_APPLICATION_CREDENTIALS=/path/to/server-side-service-account.json
```

The Android build is designed so Firebase configuration is optional. Without `google-services.json`, the CRM still builds and its in-app notification system remains available.

## Server credentials

The service-account JSON must stay on the server, outside the public web root. Rotate the service account according to your organization's security policy.

The repository does not contain Firebase service-account credentials.

## Current notification behavior

Follow-up notifications are stored in Laravel's database notification table. The Android client can display them from the CRM API.

Push delivery requires the Firebase project and server-side credential setup above; that environment-specific wiring cannot be verified from GitHub alone.
