# Shared Hosting

Required: PHP 8.3 or newer, MySQL or MariaDB, and a way to install Composer dependencies.

Recommended layout:

/home/account/omnigocrm/backend
/home/account/your-domain/public_html

The domain should serve backend/public. Keep the Laravel application code outside public_html when the hosting panel allows it.

Set APP_URL, the MySQL connection variables, SESSION_DRIVER=database, CACHE_STORE=database, and FILESYSTEM_DISK=local.

Private call recordings must be writable by PHP but must not be directly downloadable from a public URL.

Run database migrations during deployment before enabling a new application release.
