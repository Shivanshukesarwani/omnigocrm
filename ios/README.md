# OmniGoCRM iOS

Native SwiftUI client for the OmniGoCRM API.

## Configuration

Edit `ios/OmniGoCRM/AppConfig.swift` and set the public HTTPS CRM URL.

The client authenticates through the server's native `GET /api/v1/App/user` flow using the required `Espo-Authorization` header, then reuses the returned token for API requests.

## Project generation

This repository uses XcodeGen so the Xcode project is reproducible from `project.yml`.

~~~text
brew install xcodegen
cd ios
xcodegen generate
~~~

Open `OmniGoCRM.xcodeproj` in Xcode.

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
- WhatsApp message inbox
- WhatsApp text send from an opted-in lead

Additional web parity layers such as offline sync, push routing, richer record editing, camera/media upload, and background synchronization can be built on the same API client.
