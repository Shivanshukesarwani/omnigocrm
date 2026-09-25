# Start Here

OmniGoCRM is a multi-tenant PHP/Laravel CRM with a native Android client.

## 1. Backend installation

Create a MySQL/MariaDB database. For local development, SQLite is also supported.

Inside the `backend/` directory:

```text
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan optimize
php artisan serve
```

Open the local CRM at `http://127.0.0.1:8000`.

Before seeding a fresh installation, set the admin/sales credentials in `.env`:

```env
CRM_ADMIN_EMAIL=admin@example.com
CRM_ADMIN_PASSWORD=change-this-password
CRM_SALES_EMAIL=sales@example.com
CRM_SALES_PASSWORD=change-this-password-too
```

## 2. Shared PHP hosting

Production runs on PHP 8.3+ with MySQL/MariaDB. GitHub is source control; GitHub Pages is not the CRM runtime.

Point the CRM subdomain document root to:

```text
backend/public
```

Keep the Laravel application code, `.env`, Composer dependencies and private call recordings outside the public document root where your host permits.

Use:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
CRM_COMPANY_NAME="Your Business"
CRM_TIMEZONE="Asia/Kolkata"
```

Production install:

```text
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan optimize
```

## 3. Android app

The canonical Android project is the `android/` directory.

Open `android/` in Android Studio and let Gradle sync. Set the production API address in:

```text
android/app/src/main/java/com/shivanshu/crm/AppConfig.kt
```

The mobile app shares the same Laravel API and includes the CRM dashboard, leads, contacts, customers, follow-ups, companies, tasks, products, quotations, orders, payments, notifications, WhatsApp and calling.

See `docs/ANDROID.md` and `docs/CALL_RECORDING.md`.

## 4. WhatsApp

The first release uses click-to-chat with editable, situation-based templates. The CRM generates a prefilled WhatsApp URL; the user reviews and sends the message inside WhatsApp.

No Chrome extension is required.

## 5. Call recording

Call recording is best-effort on Android because cellular recording depends on the exact device, OEM, carrier and OS. Calls are still logged when recording is unavailable.

Recordings uploaded to Laravel are private. Only Super Admin/Admin can access the web playback route, and recording access is audited.

## 6. SaaS lifecycle

A new business can use `/signup` to create its workspace and owner account. New workspaces start with a 14-day trial.

The CRM keeps data separated by workspace and supports Super Admin, Admin, Manager and Sales roles.

## 7. Excel / CSV imports

Use the Lead Import screen for CSV and Excel files. Required fields are:

- `first_name`
- `mobile`

Optional fields include last name, company, email, WhatsApp, source, status, pipeline stage, requirement and notes.

## 8. First production test

Verify the full workflow with a real test account:

Lead → Contact → Customer → Quotation → Order → Payment.

Also test WhatsApp, native calling, call activity logging, recording permissions, notification delivery, workspace separation, backups and user-role restrictions.
