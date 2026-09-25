# Hosting without SSH / terminal

If your Premium Hosting control panel has no SSH/terminal, use this process.

## On your Windows PC

1. Install PHP 8.3+ and Composer.
2. Open a Command Prompt in `backend/`.
3. Run:

```bat
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize
```

4. Upload the complete `backend/` folder **including `vendor/`** to the hosting account.
5. Set the domain/subdomain document root to the `backend/public` directory.
6. Copy `backend/.env.example` to `.env` and change it to your production MySQL credentials and HTTPS URL.
7. If your control panel has a PHP selector, choose PHP 8.3–8.5.
8. Make `storage/` and `bootstrap/cache/` writable by the web server.

Do not put `.env`, `vendor`, `app`, `config`, or `database` directly inside a public `public_html` folder where they can be downloaded. The web-visible root must be the Laravel `public` folder.
