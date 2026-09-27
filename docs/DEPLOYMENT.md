# Deployment

OmniGoCRM is deployed as an EspoCRM application with the OmniGoCRM custom module and native clients.

## Release sequence

1. Put the repository/build artifact on the production server.
2. Install the pinned Composer dependencies required by EspoCRM.
3. Configure the production environment and database connection.
4. Run the EspoCRM upgrade/migration procedure required by the selected release.
5. Build/prepare the EspoCRM frontend assets.
6. Configure the web server to serve the EspoCRM public application directory.
7. Configure scheduled jobs for EspoCRM and OmniGoCRM automation.
8. Configure HTTPS and reverse-proxy/security headers.
9. Configure production secrets for WhatsApp, lead sources, billing and push providers.
10. Build the Android/iOS clients with the production API endpoint.

## Production requirements

- MariaDB/MySQL supported by the pinned EspoCRM release.
- PHP version supported by that EspoCRM release.
- Composer for controlled dependency installation.
- HTTPS.
- Persistent writable data directories required by EspoCRM.
- Scheduled jobs/cron.
- Database and file backups.

Do not expose environment secrets, private attachments or application configuration through the public web root.

## Release discipline

Pin the EspoCRM version/digest rather than deploying a floating `latest` container tag. Validate migrations, custom modules and scheduled jobs in CI before production rollout.
