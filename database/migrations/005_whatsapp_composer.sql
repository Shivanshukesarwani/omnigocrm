CREATE TABLE IF NOT EXISTS media_assets (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  workspace_id uuid NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,
  name text NOT NULL,
  description text,
  asset_type text NOT NULL DEFAULT 'document' CHECK (asset_type IN ('image','video','document','audio','link')),
  url text NOT NULL,
  thumbnail_url text,
  mime_type text,
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS message_templates (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  workspace_id uuid NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,
  name text NOT NULL,
  channel text NOT NULL DEFAULT 'whatsapp' CHECK (channel IN ('whatsapp','sms','email')),
  body text NOT NULL,
  media_asset_id uuid REFERENCES media_assets(id) ON DELETE SET NULL,
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

ALTER TABLE conversations
  ADD COLUMN IF NOT EXISTS lead_id uuid REFERENCES leads(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS media_assets_workspace_idx ON media_assets(workspace_id, is_active, created_at);
CREATE INDEX IF NOT EXISTS message_templates_workspace_idx ON message_templates(workspace_id, channel, is_active, created_at);
CREATE INDEX IF NOT EXISTS conversations_lead_idx ON conversations(lead_id);

DROP TRIGGER IF EXISTS media_assets_updated_at ON media_assets;
CREATE TRIGGER media_assets_updated_at BEFORE UPDATE ON media_assets FOR EACH ROW EXECUTE FUNCTION omnigo_set_updated_at();

DROP TRIGGER IF EXISTS message_templates_updated_at ON message_templates;
CREATE TRIGGER message_templates_updated_at BEFORE UPDATE ON message_templates FOR EACH ROW EXECUTE FUNCTION omnigo_set_updated_at();
