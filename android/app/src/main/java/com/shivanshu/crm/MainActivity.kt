package com.shivanshu.crm

import android.Manifest
import android.app.Activity
import android.app.AlertDialog
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import java.util.concurrent.Executors

class MainActivity : Activity() {
    private lateinit var api: ApiClient
    private lateinit var session: SessionManager
    private lateinit var root: LinearLayout
    private lateinit var title: TextView
    private val worker = Executors.newSingleThreadExecutor()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        api = ApiClient(this)
        session = SessionManager(this)
        if (session.token == null) showLogin() else showDashboard()
    }

    private fun showLogin() {
        val box = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(40, 60, 40, 40)
        }
        addText(box, "Shivanshu CRM", 28f, true)
        addText(box, "Login to your CRM", 16f, false)
        val email = EditText(this).apply { hint = "Email" }
        val password = EditText(this).apply { hint = "Password"; inputType = 0x81 }
        val button = Button(this).apply { text = "Login" }
        box.addView(email, lp())
        box.addView(password, lp())
        box.addView(button, lp())
        button.setOnClickListener {
            button.isEnabled = false
            api.runAsync {
                try {
                    api.login(email.text.toString().trim(), password.text.toString())
                    runOnUiThread { showDashboard() }
                } catch (e: Exception) {
                    runOnUiThread {
                        button.isEnabled = true
                        toast(e.message ?: "Login failed")
                    }
                }
            }
        }
        setContentView(box)
    }

    private fun setupShell(screenTitle: String) {
        root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(Color.rgb(247, 249, 252))
        }
        val bar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            setPadding(18, 16, 10, 10)
            setBackgroundColor(Color.WHITE)
        }
        title = TextView(this).apply {
            text = screenTitle
            textSize = 22f
            setTextColor(Color.rgb(20, 30, 50))
            setTypeface(null, 1)
        }
        bar.addView(title, LinearLayout.LayoutParams(0, -2, 1f))
        val logout = Button(this).apply { text = "Logout" }
        bar.addView(logout, LinearLayout.LayoutParams(110, -2))
        logout.setOnClickListener {
            api.runAsync {
                api.logout()
                runOnUiThread {
                    session.clear()
                    showLogin()
                }
            }
        }
        root.addView(bar)

        val navScroll = ScrollView(this).apply { isHorizontalScrollBarEnabled = false }
        val nav = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; setPadding(8, 4, 8, 4); setBackgroundColor(Color.WHITE) }
        listOf("Dashboard", "Leads", "Contacts", "Customers", "Follow-ups").forEach { label ->
            val b = Button(this).apply { text = label }
            nav.addView(b, LinearLayout.LayoutParams(-2, -2))
            b.setOnClickListener {
                when (label) {
                    "Dashboard" -> showDashboard()
                    "Leads" -> showLeads()
                    "Contacts" -> showContacts()
                    "Customers" -> showCustomers()
                    "Follow-ups" -> showFollowUps()
                }
            }
        }
        navScroll.addView(nav)
        root.addView(navScroll)
        setContentView(root)
    }

    private fun contentScroll(): ScrollView = ScrollView(this).also { it.setPadding(14, 12, 14, 30) }

    private fun showDashboard() {
        setupShell("Dashboard")
        val scroll = contentScroll()
        val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        addText(box, "Welcome, ${session.userName ?: "User"}", 21f, true)
        val counts = TextView(this).apply { textSize = 18f; setPadding(0, 12, 0, 16) }
        box.addView(counts, lp())
        addText(box, "Recent Leads", 18f, true)
        val leadList = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        box.addView(leadList, lp())
        scroll.addView(box)
        root.addView(scroll, LinearLayout.LayoutParams(-1, 0, 1f))
        api.runAsync {
            try {
                val d = api.dashboard()
                val c = d.getJSONObject("counts")
                val rows = d.getJSONArray("recent_leads")
                runOnUiThread {
                    counts.text = "Leads: ${c.getInt("leads")}   Contacts: ${c.getInt("contacts")}\nCustomers: ${c.getInt("customers")}   Follow-ups: ${c.getInt("followups")}"
                    for (i in 0 until rows.length()) {
                        val o = rows.getJSONObject(i)
                        leadList.addView(card("${o.optString("first_name")} ${o.optString("last_name")}".trim(), "${o.optString("company")} • ${o.optString("mobile")} • ${o.optString("status")}") {
                            showLead(o.getLong("id"))
                        })
                    }
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not load dashboard") }
            }
        }
    }

    private fun showLeads() {
        setupShell("Leads")
        val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(14, 10, 14, 20) }
        val add = Button(this).apply { text = "+ New Lead" }
        box.addView(add, lp())
        add.setOnClickListener { showCreateLead() }
        val scroll = contentScroll()
        box.addView(scroll, LinearLayout.LayoutParams(-1, 0, 1f))
        root.addView(box, LinearLayout.LayoutParams(-1, 0, 1f))
        api.runAsync {
            try {
                val leads = api.leads()
                runOnUiThread {
                    leads.forEach { lead ->
                        scroll.addView(card("${lead.firstName} ${lead.lastName}".trim(), "${lead.company.ifBlank { "Individual" }} • ${lead.mobile} • ${lead.status}") {
                            showLead(lead.id)
                        })
                    }
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not load leads") }
            }
        }
    }

    private fun showCreateLead() {
        setupShell("New Lead")
        val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(14, 10, 14, 20) }
        val fields = listOf(edit("First name *"), edit("Last name"), edit("Company"), edit("Mobile *"), edit("Source"), edit("Requirement"))
        fields.forEach { box.addView(it, lp()) }
        val save = Button(this).apply { text = "Create Lead" }
        val back = Button(this).apply { text = "Back" }
        box.addView(save, lp()); box.addView(back, lp())
        save.setOnClickListener {
            val first = fields[0].text.toString(); val last = fields[1].text.toString(); val company = fields[2].text.toString(); val mobile = fields[3].text.toString(); val source = fields[4].text.toString(); val req = fields[5].text.toString()
            if (first.isBlank() || mobile.isBlank()) { toast("First name and mobile are required"); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync {
                try { api.createLead(first, last, company, mobile, source, req); runOnUiThread { showLeads() } }
                catch (e: Exception) { runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not create lead") } }
            }
        }
        back.setOnClickListener { showLeads() }
        root.addView(contentScroll().also { it.addView(box) }, LinearLayout.LayoutParams(-1, 0, 1f))
    }

    private fun showLead(id: Long) {
        setupShell("Lead")
        val scroll = contentScroll()
        root.addView(scroll, LinearLayout.LayoutParams(-1, 0, 1f))
        api.runAsync {
            try { val lead = api.lead(id); runOnUiThread { renderLead(scroll, lead) } }
            catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load lead") } }
        }
    }

    private fun renderLead(scroll: ScrollView, o: org.json.JSONObject) {
        val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        addText(box, "${o.optString("first_name")} ${o.optString("last_name")}".trim(), 26f, true)
        addText(box, "${o.optString("company")}\n${o.optString("mobile")}\n${o.optString("requirement")}", 15f, false)
        val call = Button(this).apply { text = "📞 Call" }
        val wa = Button(this).apply { text = "💬 WhatsApp" }
        val convert = Button(this).apply { text = "Convert to Contact" }
        box.addView(call, lp()); box.addView(wa, lp()); box.addView(convert, lp())
        val phone = o.optString("whatsapp").ifBlank { o.optString("mobile") }
        call.setOnClickListener { startTrackedCall("lead", o.getLong("id"), phone) }
        wa.setOnClickListener { chooseTemplate("lead", o.getLong("id")) }
        if (o.optLong("converted_contact_id", 0L) > 0) { convert.text = "Already converted"; convert.isEnabled = false }
        else convert.setOnClickListener {
            api.runAsync { try { val c=api.convertLead(o.getLong("id")); runOnUiThread { toast("Converted to Contact #${c.getLong("id")}"); showContacts() } } catch(e:Exception){ runOnUiThread{toast(e.message ?: "Conversion failed")} } }
        }
        addText(box, "Recent Calls", 18f, true)
        val calls = o.optJSONArray("calls") ?: org.json.JSONArray()
        for (i in 0 until calls.length()) {
            val c = calls.getJSONObject(i)
            box.addView(card(c.optString("phone"), "${c.optString("direction")} • ${c.optInt("duration_seconds")} sec"))
        }
        scroll.addView(box)
    }

    private fun chooseTemplate(type: String, id: Long) {
        api.runAsync {
            try {
                val templates = api.templates()
                runOnUiThread {
                    val labels = templates.map { "${it.name} · ${it.situation}" }.toTypedArray()
                    AlertDialog.Builder(this).setTitle("WhatsApp message").setItems(labels) { _, which ->
                        api.runAsync {
                            try {
                                val result = api.whatsapp(type, id, templates[which].situation)
                                runOnUiThread { startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(result.getString("url")))) }
                            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not create WhatsApp message") } }
                        }
                    }.show()
                }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load templates") } }
        }
    }

    private fun showContacts() {
        setupShell("Contacts")
        val scroll = contentScroll(); root.addView(scroll, LinearLayout.LayoutParams(-1, 0, 1f))
        api.runAsync {
            try {
                val rows = api.contacts()
                runOnUiThread { rows.forEach { c -> scroll.addView(card("${c.firstName} ${c.lastName}".trim(), "${c.company.ifBlank { "Contact" }} • ${c.mobile}${if (c.customerCode != null) " • ${c.customerCode}" else ""}") { showContact(c.id) }) } }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load contacts") } }
        }
    }

    private fun showContact(id: Long) {
        api.runAsync {
            try {
                val o = api.contact(id)
                runOnUiThread {
                    setupShell("Contact")
                    val scroll = contentScroll(); val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
                    addText(box, "${o.optString("first_name")} ${o.optString("last_name")}".trim(), 26f, true)
                    addText(box, "${o.optString("company")}\n${o.optString("mobile")}\n${o.optString("email")}", 15f, false)
                    val call=Button(this).apply{text="📞 Call"}; val wa=Button(this).apply{text="💬 WhatsApp"}; val convert=Button(this).apply{text="Convert to Customer"}
                    box.addView(call,lp());box.addView(wa,lp());box.addView(convert,lp())
                    val phone=o.optString("whatsapp").ifBlank{o.optString("mobile")}
                    call.setOnClickListener{startTrackedCall("contact",id,phone)};wa.setOnClickListener{chooseTemplate("contact",id)}
                    if(o.optJSONObject("customer")!=null){convert.text="Already a Customer";convert.isEnabled=false}else convert.setOnClickListener{convertContact(id)}
                    scroll.addView(box); root.addView(scroll,LinearLayout.LayoutParams(-1,0,1f))
                }
            } catch(e:Exception){runOnUiThread{toast(e.message ?: "Could not load contact")}}
        }
    }

    private fun convertContact(id: Long) {
        api.runAsync { try { val c=api.convertContact(id); runOnUiThread { toast("Converted to ${c.optString("customer_code")}"); showCustomers() } } catch(e:Exception){runOnUiThread{toast(e.message ?: "Conversion failed")}} }
    }

    private fun showCustomers() {
        setupShell("Customers")
        val scroll=contentScroll();root.addView(scroll,LinearLayout.LayoutParams(-1,0,1f))
        api.runAsync { try { val rows=api.customers(); runOnUiThread { rows.forEach { c -> scroll.addView(card(c.code,"${c.name.trim()} • ${c.company} • ${c.mobile}"){showCustomer(c.id)}) } } } catch(e:Exception){runOnUiThread{toast(e.message ?: "Could not load customers")}} }
    }

    private fun showCustomer(id: Long) {
        api.runAsync {
            try {
                val o=api.customer(id)
                runOnUiThread {
                    setupShell("Customer")
                    val scroll=contentScroll(); val box=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL}; val c=o.optJSONObject("contact")
                    addText(box,o.optString("customer_code"),26f,true)
                    addText(box,"${c?.optString("first_name") ?: ""} ${c?.optString("last_name") ?: ""}\n${c?.optString("company") ?: ""}\n${c?.optString("mobile") ?: ""}",15f,false)
                    val call=Button(this).apply{text="📞 Call"};val wa=Button(this).apply{text="💬 WhatsApp"};box.addView(call,lp());box.addView(wa,lp())
                    val phone=c?.optString("whatsapp")?.ifBlank{c.optString("mobile")} ?: ""
                    call.setOnClickListener{startTrackedCall("customer",id,phone)};wa.setOnClickListener{chooseTemplate("customer",id)}
                    scroll.addView(box);root.addView(scroll,LinearLayout.LayoutParams(-1,0,1f))
                }
            } catch(e:Exception){runOnUiThread{toast(e.message ?: "Could not load customer")}}
        }
    }

    private fun showFollowUps() {
        setupShell("Follow-ups")
        val scroll=contentScroll();root.addView(scroll,LinearLayout.LayoutParams(-1,0,1f))
        api.runAsync {
            try {
                val rows=api.followUps()
                runOnUiThread {
                    for(i in 0 until rows.length()) {
                        val f=rows.getJSONObject(i);val sub=f.optJSONObject("subject")
                        val name=if(sub?.has("first_name")==true) "${sub.optString("first_name")} ${sub.optString("last_name")}".trim() else f.optString("type")
                        scroll.addView(card(name,"${f.optString("scheduled_for")} • ${f.optString("status")} • ${f.optString("note")}"))
                    }
                }
            } catch(e:Exception){runOnUiThread{toast(e.message ?: "Could not load follow-ups")}}
        }
    }

    private fun startTrackedCall(subjectType:String,subjectId:Long,phone:String){
        if(phone.isBlank()){toast("No phone number");return}
        requestCallPermissionsThen {
            val serviceIntent=Intent(this,CallTrackingService::class.java).apply{putExtra("subject_type",subjectType);putExtra("subject_id",subjectId);putExtra("phone",phone)}
            try{startForegroundService(serviceIntent)}catch(_:Exception){startService(serviceIntent)}
            val clean=phone.filter{it.isDigit()||it=='+'}
            try{
                if(checkSelfPermission(Manifest.permission.CALL_PHONE)==PackageManager.PERMISSION_GRANTED) startActivity(Intent(Intent.ACTION_CALL,Uri.parse("tel:$clean")))
                else startActivity(Intent(Intent.ACTION_DIAL,Uri.parse("tel:$clean")))
            }catch(e:Exception){toast("Could not open phone: ${e.message}")}
        }
    }

    private fun requestCallPermissionsThen(action:()->Unit){
        val need=mutableListOf<String>()
        if(checkSelfPermission(Manifest.permission.CALL_PHONE)!=PackageManager.PERMISSION_GRANTED) need+=Manifest.permission.CALL_PHONE
        if(checkSelfPermission(Manifest.permission.RECORD_AUDIO)!=PackageManager.PERMISSION_GRANTED) need+=Manifest.permission.RECORD_AUDIO
        if(need.isEmpty()) action() else {requestPermissions(need.toTypedArray(),500);toast("Allow call and microphone permissions, then tap Call again")}
    }

    private fun edit(hint:String)=EditText(this).apply{this.hint=hint;setPadding(12,10,12,10)}
    private fun lp()=LinearLayout.LayoutParams(-1,LinearLayout.LayoutParams.WRAP_CONTENT).apply{setMargins(0,7,0,7)}
    private fun section(text:String)=TextView(this).apply{this.text=text;textSize=18f;setTypeface(null,1);setPadding(0,14,0,8)}
    private fun addText(parent:LinearLayout,text:String,size:Float,bold:Boolean){parent.addView(TextView(this).apply{this.text=text;textSize=size;setTextColor(Color.rgb(28,37,54));if(bold)setTypeface(null,1);setPadding(0,8,0,8)},lp())}
    private fun card(titleText:String,subtitle:String="",click:(()->Unit)?=null)=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(16,14,16,14);setBackgroundColor(Color.WHITE);addView(TextView(this@MainActivity).apply{text=titleText;textSize=17f;setTypeface(null,1)},lp());if(subtitle.isNotBlank())addView(TextView(this@MainActivity).apply{text=subtitle;textSize=14f;setTextColor(Color.DKGRAY)},lp());click?.let{setOnClickListener{it()}};layoutParams=LinearLayout.LayoutParams(-1,LinearLayout.LayoutParams.WRAP_CONTENT).apply{setMargins(0,7,0,7)}}
    private fun toast(message:String)=android.widget.Toast.makeText(this,message,android.widget.Toast.LENGTH_LONG).show()
    override fun onDestroy(){worker.shutdownNow();super.onDestroy()}
}
