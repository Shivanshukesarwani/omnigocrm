CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE workspaces (id uuid PRIMARY KEY DEFAULT gen_random_uuid(),name text NOT NULL,slug text NOT NULL UNIQUE,created_at timestamptz NOT NULL DEFAULT now(),updated_at timestamptz NOT NULL DEFAULT now());
CREATE TABLE users (id uuid PRIMARY KEY DEFAULT gen_random_uuid(),email text NOT NULL UNIQUE,password_hash text NOT NULL,name text NOT NULL,created_at timestamptz NOT NULL DEFAULT now(),updated_at timestamptz NOT NULL DEFAULT now());
CREATE TABLE workspace_members (workspace_id uuid NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,user_id uuid NOT NULL REFERENCES users(id) ON DELETE CASCADE,role text NOT NULL DEFAULT 'agent',created_at timestamptz NOT NULL DEFAULT now(),PRIMARY KEY(workspace_id,user_id));
CREATE TABLE leads (id uuid PRIMARY KEY DEFAULT gen_random_uuid(),workspace_id uuid NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,first_name text,last_name text,email text,phone text,source text,status text NOT NULL DEFAULT 'new',owner_id uuid REFERENCES users(id) ON DELETE SET NULL,created_at timestamptz NOT NULL DEFAULT now(),updated_at timestamptz NOT NULL DEFAULT now());
CREATE INDEX leads_workspace_idx ON leads(workspace_id);
CREATE INDEX leads_owner_idx ON leads(owner_id);
