# Start Here — Non-Developer Guide

## What you received

You have two parts that work together:

1. **backend/** = the CRM website + API + database logic.
2. **android/** = the mobile CRM application.

Do not mix files between these folders.

## 1) Local web CRM on Windows

Recommended local tools:

- PHP 8.3+
- Composer
- MySQL/MariaDB, or SQLite for first testing
- VS Code

Inside `backend/`:

```text
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then open:

`http://127.0.0.1:8000`

Demo accounts after seeding:

- Admin: `admin@shivanshu.local` / `ChangeMe123!`
- Sales: `sales@shivanshu.local` / `ChangeMe123!`

Change these passwords immediately.

## 2) Hosting

Laravel 13 requires PHP 8.3+. The hosting must support the PHP extensions required by Laravel and a writable `storage` and `bootstrap/cache` directory.

For ordinary hosting, point the domain/subdomain document root to:

`backend/public`

Do **not** make the entire Laravel project web-accessible. Only the `public` directory should be directly served.

Create a MySQL database in your hosting panel and put its credentials in `.env`.

Typical production values:

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
```

After uploading:

```text
composer install --no-dev --optimize-autoloader
php artisan key:generate   # only on first installation
php artisan migrate --seed
php artisan storage:link
php artisan optimize
```

If your hosting does not provide terminal/SSH access, ask the hosting provider to run those commands or use its PHP/Composer interface. Do not manually expose `.env`.

## 3) Android app

Open the **android/** folder in Android Studio.

Use a current Android Studio version compatible with the included Android Gradle Plugin configuration. The project currently targets AGP 9.4.0 / Gradle 9.6 / JDK 17.

Open:

`android/app/src/main/java/com/shivanshu/crm/AppConfig.kt`

and set:

```kotlin
const val BASE_URL = "https://your-crm-domain.com/"
```

Then Sync Gradle and Run.

## 4) WhatsApp behavior

The CRM uses WhatsApp click-to-chat links. A message template is generated from the lead/contact data and the phone's WhatsApp app is opened with the message pre-filled. The user still presses Send in WhatsApp.

This is intentionally different from an unofficial WhatsApp Web automation system and does not require a Chrome extension.

## 5) Call recordings

The Android app logs calls and can attempt a private recording workflow where supported. Recording support varies by phone, Android version, manufacturer and carrier. The app must not be marketed as guaranteeing cellular-call recording on every Android device.

Recordings uploaded to Laravel go under private application storage and are served only through an authenticated admin endpoint.

## 6) First test checklist

- Log into web CRM as admin.
- Create a lead.
- Open WhatsApp button and verify the generated text.
- Convert the lead to Contact.
- Convert the contact to Customer.
- Create a follow-up.
- Log a call.
- On Android, log in and open the same lead.
- Test one-click calling.
- Confirm non-admin users cannot access recording endpoints.

## 7) What to do next

After the first installation is working, the next development phase should add full quotation/invoice workflows, attachments, push notifications, advanced automations, audit trails and hardened production security.
