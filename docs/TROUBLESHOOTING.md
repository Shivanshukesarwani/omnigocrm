# Troubleshooting

## 500 error after deployment

Check PHP version, APP_KEY, database credentials, storage permissions and backend/bootstrap/cache permissions.

## Database connection error

Verify DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD. Confirm the database user has permissions on the database.

## CSS missing

The domain should point to backend/public, not backend.

## Login loops back to login

Check session storage, APP_URL and the database sessions table.

## Calls are logged but not recorded

This can be normal on Android. Check microphone/phone permissions and test the exact device/carrier. The CRM still records the call activity.

## WhatsApp opens but text is not sent automatically

That is intentional. The first release opens a user-reviewed click-to-chat message rather than using the official WhatsApp API.
