# OmniGoCRM

Self-hostable multi-tenant sales CRM for web and Android.

Core lifecycle: Lead -> Contact -> Customer.

Features in this codebase include WhatsApp click-to-chat templates, follow-ups, native calling, call activity logging, private call recordings with admin-only access, products and quotations, REST API, and an Android client.

Production backend: Laravel 13 / PHP 8.3+ with MySQL or MariaDB. Local development can use SQLite.

Call recording on Android remains device/OEM/carrier dependent and must be tested on the exact devices used in production.

See `docs/START_HERE.md` for installation.
