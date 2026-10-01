import Fastify from "fastify";
import cors from "@fastify/cors";
import jwt from "@fastify/jwt";
import { z } from "zod";
import {query,withTransaction} from "./db.js";
import {hashPassword,verifyPassword,requireAuth,requireRole} from "./auth.js";

const app=Fastify({logger:true});
await app.register(cors,{origin:process.env.CORS_ORIGIN??true});
await app.register(jwt,{secret:process.env.JWT_SECRET??"change-me"});
const text=(n=500)=>z.string().trim().max(n);
const uuid=z.string().uuid();
const created=(data)=>({data});

app.setErrorHandler((e,req,reply)=>{req.log.error(e);const s=(e).statusCode??500;reply.code(s).send({error:s===500?"Internal server error":e.message})});
app.get("/health",async()=>({status:"ok",service:"omnigocrm-api",version:"0.2.0"}));
app.get("/api/v1",async()=>({name:"OmniGoCRM API",version:"v1"}));

app.post("/api/v1/auth/register",async(req,reply)=>{
 const b=z.object({name:text(120),email:z.string().email().transform(v=>v.toLowerCase()),password:z.string().min(8).max(200),workspaceName:text(160).optional()}).parse(req.body);
 const result=await withTransaction(async c=>{const u=await c.query("INSERT INTO users(email,password_hash,name) VALUES($1,$2,$3) RETURNING id,email,name",[b.email,await hashPassword(b.password),b.name]);const name=b.workspaceName??b.name+" Workspace";const slug=name.toLowerCase().replace(/[^a-z0-9]+/g,"-").replace(/^-|-$/g,"").slice(0,60)+"-"+u.rows[0].id.slice(0,8);const w=await c.query("INSERT INTO workspaces(name,slug) VALUES($1,$2) RETURNING id,name,slug",[name,slug]);await c.query("INSERT INTO workspace_members(workspace_id,user_id,role) VALUES($1,$2,'owner')",[w.rows[0].id,u.rows[0].id]);const p=await c.query("INSERT INTO pipelines(workspace_id,name,description,is_default) VALUES($1,'Default Sales Pipeline','Default opportunity pipeline',true) RETURNING id",[w.rows[0].id]);await c.query("INSERT INTO pipeline_stages(pipeline_id,name,position,probability) VALUES($1,'New',1,10),($1,'Qualified',2,30),($1,'Proposal',3,60),($1,'Negotiation',4,80),($1,'Won',5,100),($1,'Lost',6,0)",[p.rows[0].id]);return {user:u.rows[0],workspace:w.rows[0]}});
 const token=await reply.jwtSign({sub:result.user.id,workspaceId:result.workspace.id,role:"owner"},{expiresIn:"7d"});reply.code(201).send({...result,token});
});
app.post("/api/v1/auth/login",async(req,reply)=>{
 const b=z.object({email:z.string().email().transform(v=>v.toLowerCase()),password:z.string()}).parse(req.body);
 const r=await query("SELECT u.id,u.email,u.name,u.password_hash,wm.workspace_id,wm.role,w.name workspace_name,w.slug workspace_slug FROM users u JOIN workspace_members wm ON wm.user_id=u.id JOIN workspaces w ON w.id=wm.workspace_id WHERE u.email=$1 ORDER BY wm.created_at LIMIT 1",[b.email]);
 if(!r.rowCount||!(await verifyPassword(b.password,r.rows[0].password_hash)))return reply.code(401).send({error:"Invalid email or password"});
 const x=r.rows[0];const token=await reply.jwtSign({sub:x.id,workspaceId:x.workspace_id,role:x.role},{expiresIn:"7d"});return {token,user:{id:x.id,email:x.email,name:x.name},workspace:{id:x.workspace_id,name:x.workspace_name,slug:x.workspace_slug}};
});
app.get("/api/v1/auth/me",{preHandler:requireAuth},async req=>({user:req.authUser}));
app.get("/api/v1/workspaces",{preHandler:requireAuth},async req=>(await query("SELECT w.id,w.name,w.slug,wm.role FROM workspaces w JOIN workspace_members wm ON wm.workspace_id=w.id WHERE wm.user_id=$1 ORDER BY w.created_at",[req.authUser.id])).rows);
app.post("/api/v1/workspaces",{preHandler:requireAuth},async(req,reply)=>{const b=z.object({name:text(160),slug:z.string().regex(/^[a-z0-9-]+$/).min(2).max(80)}).parse(req.body);const w=await query("INSERT INTO workspaces(name,slug) VALUES($1,$2) RETURNING *",[b.name,b.slug]);await query("INSERT INTO workspace_members(workspace_id,user_id,role) VALUES($1,$2,'owner')",[w.rows[0].id,req.authUser.id]);reply.code(201).send(created(w.rows[0]))});
app.post("/api/v1/auth/switch-workspace",{preHandler:requireAuth},async(req,reply)=>{const b=z.object({workspaceId:uuid}).parse(req.body);const r=await query("SELECT role FROM workspace_members WHERE user_id=$1 AND workspace_id=$2",[req.authUser.id,b.workspaceId]);if(!r.rowCount)return reply.code(403).send({error:"Not a workspace member"});return {token:await reply.jwtSign({sub:req.authUser.id,workspaceId:b.workspaceId,role:r.rows[0].role},{expiresIn:"7d"})}});

const resources={
 leads:{table:"leads",fields:["first_name","last_name","email","phone","source","status","owner_id"],search:["first_name","last_name","email","phone"]},
 contacts:{table:"contacts",fields:["first_name","last_name","email","phone","job_title","account_id","owner_id"],search:["first_name","last_name","email","phone"]},
 accounts:{table:"accounts",fields:["name","email","phone","website","industry","owner_id"],search:["name","email","phone","website"]},
 opportunities:{table:"opportunities",fields:["name","amount","stage","probability","expected_close_date","pipeline_id","account_id","contact_id","owner_id"],search:["name","stage"]},
 tasks:{table:"tasks",fields:["title","description","status","priority","due_at","assigned_to","related_type","related_id"],search:["title","description"]},
 notes:{table:"notes",fields:["body","related_type","related_id","created_by"],search:["body"]},
 products:{table:"products",fields:["name","sku","description","unit_price","currency","tax_rate","is_active"],search:["name","sku","description"]},
 campaigns:{table:"campaigns",fields:["name","channel","status","settings"],search:["name","channel","status"]},
 automations:{table:"automations",fields:["name","trigger_type","active","definition"],search:["name","trigger_type"]},
 integrations:{table:"integrations",fields:["type","name","status","config","secrets_ref"],search:["type","name","status"]},
 quotes:{table:"quotes",fields:["quote_number","account_id","contact_id","status","currency","subtotal","tax_total","total","valid_until","created_by"],search:["quote_number","status"]},
 orders:{table:"orders",fields:["order_number","quote_id","account_id","contact_id","status","currency","subtotal","tax_total","total","created_by"],search:["order_number","status"]},
 invoices:{table:"invoices",fields:["invoice_number","order_id","account_id","status","currency","subtotal","tax_total","total","due_date"],search:["invoice_number","status"]},
 payments:{table:"payments",fields:["invoice_id","amount","currency","method","status","provider","provider_reference","paid_at"],search:["method","status","provider","provider_reference"]}
};
async function audit(req,action,entity,id){await query("INSERT INTO audit_logs(workspace_id,user_id,action,entity,entity_id) VALUES($1,$2,$3,$4,$5)",[req.authUser.workspaceId,req.authUser.id,action,entity,id])}
for(const [name,cfg] of Object.entries(resources)){
 app.get("/api/v1/"+name,{preHandler:requireAuth},async(req)=>{const q=z.object({search:z.string().optional(),status:z.string().optional(),limit:z.coerce.number().int().min(1).max(100).default(50),offset:z.coerce.number().int().min(0).default(0)}).parse(req.query);const where=["workspace_id=$1"];const vals=[req.authUser.workspaceId];let n=2;if(q.status&&cfg.fields.includes("status")){where.push("status=$"+n++);vals.push(q.status)}if(q.search){where.push("("+cfg.search.map((f)=>f+" ILIKE $"+n).join(" OR ")+")");vals.push("%"+q.search+"%");n++}const r=await query("SELECT * FROM "+cfg.table+" WHERE "+where.join(" AND ")+" ORDER BY created_at DESC LIMIT $"+n+" OFFSET $"+(n+1),[...vals,q.limit,q.offset]);return {data:r.rows,pagination:{limit:q.limit,offset:q.offset,count:r.rowCount}}});
 app.get("/api/v1/"+name+"/:id",{preHandler:requireAuth},async(req,reply)=>{const p=z.object({id:uuid}).parse(req.params);const r=await query("SELECT * FROM "+cfg.table+" WHERE id=$1 AND workspace_id=$2",[p.id,req.authUser.workspaceId]);if(!r.rowCount)return reply.code(404).send({error:"Not found"});return created(r.rows[0])});
 app.post("/api/v1/"+name,{preHandler:requireAuth},async(req,reply)=>{const b=req.body;const keys=Object.keys(b).filter(k=>cfg.fields.includes(k));if(!keys.length)return reply.code(400).send({error:"No supported fields"});if(name==="leads"&&!b.source)b.source="manual";if(["leads","contacts","accounts","opportunities"].includes(name)&&!b.owner_id)b.owner_id=req.authUser.id;if(name==="tasks"&&!b.assigned_to)b.assigned_to=req.authUser.id;if(name==="notes"&&!b.created_by)b.created_by=req.authUser.id;const vals=keys.map(k=>b[k]);const r=await query("INSERT INTO "+cfg.table+"(workspace_id,"+keys.join(",")+") VALUES($1,"+keys.map((_,i)=>"$"+(i+2)).join(",")+") RETURNING *",[req.authUser.workspaceId,...vals]);await audit(req,"create",name,r.rows[0].id);reply.code(201).send(created(r.rows[0]))});
 app.patch("/api/v1/"+name+"/:id",{preHandler:requireAuth},async(req,reply)=>{const p=z.object({id:uuid}).parse(req.params);const b=req.body;const keys=Object.keys(b).filter(k=>cfg.fields.includes(k));if(!keys.length)return reply.code(400).send({error:"No editable fields"});const r=await query("UPDATE "+cfg.table+" SET "+keys.map((k,i)=>k+"=$"+(i+1)).join(",")+",updated_at=now() WHERE id=$"+(keys.length+1)+" AND workspace_id=$"+(keys.length+2)+" RETURNING *",[...keys.map(k=>b[k]),p.id,req.authUser.workspaceId]);if(!r.rowCount)return reply.code(404).send({error:"Not found"});await audit(req,"update",name,p.id);return created(r.rows[0])});
 app.delete("/api/v1/"+name+"/:id",{preHandler:[requireAuth,requireRole("owner","admin","manager")]},async(req,reply)=>{const p=z.object({id:uuid}).parse(req.params);const r=await query("DELETE FROM "+cfg.table+" WHERE id=$1 AND workspace_id=$2 RETURNING id",[p.id,req.authUser.workspaceId]);if(!r.rowCount)return reply.code(404).send({error:"Not found"});await audit(req,"delete",name,p.id);return {success:true}});
}
app.get("/api/v1/plans",async()=>({data:(await query("SELECT id,code,name,description,monthly_price,currency,limits,features FROM plans WHERE active=true ORDER BY monthly_price")).rows}));
app.get("/api/v1/reports/summary",{preHandler:requireAuth},async req=>{const w=req.authUser.workspaceId;const [won,lost,revenue,leads]=await Promise.all([query("SELECT count(*)::int count FROM opportunities WHERE workspace_id=$1 AND stage='won'",[w]),query("SELECT count(*)::int count FROM opportunities WHERE workspace_id=$1 AND stage='lost'",[w]),query("SELECT coalesce(sum(amount),0)::numeric value FROM payments WHERE workspace_id=$1 AND status='paid'",[w]),query("SELECT source,count(*)::int count FROM leads WHERE workspace_id=$1 GROUP BY source ORDER BY count DESC",[w])]);return {won:won.rows[0].count,lost:lost.rows[0].count,paidRevenue:revenue.rows[0].value,leadsBySource:leads.rows}});
app.get("/api/v1/dashboard",{preHandler:requireAuth},async req=>{const w=req.authUser.workspaceId;const [a,b,c,d,e]=await Promise.all([query("SELECT count(*)::int count FROM leads WHERE workspace_id=$1",[w]),query("SELECT count(*)::int count FROM contacts WHERE workspace_id=$1",[w]),query("SELECT count(*)::int count FROM accounts WHERE workspace_id=$1",[w]),query("SELECT count(*)::int count,coalesce(sum(amount),0)::numeric pipeline FROM opportunities WHERE workspace_id=$1 AND stage NOT IN ('won','lost')",[w]),query("SELECT count(*)::int count FROM tasks WHERE workspace_id=$1 AND status NOT IN ('completed','cancelled')",[w])]);return {leads:a.rows[0].count,contacts:b.rows[0].count,accounts:c.rows[0].count,opportunities:d.rows[0].count,pipelineValue:d.rows[0].pipeline,openTasks:e.rows[0].count}});
app.get("/api/v1/pipelines",{preHandler:requireAuth},async req=>({data:(await query("SELECT p.*,coalesce(json_agg(ps ORDER BY ps.position) FILTER(WHERE ps.id IS NOT NULL),'[]') stages FROM pipelines p LEFT JOIN pipeline_stages ps ON ps.pipeline_id=p.id WHERE p.workspace_id=$1 GROUP BY p.id ORDER BY p.created_at",[req.authUser.workspaceId])).rows}));
app.post("/api/v1/pipelines",{preHandler:requireAuth},async(req,reply)=>{const b=z.object({name:text(120),description:text(500).optional()}).parse(req.body);const p=await query("INSERT INTO pipelines(workspace_id,name,description) VALUES($1,$2,$3) RETURNING *",[req.authUser.workspaceId,b.name,b.description??null]);reply.code(201).send(created(p.rows[0]))});
app.get("/api/v1/conversations",{preHandler:requireAuth},async req=>({data:(await query("SELECT c.*,(SELECT count(*) FROM messages m WHERE m.conversation_id=c.id)::int message_count FROM conversations c WHERE c.workspace_id=$1 ORDER BY c.updated_at DESC",[req.authUser.workspaceId])).rows}));
app.get("/api/v1/conversations/:id/messages",{preHandler:requireAuth},async(req,reply)=>{const p=z.object({id:uuid}).parse(req.params);const r=await query("SELECT m.* FROM messages m JOIN conversations c ON c.id=m.conversation_id WHERE m.conversation_id=$1 AND c.workspace_id=$2 ORDER BY m.created_at",[p.id,req.authUser.workspaceId]);return {data:r.rows}});
app.post("/api/v1/conversations",{preHandler:requireAuth},async(req,reply)=>{const b=z.object({channel:z.enum(["whatsapp","sms","email","call","web"]),external_contact:text(200).optional(),subject:text(250).optional()}).parse(req.body);const r=await query("INSERT INTO conversations(workspace_id,channel,external_contact,subject,assigned_to) VALUES($1,$2,$3,$4,$5) RETURNING *",[req.authUser.workspaceId,b.channel,b.external_contact??null,b.subject??null,req.authUser.id]);reply.code(201).send(created(r.rows[0]))});
app.post("/api/v1/conversations/:id/messages",{preHandler:requireAuth},async(req,reply)=>{const p=z.object({id:uuid}).parse(req.params);const b=z.object({body:z.string().min(1).max(10000),direction:z.enum(["inbound","outbound"]).default("outbound"),message_type:z.enum(["text","image","file","audio","template"]).default("text")}).parse(req.body);const c=await query("SELECT id FROM conversations WHERE id=$1 AND workspace_id=$2",[p.id,req.authUser.workspaceId]);if(!c.rowCount)return reply.code(404).send({error:"Conversation not found"});const r=await query("INSERT INTO messages(conversation_id,sender_id,direction,message_type,body) VALUES($1,$2,$3,$4,$5) RETURNING *",[p.id,req.authUser.id,b.direction,b.message_type,b.body]);await query("UPDATE conversations SET updated_at=now(),last_message_at=now() WHERE id=$1",[p.id]);reply.code(201).send(created(r.rows[0]))});
function normalizeWhatsAppPhone(value){
 const raw=String(value??"").trim();
 if(!raw)return "";
 const digits=raw.replace(/\\D/g,"");
 if(digits.length===10)return "91"+digits;
 if(digits.length===11&&digits.startsWith("0"))return "91"+digits.slice(1);
 if(digits.length>=10&&digits.length<=15)return digits;
 return "";
}
function renderWhatsAppBody(body,lead){
 return String(body??"")
  .replace(/\\{first_name\\}/gi,lead.first_name??"")
  .replace(/\\{last_name\\}/gi,lead.last_name??"")
  .replace(/\\{name\\}/gi,[lead.first_name,lead.last_name].filter(Boolean).join(" "));
}
app.get("/api/v1/whatsapp/assets",{preHandler:requireAuth},async req=>({
 data:(await query("SELECT id,name,description,asset_type,url,thumbnail_url,mime_type,created_at FROM media_assets WHERE workspace_id=$1 AND is_active=true ORDER BY created_at DESC",[req.authUser.workspaceId])).rows
}));
app.post("/api/v1/whatsapp/assets",{preHandler:[requireAuth,requireRole("owner","admin","manager")]},async(req,reply)=>{
 const b=z.object({name:text(160),description:text(500).optional(),asset_type:z.enum(["image","video","document","audio","link"]).default("document"),url:z.string().url(),thumbnail_url:z.string().url().optional(),mime_type:text(120).optional()}).parse(req.body);
 const r=await query("INSERT INTO media_assets(workspace_id,name,description,asset_type,url,thumbnail_url,mime_type) VALUES($1,$2,$3,$4,$5,$6,$7) RETURNING *",[req.authUser.workspaceId,b.name,b.description??null,b.asset_type,b.url,b.thumbnail_url??null,b.mime_type??null]);
 await audit(req,"create","media_asset",r.rows[0].id); reply.code(201).send(created(r.rows[0]));
});
app.get("/api/v1/whatsapp/templates",{preHandler:requireAuth},async req=>{
 const channel=req.query?.channel==="sms"||req.query?.channel==="email"?"channel":"whatsapp";
 const vals=[req.authUser.workspaceId]; const where=["workspace_id=$1","is_active=true"]; let n=2;
 if(channel!=="channel"){where.push("channel=$"+n++);vals.push(channel)}
 const r=await query("SELECT mt.id,mt.name,mt.channel,mt.body,mt.media_asset_id,ma.name media_asset_name,ma.url media_asset_url,ma.asset_type media_asset_type FROM message_templates mt LEFT JOIN media_assets ma ON ma.id=mt.media_asset_id WHERE "+where.join(" AND ")+" ORDER BY mt.created_at DESC",vals);
 return {data:r.rows};
});
app.post("/api/v1/whatsapp/templates",{preHandler:[requireAuth,requireRole("owner","admin","manager")]},async(req,reply)=>{
 const b=z.object({name:text(160),body:z.string().min(1).max(10000),channel:z.enum(["whatsapp","sms","email"]).default("whatsapp"),media_asset_id:uuid.optional()}).parse(req.body);
 if(b.media_asset_id){
  const a=await query("SELECT id FROM media_assets WHERE id=$1 AND workspace_id=$2 AND is_active=true",[b.media_asset_id,req.authUser.workspaceId]);
  if(!a.rowCount)return reply.code(400).send({error:"Media asset not found"});
 }
 const r=await query("INSERT INTO message_templates(workspace_id,name,channel,body,media_asset_id) VALUES($1,$2,$3,$4,$5) RETURNING *",[req.authUser.workspaceId,b.name,b.channel,b.body,b.media_asset_id??null]);
 await audit(req,"create","message_template",r.rows[0].id); reply.code(201).send(created(r.rows[0]));
});
app.post("/api/v1/leads/:id/whatsapp/prepare",{preHandler:requireAuth},async(req,reply)=>{
 const p=z.object({id:uuid}).parse(req.params);
 const b=z.object({body:z.string().min(1).max(10000),template_id:uuid.optional(),media_asset_id:uuid.optional()}).parse(req.body);
 const lead=await query("SELECT id,first_name,last_name,phone FROM leads WHERE id=$1 AND workspace_id=$2",[p.id,req.authUser.workspaceId]);
 if(!lead.rowCount)return reply.code(404).send({error:"Lead not found"});
 const l=lead.rows[0]; const phone=normalizeWhatsAppPhone(l.phone);
 if(!phone)return reply.code(400).send({error:"Lead does not have a valid WhatsApp phone number"});
 let asset=null;
 if(b.media_asset_id){
  const ar=await query("SELECT id,name,url,asset_type,mime_type FROM media_assets WHERE id=$1 AND workspace_id=$2 AND is_active=true",[b.media_asset_id,req.authUser.workspaceId]);
  if(!ar.rowCount)return reply.code(400).send({error:"Media asset not found"});
  asset=ar.rows[0];
 }
 let body=renderWhatsAppBody(b.body,l);
 if(asset?.url && !body.includes(asset.url))body=(body.trim()+"\\n\\n"+asset.name+": "+asset.url).trim();
 let conversation=await query("SELECT id FROM conversations WHERE workspace_id=$1 AND lead_id=$2 AND channel='whatsapp' ORDER BY updated_at DESC LIMIT 1",[req.authUser.workspaceId,l.id]);
 let conversationId=conversation.rows[0]?.id;
 if(!conversationId){
  const cr=await query("INSERT INTO conversations(workspace_id,channel,external_contact,assigned_to,lead_id,last_message_at) VALUES($1,'whatsapp',$2,$3,$4,now()) RETURNING id",[req.authUser.workspaceId,l.phone,req.authUser.id,l.id]);
  conversationId=cr.rows[0].id;
 }
 const metadata={delivery_method:"external_redirect",delivery_status:"prepared",phone,template_id:b.template_id??null,media_asset_id:asset?.id??null,media_asset_url:asset?.url??null};
 const mr=await query("INSERT INTO messages(conversation_id,sender_id,direction,message_type,body,media_url,metadata) VALUES($1,$2,'outbound',$3,$4,$5,$6) RETURNING id,created_at",[conversationId,req.authUser.id,asset?.asset_type==="image"?"image":b.template_id?"template":"text",body,asset?.url??null,metadata]);
 await query("UPDATE conversations SET updated_at=now(),last_message_at=now() WHERE id=$1",[conversationId]);
 await audit(req,"whatsapp_redirect","lead",l.id);
 const encoded=encodeURIComponent(body);
 const whatsappUrl="https://wa.me/"+phone+"?text="+encoded;
 const webUrl="https://web.whatsapp.com/send?phone="+phone+"&text="+encoded;
 const desktopUrl="whatsapp://send?phone="+phone+"&text="+encoded;
 return {lead:{id:l.id,name:[l.first_name,l.last_name].filter(Boolean).join(" "),phone:l.phone,normalizedPhone:phone},conversationId,messageId:mr.rows[0].id,body,media:asset,urls:{mobile:whatsappUrl,web:webUrl,desktop:desktopUrl}};
});
app.get("/api/v1/notifications",{preHandler:requireAuth},async req=>({data:(await query("SELECT * FROM notifications WHERE user_id=$1 AND workspace_id=$2 ORDER BY created_at DESC LIMIT 50",[req.authUser.id,req.authUser.workspaceId])).rows}));
app.get("/api/v1/audit-logs",{preHandler:[requireAuth,requireRole("owner","admin")]},async req=>({data:(await query("SELECT * FROM audit_logs WHERE workspace_id=$1 ORDER BY created_at DESC LIMIT 100",[req.authUser.workspaceId])).rows}));
app.post("/api/v1/public/leads/:workspaceSlug",async(req,reply)=>{const p=z.object({workspaceSlug:z.string().min(2)}).parse(req.params);const b=z.object({first_name:text(120),last_name:text(120).optional(),email:z.string().email().optional(),phone:text(50).optional(),source:text(120).default("website")}).parse(req.body);const w=await query("SELECT id FROM workspaces WHERE slug=$1",[p.workspaceSlug]);if(!w.rowCount)return reply.code(404).send({error:"Workspace not found"});const r=await query("INSERT INTO leads(workspace_id,first_name,last_name,email,phone,source) VALUES($1,$2,$3,$4,$5,$6) RETURNING id,first_name,last_name,email,phone,source,status,created_at",[w.rows[0].id,b.first_name,b.last_name??null,b.email??null,b.phone??null,b.source]);reply.code(201).send(created(r.rows[0]))});
await app.listen({port:Number(process.env.PORT??3000),host:"0.0.0.0"});
export {app};