package com.shivanshu.crm

import android.app.Activity
import android.app.AlertDialog
import android.content.Intent
import android.content.ClipData
import android.content.ActivityNotFoundException
import android.graphics.Color
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ImageView
import android.widget.ScrollView
import android.widget.TextView
import androidx.core.content.FileProvider
import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import com.google.firebase.messaging.FirebaseMessaging
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import java.util.concurrent.Executors
import org.json.JSONArray
import org.json.JSONObject

class MainActivity : Activity() {
    private lateinit var api: ApiClient
    private lateinit var session: SessionManager
    private lateinit var root: LinearLayout
    private lateinit var body: LinearLayout
    private val worker = Executors.newCachedThreadPool()
    private var pushRegistrationUser: String? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (Build.VERSION.SDK_INT >= 33 && checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(arrayOf(Manifest.permission.POST_NOTIFICATIONS), 4102)
        }
        api = ApiClient(this)
        session = SessionManager(this)
        if (session.token.isNullOrBlank()) showLogin() else ensureWorkspace()
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
                try { api.login(AppConfig.BASE_URL, username.text.toString().trim(), password.text.toString()); runOnUiThread { ensureWorkspace() } }
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
        val menu = listOf("Dashboard", "Leads", "Contacts", "Accounts", "Opportunities", "Tasks", "Follow-ups", "Meetings", "Calls", "Products", "Quotes", "Orders", "Payments", "Broadcasts", "WhatsApp")
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
                    "Follow-ups" -> showFollowUps()
                    "Meetings" -> showEntityList("Meeting", "Meetings")
                    "Calls" -> showEntityList("Call", "Calls")
                    "Products" -> showEntityList("Product", "Products")
                    "Quotes" -> showSalesList("Quote", "Quotes")
                    "Orders" -> showSalesList("Order", "Orders")
                    "Payments" -> showSalesList("Payment", "Payments")
                    "Broadcasts" -> showBroadcasts()
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
        registerForPush()
        addText(body, "Welcome, " + (session.userName ?: "User"), 24f, true)
        val summary = TextView(this).apply { textSize = 17f; setPadding(0, 12, 0, 20) }; body.addView(summary, lp())
        api.runAsync {
            try {
                val dashboard = api.dashboardSummary()
                val counts = dashboard.optJSONObject("counts") ?: JSONObject()
                val inbox = dashboard.optJSONObject("inbox") ?: JSONObject()
                val leads = api.list("Lead", "id,firstName,lastName,accountName,phoneNumber,whatsappNumber,status,leadStage", 5)
                val averageReply = inbox.optInt("averageLatestReplySeconds30d", -1)
                val assignees = inbox.optJSONArray("byAssignee") ?: JSONArray()
                val trends = inbox.optJSONObject("messageTrend30d") ?: JSONObject()
                val inboundTrend = trends.optJSONArray("inbound") ?: JSONArray()
                val outboundTrend = trends.optJSONArray("outbound") ?: JSONArray()
                val dailyTrend = (maxOf(0, inboundTrend.length() - 7) until inboundTrend.length()).map { index ->
                    val inbound = inboundTrend.optJSONObject(index) ?: JSONObject()
                    val outbound = outboundTrend.optJSONObject(index) ?: JSONObject()
                    "${inbound.optString("date").takeLast(5)}: ${inbound.optInt("count")} in · ${outbound.optInt("count")} out"
                }.joinToString("\n")
                val assigneeSummary = (0 until assignees.length()).map { index ->
                    val assignee = assignees.optJSONObject(index) ?: JSONObject()
                    val reply = assignee.optInt("averageLatestReplySeconds30d", -1)
                    "${assignee.optString("name", "Workspace member")}: ${assignee.optInt("openConversations")} open · ${assignee.optInt("waitingForResponse")} waiting (${assignee.optInt("waitingOver24Hours")} >24h) · avg reply ${if (reply >= 0) formatDuration(reply) else "—"} (${assignee.optInt("latestReplySamples30d")} samples)"
                }.joinToString("\n")
                runOnUiThread {
                    summary.text = "Leads: ${counts.optInt("leads")}   Contacts: ${counts.optInt("contacts")}\n" +
                        "Accounts: ${counts.optInt("accounts")}   Opportunities: ${counts.optInt("opportunities")}\n" +
                        "WhatsApp: ${inbox.optInt("openConversations")} open · ${inbox.optInt("unassignedOpenConversations")} unassigned · ${inbox.optInt("waitingForResponse")} awaiting reply (${inbox.optInt("waitingOver24Hours")} >24h)\n" +
                        "Inbox · 30d: ${inbox.optInt("inboundMessages30d")} inbound · ${inbox.optInt("outboundMessages30d")} outbound · avg tracked latest reply ${if (averageReply >= 0) formatDuration(averageReply) else "—"} (${inbox.optInt("replySamples30d")} samples)" +
                        (if (dailyTrend.isNotEmpty()) "\nDaily inbox · 7d:\n$dailyTrend" else "") +
                        (if (assigneeSummary.isNotEmpty()) "\nBy assignee:\n$assigneeSummary" else "")
                    addText(body, "Recent leads", 19f, true)
                    if (leads.length() == 0) addText(body, "No leads found.", 14f, false)
                    for (i in 0 until leads.length()) { val row = leads.getJSONObject(i); body.addView(card(leadName(row), leadSubtitle(row)) { showLead(row.optString("id")) }) }
                }
            } catch (e: Exception) { runOnUiThread { summary.text = "Could not load dashboard data."; toast(e.message ?: "Dashboard error") } }
        }
    }

    private fun formatDuration(totalSeconds: Int): String = when {
        totalSeconds < 60 -> "${totalSeconds}s"
        totalSeconds < 3600 -> "${totalSeconds / 60}m ${totalSeconds % 60}s"
        else -> "${totalSeconds / 3600}h ${(totalSeconds % 3600) / 60}m"
    }

    private fun registerForPush() {
        val user = session.email ?: session.username ?: return
        if (pushRegistrationUser == user) return
        try {
            FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
                if (!task.isSuccessful) return@addOnCompleteListener
                val token = task.result ?: return@addOnCompleteListener
                api.runAsync {
                    try {
                        api.registerFcmToken(token, Build.MODEL ?: "Android")
                        pushRegistrationUser = user
                    } catch (_: Exception) {
                        // Registration is retried when the dashboard is opened again.
                    }
                }
            }
        } catch (_: IllegalStateException) {
            // Firebase is optional until google-services.json is configured.
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
        val followUp = Button(this).apply { text = "Schedule follow-up" }
        body.addView(call, lp()); body.addView(whatsapp, lp()); body.addView(followUp, lp()); body.addView(convert, lp())
        followUp.setOnClickListener { showCreateFollowUp("Lead", row.optString("id")) }
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

    private fun showSalesList(entityType: String, title: String) {
        shell(title)
        if (entityType == "Quote" || entityType == "Payment") {
            val create = Button(this).apply { text = if (entityType == "Quote") "+ New Quote" else "+ Record Payment" }
            body.addView(create, lp())
            create.setOnClickListener { if (entityType == "Quote") showCreateQuote() else showCreatePayment() }
        }
        api.runAsync {
            try {
                val rows = api.list(entityType, "id,name,status,totalAmount,amount,quoteNumber,orderNumber,paymentNumber,currency", 100)
                runOnUiThread {
                    if (rows.length() == 0) addText(body, "No $title yet.", 14f, false)
                    for (i in 0 until rows.length()) {
                        val row = rows.getJSONObject(i)
                        val number = listOf("quoteNumber", "orderNumber", "paymentNumber").map { row.optString(it) }.firstOrNull { it.isNotBlank() }.orEmpty()
                        val amount = row.optString("totalAmount").ifBlank { row.optString("amount") }
                        body.addView(card(
                            row.optString("name").ifBlank { number.ifBlank { title.removeSuffix("s") } },
                            listOf(number, row.optString("status"), amount.takeIf { it.isNotBlank() }?.let { row.optString("currency").ifBlank { "INR" } + " " + it }).filter { !it.isNullOrBlank() }.joinToString(" • "),
                        ) { showSalesDetail(entityType, title, row.optString("id")) })
                    }
                }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load $title") } }
        }
    }

    private fun showSalesDetail(entityType: String, title: String, id: String) {
        shell(title.removeSuffix("s"))
        api.runAsync {
            try {
                val row = api.read(entityType, id)
                runOnUiThread {
                    val number = listOf("quoteNumber", "orderNumber", "paymentNumber").map { row.optString(it) }.firstOrNull { it.isNotBlank() }.orEmpty()
                    addText(body, row.optString("name").ifBlank { number.ifBlank { title.removeSuffix("s") } }, 24f, true)
                    val amount = row.optString("totalAmount").ifBlank { row.optString("amount") }
                    addText(body, listOf(number, row.optString("status"), amount.takeIf { it.isNotBlank() }?.let { row.optString("currency").ifBlank { "INR" } + " " + it }, row.optString("paymentDate"), row.optString("orderDate"), row.optString("issueDate"), row.optString("notes")).filter { !it.isNullOrBlank() }.joinToString("\n"), 15f, false)
                    if (entityType == "Quote") {
                        val addItem = Button(this).apply { text = "Add line item" }
                        val convert = Button(this).apply { text = "Convert to order"; isEnabled = row.optString("status") != "Converted" }
                        body.addView(addItem, lp()); body.addView(convert, lp())
                        addItem.setOnClickListener { promptQuoteItem(id) }
                        convert.setOnClickListener {
                            convert.isEnabled = false
                            api.runAsync { try { val order = api.convertQuote(id); runOnUiThread { toast("Order created: " + order.optString("orderNumber")); showSalesList("Order", "Orders") } } catch (e: Exception) { runOnUiThread { convert.isEnabled = true; toast(e.message ?: "Could not convert quote") } } }
                        }
                    }
                    if (entityType == "Order") {
                        val payment = Button(this).apply { text = "Record payment" }
                        body.addView(payment, lp()); payment.setOnClickListener { showCreatePayment(id) }
                    }
                }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load $title") } }
        }
    }

    private fun showCreateQuote() {
        shell("New Quote")
        val name = edit("Quote name *")
        val currency = edit("Currency (default INR)").apply { setText("INR") }
        body.addView(name, lp()); body.addView(currency, lp())
        val save = Button(this).apply { text = "Create quote" }
        body.addView(save, lp())
        save.setOnClickListener {
            if (name.text.isNullOrBlank()) { toast("Quote name is required."); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync { try { api.createQuote(name.text.toString().trim(), currency.text.toString().trim().ifBlank { "INR" }); runOnUiThread { showSalesList("Quote", "Quotes") } } catch (e: Exception) { runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not create quote") } } }
        }
    }

    private fun promptQuoteItem(quoteId: String) {
        val box = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(24, 8, 24, 0) }
        val name = edit("Item name *"); val quantity = edit("Quantity").apply { setText("1") }; val price = edit("Unit price *").apply { inputType = 8194 }
        listOf(name, quantity, price).forEach { box.addView(it, lp()) }
        AlertDialog.Builder(this).setTitle("Add quote line item").setView(box).setNegativeButton("Cancel", null).setPositiveButton("Add", null).create().also { dialog ->
            dialog.setOnShowListener {
                dialog.getButton(AlertDialog.BUTTON_POSITIVE).setOnClickListener {
                    val itemName = name.text.toString().trim(); val qty = quantity.text.toString().toDoubleOrNull(); val unit = price.text.toString().toDoubleOrNull()
                    if (itemName.isBlank() || qty == null || qty <= 0 || unit == null || unit < 0) { toast("Enter a name, positive quantity and non-negative unit price."); return@setOnClickListener }
                    dialog.getButton(AlertDialog.BUTTON_POSITIVE).isEnabled = false
                    api.runAsync { try { api.addQuoteItem(quoteId, itemName, qty, unit); runOnUiThread { dialog.dismiss(); showSalesDetail("Quote", "Quote", quoteId) } } catch (e: Exception) { runOnUiThread { dialog.getButton(AlertDialog.BUTTON_POSITIVE).isEnabled = true; toast(e.message ?: "Could not add item") } } }
                }
            }
            dialog.show()
        }
    }

    private fun showCreatePayment(orderId: String = "") {
        shell("Record Payment")
        val name = edit("Payment reference/name")
        val amount = edit("Amount *").apply { inputType = 8194 }
        val method = edit("Method (Cash, Bank Transfer, Card, UPI, Other)").apply { setText("Other") }
        listOf(name, amount, method).forEach { body.addView(it, lp()) }
        val save = Button(this).apply { text = "Record payment" }
        body.addView(save, lp())
        save.setOnClickListener {
            val value = amount.text.toString().toDoubleOrNull()
            if (value == null || value <= 0) { toast("Enter an amount greater than zero."); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync { try { api.createPayment(name.text.toString().trim().ifBlank { "Payment" }, value, method.text.toString().trim().ifBlank { "Other" }, orderId); runOnUiThread { showSalesList("Payment", "Payments") } } catch (e: Exception) { runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not record payment") } } }
        }
    }

    private fun showBroadcasts() {
        shell("WhatsApp Broadcasts")
        val create = Button(this).apply { text = "+ New Campaign" }
        body.addView(create, lp()); create.setOnClickListener { showCreateBroadcast() }
        api.runAsync {
            try {
                val campaigns = api.list("BroadcastCampaign", "id,name,status,templateName,scheduledAt,totalRecipients,sentCount,failedCount", 100)
                runOnUiThread {
                    if (campaigns.length() == 0) addText(body, "No broadcast campaigns yet.", 14f, false)
                    for (i in 0 until campaigns.length()) {
                        val campaign = campaigns.getJSONObject(i)
                        val progress = "${campaign.optInt("sentCount")}/${campaign.optInt("totalRecipients")} sent"
                        body.addView(card(campaign.optString("name"), campaign.optString("status") + " • " + campaign.optString("templateName") + " • " + progress) {
                            showBroadcastCampaign(campaign.optString("id"))
                        })
                    }
                }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load broadcasts") } }
        }
    }

    private fun showCreateBroadcast() {
        shell("New Broadcast")
        val name = edit("Campaign name *")
        val template = edit("Approved WhatsApp template name *")
        val language = edit("Template language").apply { setText("en_US") }
        listOf(name, template, language).forEach { body.addView(it, lp()) }
        addText(body, "Only active, approved templates can be scheduled. Recipient opt-in and plan limits are checked by the server.", 14f, false)
        val save = Button(this).apply { text = "Create draft" }
        body.addView(save, lp())
        save.setOnClickListener {
            if (name.text.isNullOrBlank() || template.text.isNullOrBlank()) { toast("Campaign name and template are required."); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync {
                try {
                    val campaign = api.createBroadcastCampaign(name.text.toString().trim(), template.text.toString().trim(), language.text.toString().trim().ifBlank { "en_US" })
                    runOnUiThread { showBroadcastCampaign(campaign.optString("id")) }
                } catch (e: Exception) { runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not create campaign") } }
            }
        }
    }

    private fun showBroadcastCampaign(campaignId: String) {
        shell("Broadcast Campaign")
        api.runAsync {
            try {
                val result = api.broadcastCampaign(campaignId)
                val campaign = result.optJSONObject("campaign") ?: JSONObject()
                val recipients = result.optJSONArray("recipients") ?: JSONArray()
                runOnUiThread {
                    addText(body, campaign.optString("name"), 25f, true)
                    addText(body, listOf(campaign.optString("status"), campaign.optString("templateName"), campaign.optString("languageCode"), campaign.optString("scheduledAt")).filter { it.isNotBlank() }.joinToString(" • "), 14f, false)
                    addText(body, "Recipients: ${recipients.length()}   Sent: ${campaign.optInt("sentCount")}   Failed: ${campaign.optInt("failedCount")}", 15f, true)
                    val add = Button(this).apply { text = "Add lead recipient"; isEnabled = campaign.optString("status") == "Draft" }
                    val schedule = Button(this).apply { text = "Schedule now"; isEnabled = campaign.optString("status") == "Draft" || campaign.optString("status") == "Scheduled" }
                    body.addView(add, lp()); body.addView(schedule, lp())
                    add.setOnClickListener { chooseBroadcastLead(campaignId) }
                    schedule.setOnClickListener {
                        schedule.isEnabled = false
                        api.runAsync { try { api.scheduleBroadcast(campaignId); runOnUiThread { toast("Campaign scheduled. The server will enforce template approval and monthly quota."); showBroadcastCampaign(campaignId) } } catch (e: Exception) { runOnUiThread { schedule.isEnabled = true; toast(e.message ?: "Could not schedule campaign") } } }
                    }
                    if (recipients.length() == 0) addText(body, "Add recipients before scheduling.", 14f, false)
                    for (i in 0 until recipients.length()) {
                        val recipient = recipients.getJSONObject(i)
                        body.addView(card(recipient.optString("name").ifBlank { recipient.optString("phoneNumber") }, recipient.optString("status") + " • " + recipient.optString("errorMessage").ifBlank { recipient.optString("sentAt") }))
                    }
                }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load campaign") } }
        }
    }

    private fun chooseBroadcastLead(campaignId: String) {
        api.runAsync {
            try {
                val leads = api.list("Lead", "id,firstName,lastName,name,whatsappNumber,whatsappOptIn", 100)
                runOnUiThread {
                    if (leads.length() == 0) { toast("No leads found in this workspace."); return@runOnUiThread }
                    val labels = Array(leads.length()) { index ->
                        val lead = leads.getJSONObject(index)
                        val label = listOf(lead.optString("firstName"), lead.optString("lastName")).filter { it.isNotBlank() }.joinToString(" ").ifBlank { lead.optString("name") }
                        label + " • " + lead.optString("whatsappNumber") + if (lead.optBoolean("whatsappOptIn")) " • opted in" else " • no opt-in"
                    }
                    AlertDialog.Builder(this).setTitle("Add lead").setItems(labels) { _, which ->
                        api.runAsync {
                            try {
                                val result = api.addBroadcastRecipient(campaignId, leads.getJSONObject(which).optString("id"))
                                runOnUiThread { toast("Recipient: " + result.optString("status")); showBroadcastCampaign(campaignId) }
                            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not add recipient") } }
                        }
                    }.setNegativeButton("Cancel", null).show()
                }
            } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load leads") } }
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

    private fun showFollowUps() {
        shell("Follow-ups")
        api.runAsync {
            try {
                val rows = api.listFollowUps()
                runOnUiThread {
                    if (rows.length() == 0) addText(body, "No follow-ups scheduled.", 14f, false)
                    for (i in 0 until rows.length()) {
                        val row = rows.getJSONObject(i)
                        body.addView(card(
                            row.optString("name").ifBlank { "Follow-up" },
                            row.optString("priority") + " • " + row.optString("status") + " • " + row.optString("dateStart")
                        ) {
                            if (row.optString("status") != "Completed") api.runAsync {
                                try {
                                    api.completeTask(row.optString("id"))
                                    runOnUiThread { showFollowUps() }
                                } catch (e: Exception) {
                                    runOnUiThread { toast(e.message ?: "Could not complete follow-up") }
                                }
                            }
                        })
                    }
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not load follow-ups") }
            }
        }
    }

    private fun showCreateFollowUp(parentType: String, parentId: String) {
        shell("New Follow-up")
        val name = edit("Follow-up name *")
        val description = edit("Notes")
        val priority = edit("Priority (Low / Normal / High / Urgent)").apply { setText("Normal") }
        val schedule = Calendar.getInstance().apply {
            add(Calendar.DAY_OF_YEAR, 1)
            set(Calendar.HOUR_OF_DAY, 9)
            set(Calendar.MINUTE, 0)
            set(Calendar.SECOND, 0)
        }
        val dateButton = Button(this).apply { text = "Due: " + displayDate(schedule) }
        val timeButton = Button(this).apply { text = "Time: " + displayTime(schedule) }
        body.addView(name, lp()); body.addView(description, lp()); body.addView(priority, lp())
        body.addView(dateButton, lp()); body.addView(timeButton, lp())
        dateButton.setOnClickListener {
            DatePickerDialog(this, { _, year, month, day ->
                schedule.set(year, month, day)
                dateButton.text = "Due: " + displayDate(schedule)
            }, schedule.get(Calendar.YEAR), schedule.get(Calendar.MONTH), schedule.get(Calendar.DAY_OF_MONTH)).show()
        }
        timeButton.setOnClickListener {
            TimePickerDialog(this, { _, hour, minute ->
                schedule.set(Calendar.HOUR_OF_DAY, hour)
                schedule.set(Calendar.MINUTE, minute)
                timeButton.text = "Time: " + displayTime(schedule)
            }, schedule.get(Calendar.HOUR_OF_DAY), schedule.get(Calendar.MINUTE), false).show()
        }
        val save = Button(this).apply { text = "Schedule follow-up" }
        body.addView(save, lp())
        save.setOnClickListener {
            if (name.text.isNullOrBlank()) { toast("Follow-up name is required."); return@setOnClickListener }
            save.isEnabled = false
            api.runAsync {
                try {
                    val dateStart = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US).format(schedule.time)
                    api.createFollowUp(name.text.toString().trim(), dateStart, parentType, parentId, description.text.toString().trim(), priority.text.toString().trim().ifBlank { "Normal" })
                    runOnUiThread { showFollowUps() }
                } catch (e: Exception) {
                    runOnUiThread { save.isEnabled = true; toast(e.message ?: "Could not schedule follow-up") }
                }
            }
        }
    }

    private fun displayDate(calendar: Calendar): String = SimpleDateFormat("EEE, MMM d, yyyy", Locale.getDefault()).format(calendar.time)
    private fun displayTime(calendar: Calendar): String = SimpleDateFormat("h:mm a", Locale.getDefault()).format(calendar.time)

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
            try { val rows = api.listWhatsAppConversations(); runOnUiThread {
                if (rows.length() == 0) addText(body, "No WhatsApp conversations yet. Configure the Cloud API and webhook on the server.", 14f, false)
                for (i in 0 until rows.length()) {
                    val row = rows.getJSONObject(i)
                    val preview = row.optString("lastMessagePreview").ifBlank { row.optString("waId") }
                    val unread = row.optInt("unreadCount")
                    body.addView(card(
                        row.optString("name") + if (unread > 0) " · $unread unread" else "",
                        row.optString("status") + " · " + preview,
                    ) { showWhatsAppConversation(row) })
                }
            } } catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not load WhatsApp messages") } }
        }
    }

    private fun showWhatsAppConversation(conversation: JSONObject) {
        val conversationId = conversation.optString("id")
        shell(conversation.optString("name").ifBlank { "WhatsApp Conversation" })
        api.runAsync {
            try {
                api.actOnWhatsAppConversation(conversationId, "read")
                val messages = api.listWhatsAppConversationMessages(conversationId)
                runOnUiThread {
                    val phone = conversation.optString("waId")
                    addText(body, phone, 14f, false)
                    val reply = Button(this).apply {
                        text = "Reply"
                        isEnabled = conversation.optString("leadId").isNotBlank()
                        setOnClickListener { promptWhatsAppReply(conversation) }
                    }
                    body.addView(reply, lp())
                    body.addView(Button(this).apply {
                        text = "Add internal note"
                        setOnClickListener { promptWhatsAppInternalNote(conversation) }
                    }, lp())
                    val close = Button(this).apply {
                        text = if (conversation.optString("status") == "Closed") "Reopen conversation" else "Close conversation"
                        setOnClickListener {
                            val action = if (conversation.optString("status") == "Closed") "open" else "close"
                            api.runAsync {
                                try {
                                    val result = api.actOnWhatsAppConversation(conversationId, action)
                                    conversation.put("status", result.optString("status"))
                                    runOnUiThread { showWhatsAppConversation(conversation) }
                                }
                                catch (e: Exception) { runOnUiThread { toast(e.message ?: "Could not update conversation") } }
                            }
                        }
                    }
                    body.addView(close, lp())
                    if (messages.length() == 0) addText(body, "No messages in this conversation yet.", 14f, false)
                    for (i in 0 until messages.length()) {
                        val message = messages.getJSONObject(i)
                        val preview = message.optString("textBody").ifBlank { message.optString("mediaCaption").ifBlank { message.optString("messageType") } }
                        val label = if (message.optBoolean("isInternal")) "Internal note" else message.optString("direction") + " · " + message.optString("status")
                        body.addView(card(
                            label,
                            preview + "\n" + message.optString("receivedAt").ifBlank { message.optString("sentAt") },
                        ))
                        if (
                            message.optString("mediaId").isNotBlank() &&
                            message.optString("mediaMimeType") in setOf("image/jpeg", "image/png", "image/gif", "image/webp")
                        ) {
                            body.addView(Button(this).apply {
                                text = "View image"
                                setOnClickListener {
                                    isEnabled = false
                                    api.runAsync {
                                        try {
                                            val bytes = api.downloadWhatsAppMedia(message.optString("id"), message.optString("mediaMimeType"))
                                            val bitmap = BitmapFactory.decodeFile(bytes.absolutePath)
                                            runOnUiThread {
                                                isEnabled = true
                                                if (bitmap == null) {
                                                    toast("Could not display WhatsApp image.")
                                                } else {
                                                    body.addView(ImageView(this@MainActivity).apply {
                                                        setImageBitmap(bitmap)
                                                        adjustViewBounds = true
                                                        maxHeight = 700
                                                        scaleType = ImageView.ScaleType.FIT_CENTER
                                                    }, lp())
                                                }
                                            }
                                        } catch (e: Exception) {
                                            runOnUiThread { isEnabled = true; toast(e.message ?: "Could not load WhatsApp image") }
                                        }
                                    }
                                }
                            }, lp())
                        } else if (message.optString("mediaId").isNotBlank() && isOpenableWhatsAppMime(message.optString("mediaMimeType"))) {
                            body.addView(Button(this).apply {
                                text = "Open or share media"
                                setOnClickListener { openWhatsAppMedia(message) }
                            }, lp())
                        }
                    }
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not load WhatsApp conversation") }
            }
        }
    }

    private fun promptWhatsAppReply(conversation: JSONObject) {
        val input = edit("Message")
        AlertDialog.Builder(this)
            .setTitle("Reply on WhatsApp")
            .setView(input)
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Send") { _, _ ->
                val message = input.text.toString().trim()
                if (message.isEmpty()) { toast("Message is required."); return@setPositiveButton }
                api.runAsync {
                    try {
                        api.sendWhatsAppText(conversation.optString("leadId"), message)
                        runOnUiThread { showWhatsAppConversation(conversation) }
                    } catch (e: Exception) {
                        runOnUiThread { toast(e.message ?: "Could not send WhatsApp reply") }
                    }
                }
            }
            .show()
    }

    private fun promptWhatsAppInternalNote(conversation: JSONObject) {
        val input = edit("Visible only to workspace users")
        AlertDialog.Builder(this)
            .setTitle("Add internal note")
            .setView(input)
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Save") { _, _ ->
                val note = input.text.toString().trim()
                if (note.isEmpty()) { toast("Note is required."); return@setPositiveButton }
                api.runAsync {
                    try {
                        api.actOnWhatsAppConversation(conversation.optString("id"), "note", note)
                        runOnUiThread { showWhatsAppConversation(conversation) }
                    } catch (e: Exception) {
                        runOnUiThread { toast(e.message ?: "Could not save internal note") }
                    }
                }
            }
            .show()
    }

    private fun isOpenableWhatsAppMime(mimeType: String): Boolean = mimeType in setOf(
        "audio/aac", "audio/amr", "audio/mpeg", "audio/mp4", "audio/ogg",
        "video/mp4", "video/3gpp", "application/pdf", "text/plain", "text/csv",
        "application/msword", "application/vnd.ms-excel", "application/vnd.ms-powerpoint",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    )

    private fun openWhatsAppMedia(message: JSONObject) {
        api.runAsync {
            try {
                val mimeType = message.optString("mediaMimeType")
                val file = api.downloadWhatsAppMedia(message.optString("id"), mimeType)
                val uri = FileProvider.getUriForFile(this, "$packageName.fileprovider", file)
                val view = Intent(Intent.ACTION_VIEW).setDataAndType(uri, mimeType)
                    .addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                    .apply { clipData = ClipData.newUri(contentResolver, "WhatsApp media", uri) }
                runOnUiThread {
                    try {
                        startActivity(Intent.createChooser(view, "Open WhatsApp media"))
                    } catch (_: ActivityNotFoundException) {
                        val share = Intent(Intent.ACTION_SEND).setType(mimeType)
                            .putExtra(Intent.EXTRA_STREAM, uri)
                            .addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                        try {
                            startActivity(Intent.createChooser(share, "Share WhatsApp media"))
                        } catch (_: ActivityNotFoundException) {
                            toast("No app is available to open or share this media.")
                        }
                    }
                }
            } catch (e: Exception) {
                runOnUiThread { toast(e.message ?: "Could not open WhatsApp media") }
            }
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
