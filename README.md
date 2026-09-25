# OmniGoCRM

Self-hostable, multi-tenant CRM for web and Android.

Core lifecycle: Lead -> Contact -> Customer.

Modules: leads, contacts, customers, companies, tags, custom fields, follow-ups, tasks, pipeline, products/services, quotations, orders, payments, CSV import, teams/roles, audit logs, private call recordings, WhatsApp click-to-chat templates, REST API, and Android client.

Production stack: PHP 8.3+, Laravel 13, MySQL/MariaDB, Blade and responsive CSS, native Android/Kotlin.

GitHub stores source code. PHP hosting runs the web application with the domain pointed at backend/public. Android uses the Laravel REST API.

WhatsApp initially uses wa.me click-to-chat with CRM templates and editable prefilled text. Official WhatsApp Business Platform automation is a separate future integration.

Cellular call recording is device/OEM/carrier/OS dependent and cannot be guaranteed on every Android device. Recording access is intended for Super Admin/Admin only and production use must follow applicable consent and recording requirements.

See docs/START_HERE.md and docs/SHARED_HOSTING.md.
