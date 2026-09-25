# WhatsApp

OmniGoCRM uses WhatsApp click-to-chat in the first release.

The CRM stores reusable message templates and fills variables such as:

- {first_name}
- {last_name}
- {company}
- {requirement}
- {salesperson}
- {company_name}
- {phone}

The user reviews and can edit the message before WhatsApp opens with the prefilled text.

This is intentionally not the official WhatsApp Business Platform API. It does not silently send messages and it does not bypass WhatsApp user interaction.

A future official integration should be implemented as a separate provider with webhook verification, approved message templates, consent handling, rate limiting and delivery status tracking.
