# Firebase Cloud Messaging

OmniGoCRM has three layers for mobile notifications:

1. Database notifications are always supported by the EspoCRM backend.
2. The Android client contains an optional Firebase Messaging service.
3. Push delivery is connected to the EspoCRM/OmniGoCRM device-registration and notification APIs.

## Enable push delivery

Create a Firebase Android app whose package/application ID matches:

`com.shivanshu.crm`

Download `google-services.json` from Firebase and place it at:

`android/app/google-services.json`

Do not commit project-specific Firebase files or server credentials to a public repository.

## Server credentials

If server-side Firebase credentials are required by the selected push provider, keep them outside the public web root and outside Git. Rotate service credentials according to your organization's security policy.

## Current notification behavior

OmniGoCRM notifications are stored and delivered through the EspoCRM/OmniGoCRM backend. The Android client can display in-app notifications and register its device for push delivery.

Environment-specific Firebase wiring must be configured in the deployment environment and is not stored in this repository.
