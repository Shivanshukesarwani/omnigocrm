# Start Here

OmniGoCRM is an EspoCRM-based omnichannel CRM SaaS with native Android and iOS clients.

## 1. Backend

The only active backend is EspoCRM plus the OmniGoCRM custom module.

For local development, use the repository's Docker/Compose setup or the documented EspoCRM installation procedure.

Key locations:

`custom/Espo/Modules/OmniGoCRM/` — backend/module code

`client/custom/modules/omni-go-crm/` — frontend customizations

`android/` — Android client

`ios/` — iOS client

## 2. Production

Use the EspoCRM deployment procedure documented in `docs/DEPLOYMENT.md`.

Production should use HTTPS, a supported PHP version, MariaDB/MySQL, scheduled jobs, protected application storage and regular backups.

## 3. Android app

The canonical Android project is the `android/` directory.

Open it in Android Studio and configure the production API address in:

`android/app/src/main/java/com/shivanshu/crm/AppConfig.kt`

The mobile client uses the same EspoCRM/OmniGoCRM API as the web platform.

## 4. WhatsApp

OmniGoCRM uses the official WhatsApp Cloud API integration for server-side messaging. Provider credentials and webhook signing secrets belong in the deployment environment, never in Git.

## 5. Calling

Calling uses the native device dialer/call-tracking foundation. Cellular recording is device and carrier dependent and must be tested on the exact devices used by the team.

## 6. SaaS lifecycle

Workspaces, memberships, workspace switching and tenant-aware records are implemented through the OmniGoCRM module on EspoCRM.

## 7. Excel / CSV imports

Lead import is handled through EspoCRM's import capabilities plus OmniGoCRM field mapping. Verify required fields and workspace assignment before importing production data.

## 8. First production test

Verify the full workflow with a real test account:

Lead → Contact → Opportunity → Quote → Order → Payment.

Also test WhatsApp, native calling, call activity logging, notification delivery, workspace separation, backups and user-role restrictions.
