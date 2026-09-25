# OmniGoCRM

Self-hostable multi-tenant CRM SaaS for web and Android.

## Product modules

Lead -> Contact -> Customer lifecycle, with:
- companies and workspaces
- lead assignment and search
- tags and custom fields
- follow-ups and tasks
- situation-based WhatsApp click-to-chat templates
- native calling and call activity
- private call-recording upload with admin-only web playback route
- products and services
- quotations
- orders / sales
- payments
- audit logging
- bulk lead import API
- Android client with shared REST API

## Stack

- Laravel 13
- PHP 8.3+
- MySQL/MariaDB in production
- SQLite for local testing
- Blade web UI
- JSON REST API
- native Android/Kotlin client

## Important deployment facts

GitHub is source control, not the production runtime. The Laravel application runs on PHP hosting with the document root pointed at backend/public.

The WhatsApp integration intentionally uses click-to-chat URLs and does not depend on a Chrome extension. Official WhatsApp Business Platform automation can be added later.

Android cellular call recording is device/OEM/carrier dependent and cannot be guaranteed on every phone. The CRM still records call activity even when recording is unavailable.

See docs/START_HERE.md and docs/SAAS.md for setup and architecture.
