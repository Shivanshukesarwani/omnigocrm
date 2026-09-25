# OmniGoCRM Sales

OmniGoCRM now has first-class sales records for quotation, order and payment workflows.

## Entities

- `Product` — catalog item with SKU, price, currency, description and active flag.
- `Quote` — quotation header with dates, status, customer linkage and totals.
- `QuoteItem` — quotation line item with quantity, pricing, discount and tax.
- `Order` — sales order created directly or converted from a quote.
- `OrderItem` — sales order line item.
- `Payment` — payment record with method, provider/reference data and related order/quote/customer IDs.

All are workspace-scoped.

## Workflow

1. Create a Quote.
2. Add QuoteItems.
3. Totals are recalculated.
4. Mark the quote accepted/sent/draft as appropriate.
5. Convert the Quote to an Order. Items and totals are copied.
6. Add OrderItems as needed.
7. Record Payments against the order/quote/customer.

## API

~~~text
POST /api/v1/OmniGoCRM/Sales/quote
POST /api/v1/OmniGoCRM/Sales/quoteItem
POST /api/v1/OmniGoCRM/Sales/quoteConvert
POST /api/v1/OmniGoCRM/Sales/orderItem
POST /api/v1/OmniGoCRM/Sales/payment
~~~

Example Quote:

~~~json
{
  "name": "Website Design Proposal",
  "leadId": "LEAD_ID",
  "currency": "INR",
  "validUntil": "2026-10-10",
  "notes": "Includes website setup and one year of support."
}
~~~

Example QuoteItem:

~~~json
{
  "quoteId": "QUOTE_ID",
  "name": "Website Development",
  "quantity": 1,
  "unitPrice": 25000,
  "discountAmount": 2000,
  "taxAmount": 4140
}
~~~

Example Payment:

~~~json
{
  "orderId": "ORDER_ID",
  "amount": 10000,
  "currency": "INR",
  "method": "UPI",
  "status": "Paid",
  "referenceNumber": "UPI-REFERENCE"
}
~~~

The payment model is provider-neutral. Gateway webhooks and automatic reconciliation are still separate work.
