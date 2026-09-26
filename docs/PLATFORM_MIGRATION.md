# Platform Migration Plan

## Why migrate

The original OmniGoCRM prototype implemented CRM, SaaS and mobile concepts independently. The current CRM platform supplies core entities, relationships, access control, metadata, REST API, layouts, scheduled jobs, and module support.

The migration uses the existing CRM engine rather than continuing to duplicate those foundations.

## Repository layout during migration

```
omnigocrm/
├── application/              # CRM backend
├── client/                   # CRM web client
├── custom/                   # OmniGoCRM custom backend modules
├── client/custom/            # OmniGoCRM custom frontend
├── mobile/                   # OmniGoCRM mobile clients
├── docs/                     # architecture/deployment docs
└── legacy/                   # old Laravel prototype after migration
```

## Migration stages

1. Establish the CRM platform as the application foundation.
2. Build and run the core application.
3. Add OmniGoCRM module scaffolding.
4. Recreate the CRM requirements using native entities and metadata.
5. Add WhatsApp and telephony provider abstractions.
6. Add SaaS tenant/workspace functionality.
7. Connect the mobile clients to the CRM REST API.
8. Port useful prototype functionality.
9. Add automated tests.
10. Move the Laravel prototype to `legacy/` only after parity is verified.

## Do not do

- Do not duplicate CRM-core behavior in Laravel controllers.
- Do not put credentials in Git.
- Do not use unofficial WhatsApp browser automation as the core integration.
- Do not promise universal Android call recording.
- Do not remove required license and copyright notices.
