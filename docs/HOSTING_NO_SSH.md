# Hosting without SSH / terminal

If the hosting control panel has no SSH/terminal, use the host's supported PHP/Composer deployment workflow or upload a pre-built, verified EspoCRM release artifact.

## On your Windows PC

1. Install the PHP and Composer versions supported by the pinned EspoCRM release.
2. Prepare the EspoCRM application and OmniGoCRM custom module locally.
3. Install the production Composer dependencies.
4. Build/prepare the EspoCRM frontend assets.
5. Upload the complete verified application artifact and required writable data directories.
6. Configure the production database and environment settings in the hosting panel.
7. Set the domain/subdomain document root to the EspoCRM public application directory.
8. Configure scheduled jobs according to the EspoCRM release instructions.
9. Enable HTTPS.
10. Verify workspace isolation, authentication, scheduled automation, WhatsApp webhooks and backups.

Do not place environment secrets, private attachments or internal application files in a publicly downloadable directory.
