# API

Base URL:

https://your-crm-domain/api/

Authentication:

POST /login with email and password. The API returns a bearer token. Send it as:

Authorization: Bearer TOKEN

Core endpoints include dashboard, leads, lead conversion, contacts, customer conversion, customers, follow-ups, calls, message templates, WhatsApp URL generation, companies, tasks, quotations, orders, payments, products and notifications.

The Android client uses the same backend API and therefore shares the same CRM records as the web application.

All authenticated API requests are scoped to the user's workspace.
