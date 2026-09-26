package com.omnigocrm.android

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

    fun actOnWhatsAppConversation(conversationId: String, action: String): JSONObject {
        require(action == "read" || action == "close" || action == "open")
        return JSONObject(
            request(
                "POST",
                "OmniGoCRM/WhatsApp/conversationAction",
                JSONObject()
                    .put("conversationId", conversationId)
                    .put("action", action)
                    .toString()
            )
        )
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
        return JSONObject(
            request(
                "POST",
                "OmniGoCRM/Devices/register",
                JSONObject()
                    .put("platform", "Android")
                    .put("pushProvider", "FCM")
                    .put("pushToken", token)
                    .put("deviceName", deviceName)
                    .toString()
            )
        )
    }
}
