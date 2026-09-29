# OmniGoCRM Mobile

The native mobile clients are API clients, not separate backends.

Planned native applications:
- Android: Kotlin + Jetpack Compose
- iOS: Swift + SwiftUI

The mobile clients use the same /api/v1 authentication, workspace, CRM, inbox and automation contracts as the web application.

Mobile parity requirements:
- Authentication and workspace switching
- Dashboard
- Leads, contacts, accounts and opportunities
- Tasks and activities
- Unified inbox
- Calling and messaging provider integrations
- Push notifications
- Offline cache and queued mutations
- Conflict resolution and sync cursors

No mobile client may introduce a second CRM domain model or a second backend.