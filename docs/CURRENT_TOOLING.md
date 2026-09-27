# Current Tooling

## Backend

- EspoCRM — sole CRM/data backend
- PHP version supported by the pinned EspoCRM release
- Composer for dependency management
- MariaDB/MySQL
- OmniGoCRM custom PHP modules under `custom/Espo/Modules/OmniGoCRM/`

## Frontend

- EspoCRM web client
- OmniGoCRM custom frontend under `client/custom/modules/omni-go-crm/`

## Mobile

- Native Android
- Native iOS
- Shared EspoCRM/OmniGoCRM REST API

## Infrastructure

- Docker / Docker Compose for development
- MariaDB
- Reverse proxy / HTTPS for production
- Scheduled jobs for CRM and automation
- GitHub Actions for CI

## Integrations

- WhatsApp Cloud API
- Meta lead ingestion
- Google lead ingestion
- Telephony providers
- Firebase/APNs for mobile notifications
- Billing provider integrations

## Dependency policy

Pin production versions/digests and validate upgrades in CI. Do not introduce a second application backend or duplicate CRM foundations outside EspoCRM.

Check the official release documentation for each dependency before upgrading it.
