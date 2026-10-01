INSERT INTO message_templates(workspace_id,name,channel,body)
SELECT w.id,'Introduction','whatsapp','Hi {first_name}, this is {{company_name}}. I wanted to connect with you regarding our products and services.'
FROM workspaces w
WHERE NOT EXISTS (
  SELECT 1 FROM message_templates mt
  WHERE mt.workspace_id=w.id AND mt.channel='whatsapp' AND mt.name='Introduction'
);

INSERT INTO message_templates(workspace_id,name,channel,body)
SELECT w.id,'Product follow-up','whatsapp','Hi {first_name}, just following up on our earlier conversation. I can share our product details and pricing if helpful.'
FROM workspaces w
WHERE NOT EXISTS (
  SELECT 1 FROM message_templates mt
  WHERE mt.workspace_id=w.id AND mt.channel='whatsapp' AND mt.name='Product follow-up'
);

INSERT INTO message_templates(workspace_id,name,channel,body)
SELECT w.id,'Brochure sharing','whatsapp','Hi {first_name}, sharing our latest product brochure with you. Please have a look and let me know what you are interested in.'
FROM workspaces w
WHERE NOT EXISTS (
  SELECT 1 FROM message_templates mt
  WHERE mt.workspace_id=w.id AND mt.channel='whatsapp' AND mt.name='Brochure sharing'
);
