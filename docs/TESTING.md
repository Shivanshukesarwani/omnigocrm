# OmniGoCRM local testing

This is the simplest way to run a disposable local test instance before connecting real WhatsApp, Meta, Google, Stripe, Razorpay, or production domains.

## Requirements

- Docker Desktop (Windows/macOS) or Docker Engine + Compose (Linux)
- Git
- At least 6 GB RAM available to Docker is recommended.

## Start

From the repository root:

```bash
git pull

docker compose -f docker-compose.test.yml up -d --build
```

Or on Linux/macOS:

```bash
chmod +x scripts/test-local.sh scripts/test-local-down.sh
./scripts/test-local.sh
```

Then open:

**http://localhost:8081**

The first startup uses the normal EspoCRM setup wizard. Create a local administrator account in that wizard. Do not reuse a production password.

The test database credentials are intentionally non-secret and exist only in `docker-compose.test.yml`.

## Stop

```bash
docker compose -f docker-compose.test.yml down
```

To completely reset the test installation, including its database:

```bash
docker compose -f docker-compose.test.yml down -v
```

## Basic smoke test

After the setup wizard finishes:

1. Log in at `http://localhost:8081`.
2. Open Dashboard.
3. Create a Lead.
4. Add a Contact and Account.
5. Create an Opportunity and move it through its stages.
6. Create a Task and mark it complete.
7. Open the WhatsApp inbox.
8. Create a WhatsApp template record.
9. Test the customer timeline.
10. Test CSV lead import using the columns in `docs/LEAD_IMPORT.md`.
11. Test website/API lead capture only with local/test credentials.
12. Review automation rules and automation runs.
13. Review dashboard pipeline and sales totals.

## Integration testing

Real provider integrations should be tested only after the local CRM workflow works:

- WhatsApp Cloud API: configure the official Cloud API credentials and webhook URL.
- Meta Lead Ads: configure the Meta verify token, app secret and Graph API key.
- Google Lead Ads: configure the Google lead endpoint key.
- Stripe/Razorpay: use provider test/sandbox credentials and webhook signing secrets.

Never commit provider secrets to GitHub.

## Troubleshooting

Check service state:

```bash
docker compose -f docker-compose.test.yml ps
```

View application logs:

```bash
docker compose -f docker-compose.test.yml logs --tail=200 omnigocrm-test
```

View database logs:

```bash
docker compose -f docker-compose.test.yml logs --tail=200 db-test
```

If the installation becomes corrupted during testing, reset it with `down -v` and start again.
