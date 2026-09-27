# Production secrets

Create these files before starting the production Compose stack:

- `db_password.txt`
- `db_root_password.txt`
- `admin_password.txt`

Example:

```bash
mkdir -p secrets
openssl rand -base64 36 > secrets/db_password.txt
openssl rand -base64 36 > secrets/db_root_password.txt
openssl rand -base64 36 > secrets/admin_password.txt
chmod 600 secrets/*.txt
```

Never commit these files.
