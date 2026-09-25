# Start Here

OmniGoCRM is a PHP/Laravel CRM with a native Android client.

Create a MySQL or MariaDB database, deploy the backend directory to PHP hosting, point the CRM domain document root to backend/public, configure backend/.env, run composer install, run php artisan key:generate, then run php artisan migrate --seed.

Configure the Android API base URL in mobile/app/build.gradle.kts.

Call recordings must remain in private filesystem storage and must never be placed in public web uploads.
