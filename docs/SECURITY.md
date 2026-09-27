# Security notes

## Call recordings

Call recordings must use protected/private storage and authenticated, authorized API access.

Recording access must be enforced by the active EspoCRM/OmniGoCRM authorization layer.

Sales users can create/upload recordings associated with their own calls, but they do not get an unrestricted playback endpoint through the web CRM.

## Change defaults

Immediately change demo passwords in seeded/test accounts before production use.

## HTTPS

Use HTTPS everywhere for the web CRM and Android API. The Android manifest disables clear-text HTTP.

## Authentication

Android API tokens are random bearer tokens stored only as SHA-256 hashes in the database.

## Recording consent

Call recording laws vary. Configure internal policy and user/customer consent as required for the countries and states where the CRM will be used.

## Tenant isolation

Workspace-aware records and API operations must remain subject to OmniGoCRM workspace access controls. Cross-workspace read, create, update and delete paths must be covered by integration tests before production SaaS launch.
