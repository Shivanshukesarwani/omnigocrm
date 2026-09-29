# OmniGoCRM iOS

Native SwiftUI client for the EspoCRM-based OmniGoCRM API.

## Configuration

Edit `ios/OmniGoCRM/AppConfig.swift` and set the public HTTPS CRM URL.

The client authenticates with EspoCRM's documented `GET /api/v1/App/user` flow using the `Espo-Authorization` header, then reuses the returned token for API requests.

## Project generation

This repository uses XcodeGen so the Xcode project is reproducible from `project.yml`.

~~~text
brew install xcodegen
cd ios
xcodegen generate
~~~

Open `OmniGoCRM.xcodeproj` in Xcode.

For push notifications, add the iOS app to Firebase using bundle ID `com.omnigocrm.ios`, save its `GoogleService-Info.plist` in `ios/OmniGoCRM/`, and configure an APNs authentication key in Firebase. Sign with an Apple provisioning profile that includes Push Notifications. The project uses Firebase Messaging through Swift Package Manager; setup details are in `docs/FCM.md`.

## Current native features

- Sign in
- Dashboard
- Leads
- Create lead
- Lead detail
- Lead conversion to Contact
- Contacts
- Accounts
- Opportunities
- Tasks
- Meetings
- Calls
- Products
- Quotes, quote line items, quote-to-order conversion, orders and payment recording
- WhatsApp broadcast campaigns, lead recipients and scheduling
- WhatsApp message inbox
- WhatsApp text send from an opted-in lead

Additional web parity layers such as offline sync, notification deep-link routing, richer record editing, camera/media upload, and background synchronization can be built on the same API client.
