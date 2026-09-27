# Shared Hosting

OmniGoCRM should be deployed using a hosting environment that meets the PHP, database and scheduled-job requirements of the pinned EspoCRM release.

Required:

- Supported PHP version for the pinned EspoCRM release
- MySQL/MariaDB
- Composer
- HTTPS
- Scheduled jobs/cron
- Persistent writable application data
- Regular database and file backups

The web server should serve the EspoCRM public application directory. Keep environment configuration, private attachments and application data outside directly downloadable public paths.

For shared hosting, follow the EspoCRM installation and deployment procedure for the exact version being deployed. Do not assume generic PHP application paths or commands from an unrelated framework.
