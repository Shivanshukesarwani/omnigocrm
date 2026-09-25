package com.shivanshu.crm

import android.content.Context
import java.io.BufferedInputStream
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

    private fun request(method: String, path: String, body: String? = null, contentType: String = "application/json"): String {
        val conn = (URL(AppConfig.API_URL + path).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 15000
            readTimeout = 20000
            doInput = true
            setRequestProperty("Accept", "application/json")
            session.token?.let { setRequestProperty("Authorization", "Bearer $it") }
            if (body != null) {
                doOutput = true
                setRequestProperty("Content-Type", contentType)
            }
        }
        try {
            if (body != null) conn.outputStream.use { it.write(body.toByteArray(StandardCharsets.UTF_8)) }
            val stream = if (conn.responseCode in 200..299) conn.inputStream else conn.errorStream
            val text = stream?.bufferedReader()?.use { it.readText() } ?: ""
            if (conn.responseCode !in 200..299) throw IllegalStateException("HTTP ${conn.responseCode}: $text")
            return text
        } finally { conn.disconnect() }
    }

    fun login(email: String, password: String): JSONObject {
        val result = request("POST", "login", JSONObject().put("email", email).put("password", password).toString())
        val json = JSONObject(result); val user = json.getJSONObject("user")
        session.save(json.getString("token"), user.getString("name"), user.getString("role"))
        return json
    }

    fun logout(){ try { request("POST","logout") } catch (_:Exception) {} }
    fun dashboard(): JSONObject = JSONObject(request("GET", "dashboard"))
    fun leads(search: String = ""): List<LeadItem> {
        val q = if (search.isBlank()) "" else "?search=" + URLEncoder.encode(search, "UTF-8")
        val data = JSONObject(request("GET", "leads$q")); val rows = data.getJSONArray("data"); val out = mutableListOf<LeadItem>()
        for (i in 0 until rows.length()) { val o=rows.getJSONObject(i); out += LeadItem(o.getLong("id"),o.optString("first_name"),o.optString("last_name"),o.optString("company"),o.optString("mobile"),o.optString("status"),o.optString("requirement")) }
        return out
    }
    fun lead(id: Long): JSONObject = JSONObject(request("GET", "leads/$id")).getJSONObject("lead")
    fun createLead(first: String,last: String,company:String,mobile:String,source:String,requirement:String): JSONObject {
        val body=JSONObject().apply{put("first_name",first);put("last_name",last);put("company",company);put("mobile",mobile);put("source",source);put("requirement",requirement);put("status","new")}
        return JSONObject(request("POST","leads",body.toString())).getJSONObject("lead")
    }
    fun convertLead(id:Long):JSONObject=JSONObject(request("POST","leads/$id/convert-contact")).getJSONObject("contact")
    fun contacts():List<ContactItem>{val rows=JSONObject(request("GET","contacts")).getJSONArray("data");return (0 until rows.length()).map{val o=rows.getJSONObject(it);ContactItem(o.getLong("id"),o.optString("first_name"),o.optString("last_name"),o.optString("company"),o.optString("mobile"),if(o.isNull("customer"))null else o.getJSONObject("customer").optString("customer_code"))}}
    fun contact(id:Long):JSONObject=JSONObject(request("GET","contacts/$id")).getJSONObject("contact")
    fun convertContact(id:Long):JSONObject{return JSONObject(request("POST","contacts/$id/convert-customer")).getJSONObject("customer")}
    fun customer(id:Long):JSONObject=JSONObject(request("GET","customers/$id")).getJSONObject("customer")
    fun customers():List<CustomerItem>{val rows=JSONObject(request("GET","customers")).getJSONArray("data");return (0 until rows.length()).map{val o=rows.getJSONObject(it);val c=o.optJSONObject("contact");CustomerItem(o.getLong("id"),o.optString("customer_code"),(c?.optString("first_name") ?: "")+" "+(c?.optString("last_name") ?: ""),c?.optString("company")?:"",c?.optString("mobile")?:"")}}
    fun templates():List<TemplateItem>{val rows=JSONArray(request("GET","message-templates"));return (0 until rows.length()).map{val o=rows.getJSONObject(it);TemplateItem(o.getLong("id"),o.getString("name"),o.getString("situation"),o.getString("body"))}}
    fun whatsapp(type:String,id:Long,situation:String):JSONObject{return JSONObject(request("GET","whatsapp/$type/$id?situation="+URLEncoder.encode(situation,"UTF-8")))}
    fun followUps():JSONArray=JSONObject(request("GET","follow-ups")).getJSONArray("data")
    fun logCallSimple(subjectType:String,subjectId:Long,phone:String,duration:Int):Long{
        val b=JSONObject().apply{put("subject_type",subjectType);put("subject_id",subjectId);put("phone",phone);put("duration_seconds",duration);put("direction","outgoing");put("status","completed")}
        return JSONObject(request("POST","calls",b.toString())).optLong("id")
    }

    fun uploadRecording(callId:Long,file:File):Boolean{
        val boundary="----CRM"+System.currentTimeMillis(); val conn=(URL(AppConfig.API_URL+"calls/$callId/recording").openConnection() as HttpURLConnection).apply{requestMethod="POST";doOutput=true;doInput=true;connectTimeout=20000;readTimeout=30000;setRequestProperty("Authorization","Bearer ${session.token}");setRequestProperty("Content-Type","multipart/form-data; boundary=$boundary")}
        try{
            conn.outputStream.use{out->
                fun write(s:String)=out.write(s.toByteArray(StandardCharsets.UTF_8))
                write("--$boundary\r\nContent-Disposition: form-data; name=\"recording\"; filename=\"${file.name}\"\r\nContent-Type: audio/mp4\r\n\r\n")
                FileInputStream(file).use{input->val buffer=ByteArray(8192);while(true){val n=input.read(buffer);if(n<=0)break;out.write(buffer,0,n)}}
                write("\r\n--$boundary--\r\n")
            }
            return conn.responseCode in 200..299
        }finally{conn.disconnect()}
    }
    fun companies(): JSONArray = JSONObject(request("GET","companies")).getJSONArray("data")

    fun createCompany(name:String,phone:String,email:String): JSONObject {
        val b=JSONObject().apply{put("name",name);put("phone",phone);put("email",email)}
        return JSONObject(request("POST","companies",b.toString()))
    }

    fun tasks(): JSONArray = JSONObject(request("GET","tasks")).getJSONArray("data")

    fun createTask(title:String,description:String): JSONObject {
        val b=JSONObject().apply{put("title",title);put("description",description);put("priority","normal")}
        return JSONObject(request("POST","tasks",b.toString()))
    }

    fun orders(): JSONArray = JSONObject(request("GET","orders")).getJSONArray("data")

    fun payments(): JSONArray = JSONObject(request("GET","payments")).getJSONArray("data")

    fun tags(): JSONArray = JSONArray(request("GET","tags"))

    fun createPayment(customerId:Long?,amount:Double,method:String,reference:String): JSONObject {
        val b=JSONObject().apply{
            if(customerId!=null)put("customer_id",customerId)
            put("amount",amount);put("method",method);put("reference",reference)
        }
        return JSONObject(request("POST","payments",b.toString()))
    }

    fun products(): JSONArray = JSONArray(request("GET","products"))
    fun quotations(): JSONArray = JSONObject(request("GET","quotations")).getJSONArray("data")

    fun notifications(): JSONArray = JSONObject(request("GET","notifications")).getJSONArray("data")
    fun markNotificationRead(id: String) { request("POST","notifications/$id/read", "{}") }

}
