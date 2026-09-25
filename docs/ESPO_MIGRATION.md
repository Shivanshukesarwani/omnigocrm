# EspoCRM Migration Plan

## Why migrate

The original OmniGoCRM prototype implemented CRM, SaaS and mobile concepts independently. EspoCRM provides a mature CRM platform with entities, relationships, ACL, metadata, REST API, layouts, scheduled jobs and extension/module mechanisms.

The migration therefore uses EspoCRM as the CRM engine rather than continuing to duplicate those foundations.

## Repository layout during migration

```
omnigocrm/
├── application/              # EspoCRM upstream (target)
├── client/                   # EspoCRM frontend (target)
├── custom/                   # OmniGoCRM custom backend modules
├── client/custom/            # OmniGoCRM custom frontend
├── mobile/                   # OmniGoCRM mobile clients
├── docs/                     # architecture/deployment docs
└── legacy/                   # old Laravel prototype after migration
```

## Migration stages

1. Bring EspoCRM source into the development branch.
2. Build and run the unmodified upstream application.
3. Add OmniGoCRM module scaffolding.
4. Recreate the CRM requirements using EspoCRM entities/metadata.
5. Add WhatsApp and telephony provider abstractions.
6. Add SaaS tenant/workspace functionality.
7. Connect the mobile clients to the EspoCRM REST API.
8. Port useful prototype functionality.
9. Add automated tests.
10. Move the Laravel prototype to `legacy/` only after parity is verified.

## Do not do

- Do not replace EspoCRM core with Laravel controllers.
- Do not put credentials in Git.
- Do not use unofficial WhatsApp browser automation as the core integration.
- Do not promise universal Android call recording.
- Do not remove EspoCRM license/copyright notices.
