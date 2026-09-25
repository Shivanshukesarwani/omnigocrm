# Architecture

```text
                    Laravel 13 + MySQL
                           │
                 ┌─────────┴─────────┐
                 │                   │
              Web CRM            REST API
                 │                   │
          Browser interface     Android App
                                     │
                         ┌───────────┼───────────┐
                         │           │           │
                       Calls     WhatsApp     Notifications
                         │
                    Call activity
                         │
                   Private upload
                         │
                     Admin only
```

## Lifecycle

```text
LEAD ──convert──> CONTACT ──convert──> CUSTOMER
```

The underlying person/company details are carried forward rather than creating a second unrelated record.

## WhatsApp

Templates contain placeholders such as:

- `{first_name}`
- `{last_name}`
- `{company}`
- `{requirement}`
- `{salesperson}`
- `{company_name}`

The API returns a `wa.me` click-to-chat URL.

## Permissions

- `super_admin` — all areas
- `admin` — all CRM areas including call recordings
- `manager` — operational CRM, no recording by default
- `sales` — assigned CRM work, no recording access

The recording route checks both authentication and role before streaming/downloading a file.
