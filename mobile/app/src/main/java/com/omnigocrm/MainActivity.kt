package com.omnigocrm

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.graphics.Color
import android.view.Gravity
import android.widget.*
import java.util.concurrent.Executors

class MainActivity: Activity() {
    private lateinit var api: ApiClient
    private lateinit var session: SessionManager
    private lateinit var root: LinearLayout
    private val pool=Executors.newCachedThreadPool()

    override fun onCreate(savedInstanceState: Bundle?){super.onCreate(savedInstanceState);api=ApiClient(this);session=SessionManager(this);if(session.token==null)login() else dashboard()}

    private fun login(){
        val box=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;gravity=Gravity.CENTER;setPadding(32,48,32,48)}
        box.addView(text("OmniGoCRM",28,true));box.addView(text("Sign in to your workspace",16,false))
        val email=EditText(this).apply{hint="Email"};val pass=EditText(this).apply{hint="Password";inputType=0x81};val b=Button(this).apply{text="Sign in"}
        box.addView(email);box.addView(pass);box.addView(b);setContentView(box)
        b.setOnClickListener{b.isEnabled=false;pool.execute{try{api.login(email.text.toString(),pass.text.toString());runOnUiThread{dashboard()}}catch(e:Exception){runOnUiThread{b.isEnabled=true;toast(e.message ?: "Login failed")}}}}
    }

    private fun shell(title:String):ScrollView{
        root=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setBackgroundColor(Color.rgb(247,249,252))}
        val bar=LinearLayout(this).apply{setPadding(12,12,12,8);setBackgroundColor(Color.WHITE)}
        bar.addView(text(title,22,true),LinearLayout.LayoutParams(0,-2,1f))
        val out=Button(this).apply{text="Logout"};bar.addView(out);out.setOnClickListener{pool.execute{api.logout();runOnUiThread{session.clear();login()}}};root.addView(bar)
        val nav=LinearLayout(this).apply{orientation=LinearLayout.HORIZONTAL}
        listOf("Dashboard","Leads","Contacts","Customers","Companies","Tasks","Quotations","Payments","Follow-ups").forEach{label->val b=Button(this).apply{text=label};nav.addView(b);b.setOnClickListener{when(label){"Dashboard"->dashboard();"Leads"->leads();"Contacts"->contacts();"Customers"->customers();"Companies"->companies();"Tasks"->tasks();"Quotations"->quotations();"Payments"->payments();"Follow-ups"->followups()}}}
        root.addView(HorizontalScrollView(this).apply{addView(nav)})
        val sc=ScrollView(this);sc.addView(LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(14,14,14,40)});root.addView(sc,LinearLayout.LayoutParams(-1,0,1f));setContentView(root);return sc
    }
    private fun box(sc:ScrollView)=sc.getChildAt(0) as LinearLayout

    private fun dashboard(){
        val sc=shell("Dashboard");val b=box(sc)
        pool.execute{try{val d=api.dashboard();val c=d.getJSONObject("counts");val rows=d.getJSONArray("recent_leads");runOnUiThread{b.addView(text("Leads "+c.optInt("leads")+" • Contacts "+c.optInt("contacts")+" • Customers "+c.optInt("customers")+" • Follow-ups "+c.optInt("followups"),18,true));b.addView(text("Recent Leads",18,true));for(i in 0 until rows.length()){val o=rows.getJSONObject(i);b.addView(card(o.optString("first_name")+" "+o.optString("last_name"),o.optString("company")+" • "+o.optString("mobile"),{lead(o.getLong("id"))}))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Dashboard error")}}}
    }

    private fun leads(){val sc=shell("Leads");val b=box(sc);val add=Button(this).apply{text="+ New Lead"};b.addView(add);add.setOnClickListener{newLead()};pool.execute{try{api.leads().forEach{l->runOnUiThread{b.addView(card(l.name(),l.company+" • "+l.mobile+" • "+l.status,{lead(l.id)}))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Leads error")}}}}
    private fun newLead(){val sc=shell("New Lead");val b=box(sc);val f=EditText(this).apply{hint="First name"};val l=EditText(this).apply{hint="Last name"};val c=EditText(this).apply{hint="Company"};val m=EditText(this).apply{hint="Mobile"};val r=EditText(this).apply{hint="Requirement"};val save=Button(this).apply{text="Create"};listOf(f,l,c,m,r,save).forEach{b.addView(it)};save.setOnClickListener{pool.execute{try{api.createLead(f.text.toString(),l.text.toString(),c.text.toString(),m.text.toString(),"mobile",r.text.toString());runOnUiThread{leads()}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Create failed")}}}}}
    private fun lead(id:Long){val sc=shell("Lead");val b=box(sc);pool.execute{try{val o=api.lead(id);runOnUiThread{b.addView(text(o.optString("first_name")+" "+o.optString("last_name"),26,true));b.addView(text(o.optString("company")+"\n"+o.optString("mobile")+"\n"+o.optString("requirement"),16,false));val call=Button(this).apply{text="Call"};val wa=Button(this).apply{text="WhatsApp"};val convert=Button(this).apply{text="Convert to Contact"};b.addView(call);b.addView(wa);b.addView(convert);val phone=o.optString("whatsapp").ifBlank{o.optString("mobile")};call.setOnClickListener{dialTracked("lead",id,phone)};wa.setOnClickListener{openWhatsApp("lead",id)};convert.setOnClickListener{pool.execute{try{api.convertLead(id);runOnUiThread{contacts()}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Conversion failed")}}}}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Lead error")}}}}
    private fun contacts(){val sc=shell("Contacts");val b=box(sc);pool.execute{try{api.contacts().forEach{c->runOnUiThread{b.addView(card(c.name(),c.company+" • "+c.mobile,{contact(c.id)}))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Contacts error")}}}}
    private fun contact(id:Long){val sc=shell("Contact");val b=box(sc);pool.execute{try{val o=api.contact(id);runOnUiThread{b.addView(text(o.optString("first_name")+" "+o.optString("last_name"),26,true));b.addView(text(o.optString("company")+"\n"+o.optString("mobile")+"\n"+o.optString("email"),16,false));val call=Button(this).apply{text="Call"};val wa=Button(this).apply{text="WhatsApp"};val convert=Button(this).apply{text="Convert to Customer"};b.addView(call);b.addView(wa);b.addView(convert);val phone=o.optString("whatsapp").ifBlank{o.optString("mobile")};call.setOnClickListener{dialTracked("contact",id,phone)};wa.setOnClickListener{openWhatsApp("contact",id)};convert.setOnClickListener{pool.execute{try{api.convertContact(id);runOnUiThread{customers()}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Conversion failed")}}}}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Contact error")}}}}
    private fun customers(){val sc=shell("Customers");val b=box(sc);pool.execute{try{api.customers().forEach{c->runOnUiThread{b.addView(card(c.code,c.name+" • "+c.company+" • "+c.mobile,{customer(c.id)}))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Customers error")}}}}
    private fun customer(id:Long){val sc=shell("Customer");val b=box(sc);pool.execute{try{val o=api.customer(id);val c=o.optJSONObject("contact");runOnUiThread{b.addView(text(o.optString("customer_code"),26,true));b.addView(text((c?.optString("first_name")?:"")+" "+(c?.optString("last_name")?:"")+"\n"+(c?.optString("company")?:"")+"\n"+(c?.optString("mobile")?:""),16,false))}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Customer error")}}}}
    private fun companies(){val sc=shell("Companies");val b=box(sc);pool.execute{try{api.companies().forEach{c->runOnUiThread{b.addView(card(c.name,c.phone+" • "+c.email+" • "+c.website))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Companies error")}}}}
    private fun tasks(){val sc=shell("Tasks");val b=box(sc);pool.execute{try{api.tasks().forEach{t->runOnUiThread{b.addView(card(t.title,t.priority+" • "+t.status+" • "+t.dueAt,{if(t.status!="completed")pool.execute{try{api.completeTask(t.id);runOnUiThread{tasks()}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Task error")}}}}))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Tasks error")}}}}
    private fun quotations(){val sc=shell("Quotations");val b=box(sc);pool.execute{try{val rows=api.quotations();runOnUiThread{for(i in 0 until rows.length()){val o=rows.getJSONObject(i);b.addView(card(o.optString("quote_number"),"₹"+o.optString("total")+" • "+o.optString("status")))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Quotations error")}}}}
    private fun payments(){val sc=shell("Payments");val b=box(sc);pool.execute{try{val rows=api.payments();runOnUiThread{for(i in 0 until rows.length()){val o=rows.getJSONObject(i);b.addView(card("₹"+o.optString("amount"),o.optString("method")+" • "+o.optString("paid_at")))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Payments error")}}}}
    private fun followups(){val sc=shell("Follow-ups");val b=box(sc);pool.execute{try{val rows=api.followUps();runOnUiThread{for(i in 0 until rows.length()){val o=rows.getJSONObject(i);b.addView(card("Follow-up",o.optString("scheduled_for")+" • "+o.optString("status")))}}}catch(e:Exception){runOnUiThread{toast(e.message ?: "Follow-up error")}}}}
    private fun openWhatsApp(type:String,id:Long){pool.execute{try{val t=api.templates().first();val url=api.whatsapp(type,id,t.situation).getString("url");runOnUiThread{startActivity(Intent(Intent.ACTION_VIEW,Uri.parse(url)))}}catch(e:Exception){runOnUiThread{toast(e.message ?: "WhatsApp error")}}}}
    private fun dialTracked(type:String,id:Long,phone:String){if(phone.isBlank()){toast("No phone number");return};val i=Intent(this,CallRecordingService::class.java).apply{putExtra("subject_type",type);putExtra("subject_id",id);putExtra("phone",phone)};try{startForegroundService(i)}catch(_:Exception){};startActivity(Intent(Intent.ACTION_DIAL,Uri.parse("tel:"+phone.filter{it.isDigit()||it=='+'})))}
    private fun text(s:String,size:Float,bold:Boolean)=TextView(this).apply{text=s;textSize=size;if(bold)setTypeface(null,1);setPadding(0,8,0,8)}
    private fun card(t:String,sub:String,click:(()->Unit)?=null)=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(14,12,14,12);setBackgroundColor(Color.WHITE);addView(text(t,17,true));addView(text(sub,14,false));click?.let{setOnClickListener{it()}};layoutParams=LinearLayout.LayoutParams(-1,-2).apply{setMargins(0,5,0,5)}}
    private fun toast(s:String)=Toast.makeText(this,s,Toast.LENGTH_LONG).show()
    private fun LeadItem.name()=firstName+" "+lastName
    private fun ContactItem.name()=firstName+" "+lastName
    override fun onDestroy(){pool.shutdownNow();super.onDestroy()}
}