# EspoCRM Migration Plan

## Goal

OmniGoCRM has completed the architectural migration to EspoCRM as its sole CRM/data backend.

EspoCRM provides the mature CRM foundation: entities, relationships, ACL, metadata, REST API, layouts, scheduled jobs and extension/module mechanisms.

## Repository layout

```
omnigocrm/
├── application/              # EspoCRM application
├── client/                   # EspoCRM frontend
├── custom/                   # OmniGoCRM custom backend modules
├── client/custom/            # OmniGoCRM custom frontend
├── android/                  # Android client
├── ios/                      # iOS client
├── docs/                     # architecture/deployment docs
└── tests/                    # project-specific tests
```

## Completed migration stages

1. Bring EspoCRM source into the development branch.
2. Build and validate the upstream application.
3. Add OmniGoCRM module scaffolding.
4. Recreate CRM requirements using EspoCRM entities/metadata.
5. Add WhatsApp and telephony provider abstractions.
6. Add SaaS tenant/workspace functionality.
7. Connect native clients to the EspoCRM REST API.
8. Port useful product functionality into the active architecture.
9. Add automated tests.
10. Remove the obsolete secondary backend from the repository.

## Rules

- Do not introduce a second CRM backend.
- Do not duplicate EspoCRM entities, authentication, tenancy or CRM routes in another application.
- Keep OmniGoCRM-specific backend code under `custom/Espo/Modules/OmniGoCRM/`.
- Keep frontend customizations under `client/custom/modules/omni-go-crm/`.
- Do not put credentials in Git.
- Do not use unofficial WhatsApp browser automation as the core integration.
- Do not promise universal Android call recording.
- Do not remove EspoCRM license/copyright notices.
