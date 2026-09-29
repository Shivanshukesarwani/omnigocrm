package com.shivanshu.crm

import android.content.Context
import android.util.Base64
import java.io.File
import java.io.FileInputStream
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import java.nio.charset.StandardCharsets
import java.util.concurrent.Executors
import org.json.JSONArray
import org.json.JSONObject

class ApiClient(context: Context) {
    private val appContext = context
    private val session = SessionManager(context)
    private val executor = Executors.newCachedThreadPool()

    fun runAsync(block: () -> Unit) = executor.execute(block)

    private fun authHeader(username: String, secret: String): String {
        val raw = username + ":" + secret
        val encoded = Base64.encodeToString(raw.toByteArray(StandardCharsets.UTF_8), Base64.NO_WRAP)
        return "Basic " + encoded
    }

    private fun request(
        method: String,
        path: String,
        body: String? = null,
        usernameOverride: String? = null,
        secretOverride: String? = null,
        baseUrlOverride: String? = null,
    ): String {
        val apiUrl = (baseUrlOverride ?: session.baseUrl ?: AppConfig.BASE_URL).trim().trimEnd('/') + "/api/v1/"
        val connection = (URL(apiUrl + path.trimStart('/')).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 15000
            readTimeout = 30000
            doInput = true
            setRequestProperty("Accept", "application/json")

            val username = usernameOverride ?: session.username
            val secret = secretOverride ?: session.token
            if (!username.isNullOrBlank() && !secret.isNullOrBlank()) {
                setRequestProperty("Espo-Authorization", authHeader(username, secret))
            }

            if (body != null) {
                doOutput = true
                setRequestProperty("Content-Type", "application/json")
            }
        }

        try {
            if (body != null) {
                connection.outputStream.use { it.write(body.toByteArray(StandardCharsets.UTF_8)) }
            }
            val code = connection.responseCode
            val stream = if (code in 200..299) connection.inputStream else connection.errorStream
            val text = stream?.bufferedReader()?.use { it.readText() } ?: ""
            if (code !in 200..299) {
                throw IllegalStateException("HTTP " + code + ": " + extractError(text))
            }
            return text
        } finally {
            connection.disconnect()
        }
    }

    private fun extractError(text: String): String {
        return try {
            val json = JSONObject(text)
            json.optString("message").ifBlank { text }
        } catch (_: Exception) {
            text
        }
    }

    fun login(baseUrl: String, username: String, password: String): JSONObject {
        val cleanBaseUrl = baseUrl.trim().trimEnd('/') + "/"
        if (!cleanBaseUrl.startsWith("https://") && !cleanBaseUrl.startsWith("http://")) {
            throw IllegalStateException("CRM URL must start with http:// or https://")
        }
        val response = JSONObject(
            request(
                "GET",
                "App/user",
                usernameOverride = username,
                secretOverride = password,
                baseUrlOverride = cleanBaseUrl
            )
        )
        val user = response.optJSONObject("user") ?: JSONObject()
        val token = response.optString("token")
        if (token.isBlank()) throw IllegalStateException("CRM did not return an authentication token.")
        val name = user.optString("name").ifBlank { user.optString("userName") }.ifBlank { username }
        val resolvedUsername = user.optString("userName").ifBlank { username }
        val email = user.optString("emailAddress").ifBlank { username }
        session.save(token, name, resolvedUsername, email, cleanBaseUrl)
        return response
    }

    fun logout() {
        try { request("POST", "App/destroyAuthToken", "{}") } catch (_: Exception) {}
    }


    fun myWorkspaces(): JSONArray {
        return JSONObject(request("GET", "OmniGoCRM/Workspace/mine"))
            .optJSONArray("list") ?: JSONArray()
    }

    fun createWorkspace(name: String, slug: String = ""): JSONObject {
        val body = JSONObject().put("name", name)
        if (slug.isNotBlank()) body.put("slug", slug)
        return JSONObject(request("POST", "OmniGoCRM/Workspace/create", body.toString()))
    }

    fun switchWorkspace(workspaceId: String): JSONObject {
        return JSONObject(
            request(
                "POST",
                "OmniGoCRM/Workspace/switch",
                JSONObject().put("workspaceId", workspaceId).toString()
            )
        )
    }

    fun dashboardSummary(): JSONObject {
        return JSONObject(request("GET", "OmniGoCRM/Dashboard/summary"))
    }

    fun createQuote(name: String, currency: String = "INR"): JSONObject {
        return JSONObject(request("POST", "OmniGoCRM/Sales/quote", JSONObject()
            .put("name", name)
            .put("currency", currency)
            .toString()))
    }

    fun addQuoteItem(quoteId: String, name: String, quantity: Double, unitPrice: Double): JSONObject {
        return JSONObject(request("POST", "OmniGoCRM/Sales/quoteItem", JSONObject()
            .put("quoteId", quoteId)
            .put("name", name)
            .put("quantity", quantity)
            .put("unitPrice", unitPrice)
            .toString()))
    }

    fun convertQuote(quoteId: String): JSONObject {
        return JSONObject(request("POST", "OmniGoCRM/Sales/quoteConvert", JSONObject().put("quoteId", quoteId).toString()))
    }

    fun createPayment(name: String, amount: Double, method: String, orderId: String = ""): JSONObject {
        val body = JSONObject().put("name", name).put("amount", amount).put("method", method)
        if (orderId.isNotBlank()) body.put("orderId", orderId)
        return JSONObject(request("POST", "OmniGoCRM/Sales/payment", body.toString()))
    }

    fun createBroadcastCampaign(name: String, templateName: String, languageCode: String): JSONObject {
        return JSONObject(request("POST", "BroadcastCampaign", JSONObject()
            .put("name", name)
            .put("templateName", templateName)
            .put("languageCode", languageCode)
            .put("status", "Draft")
            .toString()))
    }

    fun broadcastCampaign(campaignId: String): JSONObject {
        return JSONObject(request("GET", "OmniGoCRM/Broadcast/campaign?campaignId=" + url(campaignId)))
    }

    fun addBroadcastRecipient(campaignId: String, leadId: String): JSONObject {
        return JSONObject(request("POST", "OmniGoCRM/Broadcast/recipient", JSONObject()
            .put("campaignId", campaignId)
            .put("leadId", leadId)
            .toString()))
    }

    fun scheduleBroadcast(campaignId: String): JSONObject {
        val scheduledAt = java.text.SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", java.util.Locale.US).apply {
            timeZone = java.util.TimeZone.getTimeZone("UTC")
        }.format(java.util.Date())
        return JSONObject(request("POST", "OmniGoCRM/Broadcast/schedule", JSONObject()
            .put("campaignId", campaignId)
            .put("scheduledAt", scheduledAt)
            .toString()))
    }

    fun list(entityType: String, select: String? = null, maxSize: Int = 50, textFilter: String? = null): JSONArray {
        val params = mutableListOf<String>()
        params += "maxSize=" + maxSize
        params += "orderBy=createdAt"
        params += "order=desc"
        if (!select.isNullOrBlank()) params += "select=" + URLEncoder.encode(select, "UTF-8")
        if (!textFilter.isNullOrBlank()) params += "textFilter=" + URLEncoder.encode(textFilter, "UTF-8")
        val json = JSONObject(request("GET", entityType + "?" + params.joinToString("&")))
        return json.optJSONArray("list") ?: JSONArray()
    }

    fun read(entityType: String, id: String): JSONObject {
        return JSONObject(request("GET", entityType + "/" + url(id)))
    }

    private fun url(value: String): String = URLEncoder.encode(value, "UTF-8")

    fun createLead(firstName: String, lastName: String, company: String, phone: String, whatsapp: String, sourceDetail: String, requirement: String): JSONObject {
        val body = JSONObject()
        body.put("firstName", firstName)
        if (lastName.isNotBlank()) body.put("lastName", lastName)
        if (company.isNotBlank()) body.put("accountName", company)
        if (phone.isNotBlank()) body.put("phoneNumber", phone)
        if (whatsapp.isNotBlank()) body.put("whatsappNumber", whatsapp)
        if (sourceDetail.isNotBlank()) body.put("leadSourceDetail", sourceDetail)
        if (requirement.isNotBlank()) body.put("description", requirement)
        body.put("leadStage", "New")
        body.put("preferredContactChannel", "WhatsApp")
        return JSONObject(request("POST", "Lead", body.toString()))
    }

    fun convertLead(id: String): JSONObject {
        val lead = read("Lead", id)
        val contact = JSONObject()
        listOf("firstName", "lastName", "phoneNumber", "emailAddress", "accountName").forEach { key ->
            val value = lead.optString(key)
            if (value.isNotBlank()) contact.put(key, value)
        }
        val body = JSONObject().put("id", id).put("records", JSONObject().put("Contact", contact))
        return JSONObject(request("POST", "Lead/action/convert", body.toString()))
    }

    fun createTask(name: String, description: String, priority: String): JSONObject {
        val body = JSONObject().put("name", name).put("status", "Not Started").put("priority", priority)
        if (description.isNotBlank()) body.put("description", description)
        return JSONObject(request("POST", "Task", body.toString()))
    }

    fun completeTask(id: String) {
        request("PUT", "Task/" + url(id), JSONObject().put("status", "Completed").toString())
    }

    fun listFollowUps(): JSONArray {
        return JSONObject(request("GET", "follow-ups")).optJSONArray("list") ?: JSONArray()
    }

    fun createFollowUp(
        name: String,
        dateStart: String,
        parentType: String,
        parentId: String,
        description: String,
        priority: String,
    ): JSONObject {
        val body = JSONObject()
            .put("name", name)
            .put("dateStart", dateStart)
            .put("parentType", parentType)
            .put("parentId", parentId)
            .put("priority", priority)
        if (description.isNotBlank()) body.put("description", description)
        return JSONObject(request("POST", "follow-ups", body.toString()))
    }

    fun sendWhatsAppText(leadId: String, messageBody: String): JSONObject {
        val payload = JSONObject().put("leadId", leadId).put("body", messageBody)
        return JSONObject(request("POST", "OmniGoCRM/WhatsApp/sendText", payload.toString()))
    }

    fun logCall(subjectType: String, subjectId: String, phone: String, duration: Int): String {
        val payload = JSONObject()
            .put("name", "Mobile call")
            .put("status", "Held")
            .put("direction", "Outbound")
            .put("duration", duration)
            .put("description", phone)
            .put("parentId", subjectId)
            .put("parentType", subjectType)
        return JSONObject(request("POST", "Call", payload.toString())).optString("id")
    }


    fun listWhatsAppConversations(): JSONArray {
        return list(
            "WhatsAppConversation",
            "id,name,waId,phoneNumber,customerDisplayName,status,unreadCount,lastMessageAt,lastMessagePreview,leadId,contactId",
            100
        )
    }

    fun actOnWhatsAppConversation(conversationId: String, action: String, note: String? = null): JSONObject {
        require(action in setOf("read", "close", "open", "assign", "unassign", "note"))
        val payload = JSONObject().put("conversationId", conversationId).put("action", action)
        if (note != null) payload.put("note", note)
        return JSONObject(
            request(
                "POST",
                "OmniGoCRM/WhatsApp/conversationAction",
                payload.toString()
            )
        )
    }

    fun listWhatsAppConversationMessages(conversationId: String): JSONArray {
        val query = "conversationId=" + url(conversationId)
        return JSONObject(request("GET", "OmniGoCRM/WhatsApp/conversationMessages?$query"))
            .optJSONArray("items") ?: JSONArray()
    }

    fun downloadWhatsAppMedia(messageId: String, mimeType: String): File {
        val apiUrl = (session.baseUrl ?: AppConfig.BASE_URL).trim().trimEnd('/') + "/api/v1/"
        val connection = (URL(apiUrl + "OmniGoCRM/WhatsApp/media?messageId=" + url(messageId)).openConnection() as HttpURLConnection).apply {
            requestMethod = "GET"
            connectTimeout = 15000
            readTimeout = 60000
            setRequestProperty("Accept", "image/*, application/octet-stream")
            val username = session.username
            val secret = session.token
            if (!username.isNullOrBlank() && !secret.isNullOrBlank()) {
                setRequestProperty("Espo-Authorization", authHeader(username, secret))
            }
        }
        try {
            val code = connection.responseCode
            if (code !in 200..299) {
                val error = connection.errorStream?.bufferedReader()?.use { it.readText() } ?: ""
                throw IllegalStateException("HTTP $code: " + extractError(error))
            }
            val directory = File(appContext.cacheDir, "whatsapp-media").apply { mkdirs() }
            val staleBefore = System.currentTimeMillis() - 24L * 60 * 60 * 1000
            directory.listFiles()?.filter { it.lastModified() < staleBefore }?.forEach { it.delete() }
            val extension = mapOf(
                "image/jpeg" to "jpg", "image/png" to "png", "image/gif" to "gif", "image/webp" to "webp",
                "audio/aac" to "aac", "audio/amr" to "amr", "audio/mpeg" to "mp3", "audio/mp4" to "m4a", "audio/ogg" to "ogg",
                "video/mp4" to "mp4", "video/3gpp" to "3gp", "application/pdf" to "pdf", "text/plain" to "txt", "text/csv" to "csv",
                "application/msword" to "doc", "application/vnd.ms-excel" to "xls", "application/vnd.ms-powerpoint" to "ppt",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document" to "docx",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" to "xlsx",
                "application/vnd.openxmlformats-officedocument.presentationml.presentation" to "pptx",
            )[mimeType] ?: "dat"
            val file = File.createTempFile("media-", ".$extension", directory)
            try {
                connection.inputStream.use { input ->
                    file.outputStream().use { output ->
                        val buffer = ByteArray(8192)
                        var total = 0L
                        while (true) {
                            val count = input.read(buffer)
                            if (count < 0) break
                            total += count
                            if (total > 100L * 1024 * 1024) throw IllegalStateException("WhatsApp media exceeds the 100 MB download limit.")
                            output.write(buffer, 0, count)
                        }
                    }
                }
                return file
            } catch (e: Exception) {
                file.delete()
                throw e
            }
        } finally {
            connection.disconnect()
        }
    }

    fun logCompletedCall(
        leadId: String,
        phoneNumber: String,
        durationSeconds: Int,
        status: String = "completed",
    ): JSONObject {
        return JSONObject(
            request(
                "POST",
                "OmniGoCRM/Calling/log",
                JSONObject()
                    .put("leadId", leadId)
                    .put("phoneNumber", phoneNumber)
                    .put("duration", durationSeconds)
                    .put("status", status)
                    .put("direction", "Outbound")
                    .toString()
            )
        )
    }

    fun entityItems(entityType: String): List<EntityItem> {
        val rows = list(entityType, "id,name,status", 100)
        val result = mutableListOf<EntityItem>()
        for (i in 0 until rows.length()) {
            val row = rows.getJSONObject(i)
            result += EntityItem(row.optString("id"), row.optString("name").ifBlank { row.optString("id") }, row.optString("status"))
        }
        return result
    }

    fun uploadRecording(callId: String, file: File): Boolean {
        val boundary = "----OmniGoCRM" + System.currentTimeMillis()
        val connection = (URL(AppConfig.API_URL + "Attachment").openConnection() as HttpURLConnection).apply {
            requestMethod = "POST"
            doOutput = true
            doInput = true
            connectTimeout = 20000
            readTimeout = 30000
            setRequestProperty("Espo-Authorization", authHeader(session.username ?: "", session.token ?: ""))
            setRequestProperty("Content-Type", "multipart/form-data; boundary=" + boundary)
        }

        return try {
            connection.outputStream.use { out ->
                fun write(text: String) { out.write(text.toByteArray(StandardCharsets.UTF_8)) }
                write("--" + boundary + "\r\nContent-Disposition: form-data; name=\"file\"; filename=\"" + file.name + "\"\r\nContent-Type: audio/mp4\r\n\r\n")
                FileInputStream(file).use { input -> input.copyTo(out) }
                write("\r\n--" + boundary + "--\r\n")
            }
            connection.responseCode in 200..299
        } finally { connection.disconnect() }
    }

    fun registerFcmToken(token: String, deviceName: String): JSONObject {
        val deviceId = android.provider.Settings.Secure.getString(
            appContext.contentResolver,
            android.provider.Settings.Secure.ANDROID_ID
        ).orEmpty()
        return JSONObject(
            request(
                "POST",
                "OmniGoCRM/Devices/register",
                JSONObject()
                    .put("platform", "Android")
                    .put("pushProvider", "FCM")
                    .put("pushToken", token)
                    .put("externalDeviceId", deviceId)
                    .put("deviceName", deviceName)
                    .toString()
            )
        )
    }
}
