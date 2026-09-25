package com.shivanshu.crm

import android.app.Activity
import android.app.AlertDialog
import android.content.Intent
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import java.util.concurrent.Executors
import org.json.JSONArray
import org.json.JSONObject

class MainActivity : Activity() {
    private lateinit var api: ApiClient
    private lateinit var session: SessionManager
    private lateinit var root: LinearLayout
    private lateinit var body: LinearLayout
    private val worker = Executors.newCachedThreadPool()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        api = ApiClient(this)
        session = SessionManager(this)
        if (session.token.isNullOrBlank()) showLogin() else showDashboard()
    }

    private fun showLogin() {
        val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(40, 70, 40, 40) }
        addText(box, "OmniGoCRM", 30f, true)
        addText(box, "Native Android client for the EspoCRM-based OmniGoCRM server", 16f, false)
        val username = EditText(this).apply { hint = "Username or email" }
        val password = EditText(this).apply { hint = "Password"; inputType = 0x81 }
        val login = Button(this).apply { text = "Sign in" }
        box.addView(username, lp()); box.addView(password, lp()); box.addView(login, lp())
        login.setOnClickListener {
            if (username.text.isNullOrBlank() || password.text.isNullOrBlank()) { toast("Username and password are required."); return@setOnClickListener }
            login.isEnabled = false
            api.runAsync {
                try { api.login(AppConfig.BASE_URL, username.text.toString().trim(), password.text.toString()); runOnUiThread { showDashboard() } }
                catch (e: Exception) { runOnUiThread { login.isEnabled = true; toast(e.message ?: "Login failed") } }
            }
        }
        setContentView(box)
    }

    private fun ensureWorkspace() {
        api.runAsync {
            try {
                val rows = api.myWorkspaces()

                if (rows.length() == 0) {
                    runOnUiThread { promptCreateWorkspace() }
                    return@runAsync
                }

                val activeIndex = (0 until rows.length())
                    .firstOrNull { rows.getJSONObject(it).optBoolean("active", false) }

                if (activeIndex != null) {
                    runOnUiThread { showDashboard() }
                } else {
                    api.switchWorkspace(rows.getJSONObject(0).optString("id"))
                    runOnUiThread { showDashboard() }
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not load workspaces") }
            }
        }
    }

    private fun promptCreateWorkspace() {
        val input = EditText(this).apply { hint = "Workspace name" }

        AlertDialog.Builder(this)
            .setTitle("Create your workspace")
            .setView(input)
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Create") { _, _ ->
                val name = input.text.toString().trim()

                if (name.isBlank()) {
                    toast("Workspace name is required.")
                    return@setPositiveButton
                }

                api.runAsync {
                    try {
                        api.createWorkspace(name)
                        runOnUiThread { showDashboard() }
                    } catch (e: Exception) {
                        runOnUiThread { toast(e.message ?: "Could not create workspace") }
                    }
                }
            }
            .show()
    }

    private fun pickWorkspace() {
        api.runAsync {
            try {
                val rows = api.myWorkspaces()
                runOnUiThread {
                    if (rows.length() == 0) {
                        promptCreateWorkspace()
                        return@runOnUiThread
                    }

                    val names = Array(rows.length()) { i ->
                        rows.getJSONObject(i).optString("name").ifBlank { rows.getJSONObject(i).optString("slug") }
                    }

                    AlertDialog.Builder(this)
                        .setTitle("Switch workspace")
                        .setItems(names) { _, which ->
                            api.runAsync {
                                try {
                                    api.switchWorkspace(rows.getJSONObject(which).optString("id"))
                                    runOnUiThread { showDashboard() }
                                } catch (e: Exception) {
                                    runOnUiThread { toast(e.message ?: "Could not switch workspace") }
                                }
                            }
                        }
                        .setNegativeButton("Cancel", null)
                        .setNeutralButton("New workspace") { _, _ -> promptCreateWorkspace() }
                        .show()
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not load workspaces") }
            }
        }
    }

    private fun shell(title: String) {
        root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setBackgroundColor(Color.rgb(247, 249, 252)) }
        val header = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; setPadding(16, 16, 10, 10); setBackgroundColor(Color.WHITE) }
        addText(header, title, 22f, true, LinearLayout.LayoutParams(0, -2, 1f))
        val logout = Button(this).apply { text = "Logout" }
        header.addView(logout, LinearLayout.LayoutParams(110, -2))
        logout.setOnClickListener { api.runAsync { api.logout(); runOnUiThread { session.clear(); showLogin() } } }
        root.addView(header)
        val navScroll = ScrollView(this).apply { isHorizontalScrollBarEnabled = false }
        val nav = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; setPadding(6, 3, 6, 3); setBackgroundColor(Color.WHITE) }
        val menu = listOf("Dashboard", "Leads", "Contacts", "Accounts", "Opportunities", "Tasks", "Meetings", "Calls", "Products", "WhatsApp")
        menu.forEach { label ->
            val button = Button(this).apply { text = label }
            nav.addView(button, LinearLayout.LayoutParams(-2, -2))
            button.setOnClickListener {
                when (label) {
                    "Dashboard" -> showDashboard()
                    "Leads" -> showLeads()
                    "Contacts" -> showEntityList("Contact", "Contacts")
                    "Accounts" -> showEntityList("Account", "Accounts")
                    "Opportunities" -> showEntityList("Opportunity", "Opportunities")
                    "Tasks" -> showTasks()
                    "Meetings" -> showEntityList("Meeting", "Meetings")
                    "Calls" -> showEntityList("Call", "Calls")
                    "Products" -> showEntityList("Product", "Products")
                    "WhatsApp" -> showWhatsApp()
                }
            }
        }
        navScroll.addView(nav); root.addView(navScroll)
        val content = ScrollView(this).apply { setPadding(14, 12, 14, 30) }
        body = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        content.addView(body); root.addView(content, LinearLayout.LayoutParams(-1, 0, 1f)); setContentView(root)
    }

    private fun showDashboard() {
        shell("Dashboard")
        addText(body, "Welcome, " + (session.userName ?: "User"), 24f, true)
        val summary = TextView(this).apply { textSize = 17f; setPadding(0, 12, 0, 20) }; body.addView(summary, lp())
        api.runAsync {
            try {
                val leads = api.list("Lead", "id,firstName,lastName,accountName,phoneNumber,whatsappNumber,status,leadStage", 5)
                val contacts = api.list("Contact", "id,name", 5)
                val accounts = api.list("Account", "id,name", 5)
                val opportunities = api.list("Opportunity", "id,name", 5)
                val tasks = api.list("Task", "id,name,status", 5)
                val messages = api.list("WhatsAppMessage", "id,name,direction,status,textBody", 5)
                runOnUiThread {
                    summary.text = "Leads: " + leads.length() + "   Contacts: " + contacts.length() + "\n" +
                        "Accounts: " + accounts.length() + "   Opportunities: " + opportunities.length() + "\n" +
                        "Tasks: " + tasks.length() + "   WhatsApp messages: " + messages.length()
                    addText(body, "Recent leads", 19f, true)
                    if (leads.length() == 0) addText(body, "No leads found.", 14f, false)
                    for (i in 0 until leads.length()) { val row = leads.getJSONObject(i); body.addView(card(leadName(row), leadSubtitle(row)) { showLead(row.optString("id")) }) }
                }
            } catch (e: Exception) { runOnUiThread { summary.text = "Could not load dashboard data."; toast(e.message ?: "Dashboard error") } }
        }
    }

    private fun showLeads() {
        shell("Leads")
        val add = Button(this).apply { text = "+ New Lead" }; body.addView(add, lp()); add.setOnClickListener { showCreateLead() }
        api.runAsync {
            try { val rows = api.list("Lead", "id,firstName,lastName,accountName,phoneNumber,whatsappNumber,status,leadStage", 100); runOnUiThread { renderLeadList(rows) } }
            catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load leads") } }
        }
    }

    private fun renderLeadList(rows: JSONArray) {
        for (i in 0 until rows.length()) { val row = rows.getJSONObject(i); body.addView(card(leadName(row), leadSubtitle(row)) { showLead(row.optString("id")) }) }
    }

    private fun showCreateLead() {
        shell("New Lead")
        val first = edit("First name *"); val last = edit("Last name"); val company = edit("Company / Account")
        val phone = edit("Phone"); val whatsapp = edit("WhatsApp number"); val source = edit("Lead source detail"); val requirement = edit("Requirement / notes")
        listOf(first, last, company, phone, whatsapp, source, requirement).forEach { body.addView(it, lp()) }
        val save = Button(this).apply { text = "Create Lead" }; body.addView(save, lp())
        save.setOnClickListener {
            if (first.text.isNullOrBlank()) { toast("First name is required."); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync {
                try {
                    api.createLead(first.text.toString().trim(), last.text.toString().trim(), company.text.toString().trim(), phone.text.toString().trim(), whatsapp.text.toString().trim(), source.text.toString().trim(), requirement.text.toString().trim())
                    runOnUiThread { showLeads() }
                } catch (e: Exception) { runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not create lead") } }
            }
        }
    }

    private fun showLead(id: String) {
        shell("Lead")
        api.runAsync { try { val row = api.read("Lead", id); runOnUiThread { renderLeadDetail(row) } } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load lead") } } }
    }

    private fun renderLeadDetail(row: JSONObject) {
        addText(body, leadName(row), 28f, true)
        val details = listOf(row.optString("accountName"), row.optString("phoneNumber"), row.optString("whatsappNumber"), row.optString("emailAddress"), row.optString("leadStage"), row.optString("leadSourceDetail"), row.optString("description"))
        addText(body, details.filter { it.isNotBlank() }.joinToString("\n"), 15f, false)
        val call = Button(this).apply { text = "Call" }; val whatsapp = Button(this).apply { text = "WhatsApp message" }; val convert = Button(this).apply { text = "Convert to Contact" }
        body.addView(call, lp()); body.addView(whatsapp, lp()); body.addView(convert, lp())
        val phone = row.optString("whatsappNumber").ifBlank { row.optString("phoneNumber") }
        call.setOnClickListener {
            if (phone.isBlank()) toast("No phone number.") else startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:" + phone.filter { it.isDigit() || it == '+' })))
        }
        whatsapp.setOnClickListener {
            val id = row.optString("id")
            if (!row.optBoolean("whatsappOptIn", false)) toast("WhatsApp opt-in is required before CRM-initiated messaging.") else promptWhatsAppMessage(id)
        }
        convert.setOnClickListener {
            convert.isEnabled = false
            api.runAsync {
                try { val result = api.convertLead(row.optString("id")); runOnUiThread { toast("Lead converted. Contact: " + result.optString("createdContactId", "created")); showLead(row.optString("id")) } }
                catch (e: Exception) { runOnUiThread { convert.isEnabled = true; toast(e.message ?: "Conversion failed") } }
            }
        }
    }

    private fun promptWhatsAppMessage(leadId: String) {
        val input = EditText(this).apply { hint = "Message"; minLines = 3 }
        AlertDialog.Builder(this).setTitle("Send WhatsApp message").setView(input).setNegativeButton("Cancel", null).setPositiveButton("Send") { _, _ ->
            val message = input.text.toString().trim()
            if (message.isBlank()) { toast("Message is empty."); return@setPositiveButton }
            api.runAsync {
                try { val result = api.sendWhatsAppText(leadId, message); runOnUiThread { toast("Sent. Provider message ID: " + result.optString("providerMessageId")) } }
                catch (e: Exception) { runOnUiThread { toast(e.message ?: "WhatsApp send failed") } }
            }
        }.show()
    }

    private fun showEntityList(entityType: String, title: String) {
        shell(title)
        api.runAsync {
            try { val rows = api.entityItems(entityType); runOnUiThread { if (rows.isEmpty()) addText(body, "No records found.", 14f, false); rows.forEach { item -> body.addView(card(item.title, item.subtitle) { showGenericDetail(entityType, title, item.id) }) } } }
            catch (e: Exception) { runOnUiThread { toast(e.message ?: ("Could not load " + title)) } }
        }
    }

    private fun showGenericDetail(entityType: String, title: String, id: String) {
        shell(title.removeSuffix("s"))
        api.runAsync {
            try { val row = api.read(entityType, id); runOnUiThread {
                addText(body, row.optString("name").ifBlank { id }, 26f, true)
                val lines = row.keys().asSequence().filter { it != "id" && it != "deleted" }.take(20).map { key -> key + ": " + row.optString(key) }.filter { !it.endsWith(": ") }.toList()
                addText(body, lines.joinToString("\n"), 14f, false)
            } } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load record") } }
        }
    }

    private fun showTasks() {
        shell("Tasks")
        val add = Button(this).apply { text = "+ New Task" }; body.addView(add, lp()); add.setOnClickListener { showCreateTask() }
        api.runAsync {
            try { val rows = api.list("Task", "id,name,status,priority,dateEnd", 100); runOnUiThread {
                for (i in 0 until rows.length()) { val row = rows.getJSONObject(i); body.addView(card(row.optString("name").ifBlank { "Task" }, row.optString("priority") + " • " + row.optString("status") + " • " + row.optString("dateEnd")) {
                    if (row.optString("status") != "Completed") api.runAsync { try { api.completeTask(row.optString("id")); runOnUiThread { showTasks() } } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not complete task") } } }
                }) }
            } } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load tasks") } }
        }
    }

    private fun showCreateTask() {
        shell("New Task")
        val name = edit("Task name"); val description = edit("Description"); val priority = edit("Priority (Low / Normal / High)")
        listOf(name, description, priority).forEach { body.addView(it, lp()) }
        val save = Button(this).apply { text = "Create task" }; body.addView(save, lp())
        save.setOnClickListener {
            if (name.text.isNullOrBlank()) { toast("Task name is required."); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync {
                try { api.createTask(name.text.toString().trim(), description.text.toString().trim(), priority.text.toString().trim().ifBlank { "Normal" }); runOnUiThread { showTasks() } }
                catch (e: Exception) { runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not create task") } }
            }
        }
    }

    private fun showWhatsApp() {
        shell("WhatsApp Inbox")
        api.runAsync {
            try { val rows = api.list("WhatsAppMessage", "id,name,direction,status,messageType,fromNumber,toNumber,textBody,receivedAt,sentAt", 100); runOnUiThread {
                if (rows.length() == 0) addText(body, "No WhatsApp messages yet. Configure the Cloud API and webhook on the server.", 14f, false)
                for (i in 0 until rows.length()) { val row = rows.getJSONObject(i); body.addView(card(row.optString("direction") + " • " + row.optString("status"), row.optString("fromNumber") + " → " + row.optString("toNumber") + "\n" + row.optString("textBody"))) }
            } } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load WhatsApp messages") } }
        }
    }

    private fun leadName(row: JSONObject): String {
        return (row.optString("firstName") + " " + row.optString("lastName")).trim().ifBlank { row.optString("name").ifBlank { row.optString("id") } }
    }

    private fun leadSubtitle(row: JSONObject): String {
        return listOf(row.optString("accountName"), row.optString("phoneNumber"), row.optString("leadStage"), row.optString("status")).filter { it.isNotBlank() }.joinToString(" • ")
    }

    private fun addText(parent: LinearLayout, text: String, size: Float, bold: Boolean, params: LinearLayout.LayoutParams = lp()) {
        val view = TextView(this).apply { this.text = text; textSize = size; setTextColor(Color.rgb(20, 30, 50)); setPadding(0, 8, 0, 8); if (bold) setTypeface(null, 1) }
        parent.addView(view, params)
    }

    private fun edit(hintText: String): EditText = EditText(this).apply { hint = hintText; setPadding(12, 10, 12, 10) }

    private fun card(title: String, subtitle: String, click: (() -> Unit)? = null): LinearLayout {
        val view = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(14, 12, 14, 12); setBackgroundColor(Color.WHITE) }
        addText(view, title, 17f, true); addText(view, subtitle, 14f, false); click?.let { view.setOnClickListener { it() } }
        view.layoutParams = LinearLayout.LayoutParams(-1, -2).apply { setMargins(0, 6, 0, 6) }
        return view
    }

    private fun lp(): LinearLayout.LayoutParams = LinearLayout.LayoutParams(-1, LinearLayout.LayoutParams.WRAP_CONTENT).apply { setMargins(0, 7, 0, 7) }
    private fun toast(message: String) = android.widget.Toast.makeText(this, message, android.widget.Toast.LENGTH_LONG).show()
}
