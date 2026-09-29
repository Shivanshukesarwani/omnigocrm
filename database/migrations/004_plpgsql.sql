-- OmniGoCRM PostgreSQL procedural layer.
-- PL/pgSQL is used for database guarantees that should hold regardless of API/client.

CREATE OR REPLACE FUNCTION omnigo_set_updated_at()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$;

DO $$
DECLARE
  tbl text;
BEGIN
  FOREACH tbl IN ARRAY ARRAY[
    'workspaces','users','pipelines','accounts','contacts','leads','opportunities',
    'tasks','notes','conversations','campaigns','automations','automation_jobs',
    'products','quotes','orders','invoices','workspace_subscriptions','integrations'
  ]
  LOOP
    EXECUTE format('DROP TRIGGER IF EXISTS trg_%I_updated_at ON %I', tbl, tbl);
    EXECUTE format(
      'CREATE TRIGGER trg_%I_updated_at BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION omnigo_set_updated_at()',
      tbl, tbl
    );
  END LOOP;
END;
$$;

-- Keep line-item totals and document totals authoritative in PostgreSQL.
CREATE OR REPLACE FUNCTION omnigo_calculate_quote_item_total()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  NEW.total = round((NEW.quantity * NEW.unit_price) * (1 + NEW.tax_rate / 100), 2);
  RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION omnigo_recalculate_quote_totals()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  UPDATE quotes q
  SET
    subtotal = COALESCE(x.subtotal, 0),
    tax_total = COALESCE(x.tax_total, 0),
    total = COALESCE(x.subtotal, 0) + COALESCE(x.tax_total, 0)
  FROM (
    SELECT
      quote_id,
      SUM(quantity * unit_price) AS subtotal,
      SUM(quantity * unit_price * tax_rate / 100) AS tax_total
    FROM quote_items
    WHERE quote_id = COALESCE(NEW.quote_id, OLD.quote_id)
    GROUP BY quote_id
  ) x
  WHERE q.id = x.quote_id;

  UPDATE quotes
  SET subtotal = 0, tax_total = 0, total = 0
  WHERE id = COALESCE(NEW.quote_id, OLD.quote_id)
    AND NOT EXISTS (
      SELECT 1 FROM quote_items WHERE quote_id = COALESCE(NEW.quote_id, OLD.quote_id)
    );

  RETURN COALESCE(NEW, OLD);
END;
$$;

DROP TRIGGER IF EXISTS trg_quote_items_calculate_total ON quote_items;
CREATE TRIGGER trg_quote_items_calculate_total
BEFORE INSERT OR UPDATE OF quantity, unit_price, tax_rate ON quote_items
FOR EACH ROW EXECUTE FUNCTION omnigo_calculate_quote_item_total();

DROP TRIGGER IF EXISTS trg_quote_items_recalculate_quote ON quote_items;
CREATE TRIGGER trg_quote_items_recalculate_quote
AFTER INSERT OR UPDATE OR DELETE ON quote_items
FOR EACH ROW EXECUTE FUNCTION omnigo_recalculate_quote_totals();

CREATE OR REPLACE FUNCTION omnigo_calculate_order_item_total()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  NEW.total = round((NEW.quantity * NEW.unit_price) * (1 + NEW.tax_rate / 100), 2);
  RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION omnigo_recalculate_order_totals()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  UPDATE orders o
  SET
    subtotal = COALESCE(x.subtotal, 0),
    tax_total = COALESCE(x.tax_total, 0),
    total = COALESCE(x.subtotal, 0) + COALESCE(x.tax_total, 0)
  FROM (
    SELECT
      order_id,
      SUM(quantity * unit_price) AS subtotal,
      SUM(quantity * unit_price * tax_rate / 100) AS tax_total
    FROM order_items
    WHERE order_id = COALESCE(NEW.order_id, OLD.order_id)
    GROUP BY order_id
  ) x
  WHERE o.id = x.order_id;

  UPDATE orders
  SET subtotal = 0, tax_total = 0, total = 0
  WHERE id = COALESCE(NEW.order_id, OLD.order_id)
    AND NOT EXISTS (
      SELECT 1 FROM order_items WHERE order_id = COALESCE(NEW.order_id, OLD.order_id)
    );

  RETURN COALESCE(NEW, OLD);
END;
$$;

DROP TRIGGER IF EXISTS trg_order_items_calculate_total ON order_items;
CREATE TRIGGER trg_order_items_calculate_total
BEFORE INSERT OR UPDATE OF quantity, unit_price, tax_rate ON order_items
FOR EACH ROW EXECUTE FUNCTION omnigo_calculate_order_item_total();

DROP TRIGGER IF EXISTS trg_order_items_recalculate_order ON order_items;
CREATE TRIGGER trg_order_items_recalculate_order
AFTER INSERT OR UPDATE OR DELETE ON order_items
FOR EACH ROW EXECUTE FUNCTION omnigo_recalculate_order_totals();

-- Maintain invoice payment state from payment rows.
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS paid_amount numeric(14,2) NOT NULL DEFAULT 0;

CREATE OR REPLACE FUNCTION omnigo_recalculate_invoice_paid_amount()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
  target_invoice uuid := COALESCE(NEW.invoice_id, OLD.invoice_id);
  paid numeric(14,2);
BEGIN
  SELECT COALESCE(SUM(amount), 0)
  INTO paid
  FROM payments
  WHERE invoice_id = target_invoice
    AND status = 'paid';

  UPDATE invoices
  SET
    paid_amount = paid,
    paid_at = CASE
      WHEN paid >= total AND total > 0 THEN COALESCE(paid_at, now())
      ELSE NULL
    END,
    status = CASE
      WHEN paid >= total AND total > 0 THEN 'paid'
      WHEN paid > 0 THEN 'partially_paid'
      ELSE status
    END
  WHERE id = target_invoice;

  RETURN COALESCE(NEW, OLD);
END;
$$;

DROP TRIGGER IF EXISTS trg_payments_recalculate_invoice ON payments;
CREATE TRIGGER trg_payments_recalculate_invoice
AFTER INSERT OR UPDATE OR DELETE ON payments
FOR EACH ROW EXECUTE FUNCTION omnigo_recalculate_invoice_paid_amount();

-- Queue an automation job atomically when an active automation is explicitly invoked.
CREATE OR REPLACE FUNCTION omnigo_enqueue_automation(
  p_workspace_id uuid,
  p_automation_id uuid,
  p_payload jsonb DEFAULT '{}'::jsonb,
  p_run_at timestamptz DEFAULT now()
)
RETURNS uuid
LANGUAGE plpgsql
AS $$
DECLARE
  job_id uuid;
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM automations
    WHERE id = p_automation_id
      AND workspace_id = p_workspace_id
      AND active = true
  ) THEN
    RAISE EXCEPTION 'Automation is not active or does not belong to workspace';
  END IF;

  INSERT INTO automation_jobs(workspace_id, automation_id, status, run_at, payload)
  VALUES (p_workspace_id, p_automation_id, 'queued', p_run_at, COALESCE(p_payload, '{}'::jsonb))
  RETURNING id INTO job_id;

  RETURN job_id;
END;
$$;

COMMENT ON FUNCTION omnigo_set_updated_at() IS 'Keeps updated_at authoritative at the PostgreSQL layer.';
COMMENT ON FUNCTION omnigo_enqueue_automation(uuid,uuid,jsonb,timestamptz) IS 'Atomically validates and queues an active OmniGoCRM automation.';
