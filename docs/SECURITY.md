# Security notes

## Call recordings

Call recordings are stored on the Laravel private disk, not in the public web directory.

The web recording route requires an authenticated CRM user and the `admin` or `super_admin` role.

Sales users can create/upload recordings associated with their own calls, but they do not get a playback endpoint through the web CRM.

## Change defaults

Immediately change the demo passwords in the seeded accounts before production use.

## HTTPS

Use HTTPS everywhere for the web CRM and Android API. The Android manifest disables clear-text HTTP.

## Authentication

Android API tokens are random bearer tokens stored only as SHA-256 hashes in the database.

## Recording consent

Call recording laws vary. Configure internal policy and user/customer consent as required for the countries and states where the CRM will be used.
