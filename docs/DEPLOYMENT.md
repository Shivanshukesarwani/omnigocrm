# Deployment

Release sequence:

1. Put the code on the production server.
2. Install Composer dependencies in backend.
3. Create or update .env.
4. Run php artisan migrate --force.
5. Run php artisan config:cache.
6. Run php artisan route:cache.
7. Ensure storage and bootstrap/cache are writable.
8. Configure the scheduled Laravel command to run every minute or hour as required by the hosting provider.
9. Point the domain to backend/public.
10. Build the Android app with the correct API_BASE_URL.

For shared hosting without SSH, use the hosting panel's Composer/deployment feature or upload the already-installed vendor directory created by your deployment process. Do not expose backend/.env or private storage through public_html.
