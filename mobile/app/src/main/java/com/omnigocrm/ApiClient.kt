package com.omnigocrm
import android.content.Context
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import org.json.JSONArray
import org.json.JSONObject

class ApiClient(context:Context){
 private val session=SessionManager(context)
 private fun call(method:String,path:String,body:String?=null):String{
  val c=(URL(base()+path).openConnection() as HttpURLConnection)
  c.requestMethod=method;c.connectTimeout=15000;c.readTimeout=20000
  c.setRequestProperty("Accept","application/json")
  session.token?.let{c.setRequestProperty("Authorization","Bearer $it")}
  if(body!=null){c.doOutput=true;c.setRequestProperty("Content-Type","application/json");c.outputStream.use{it.write(body.toByteArray())}}
  val stream=if(c.responseCode in 200..299)c.inputStream else c.errorStream
  val text=stream?.bufferedReader()?.use{it.readText()}?:""
  if(c.responseCode !in 200..299)throw IllegalStateException("HTTP "+c.responseCode+": "+text)
  return text
 }
 private fun base()="https://crm.example.com/api/"
 fun login(e:String,p:String):JSONObject{val j=JSONObject(call("POST","login",JSONObject().put("email",e).put("password",p).toString()));val u=j.getJSONObject("user");session.save(j.getString("token"),u.optString("name"),u.optString("role"));return j}
 fun logout(){try{call("POST","logout")}catch(_:Exception){}}
 fun dashboard()=JSONObject(call("GET","dashboard"))
 fun leads(search:String=""):List<LeadItem>{val q=if(search.isBlank())"" else "?search="+URLEncoder.encode(search,"UTF-8");val a=JSONObject(call("GET","leads"+q)).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);LeadItem(o.getLong("id"),o.optString("first_name"),o.optString("last_name"),o.optString("company"),o.optString("mobile"),o.optString("status"),o.optString("requirement"))}}
 fun lead(id:Long)=JSONObject(call("GET","leads/$id")).getJSONObject("lead")
 fun createLead(first:String,last:String,company:String,mobile:String,source:String,requirement:String)=JSONObject(call("POST","leads",JSONObject().apply{put("first_name",first);put("last_name",last);put("company",company);put("mobile",mobile);put("source",source);put("requirement",requirement);put("status","new")}.toString())).getJSONObject("lead")
 fun convertLead(id:Long)=JSONObject(call("POST","leads/$id/convert-contact")).getJSONObject("contact")
 fun contacts():List<ContactItem>{val a=JSONObject(call("GET","contacts")).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);ContactItem(o.getLong("id"),o.optString("first_name"),o.optString("last_name"),o.optString("company"),o.optString("mobile"),o.optJSONObject("customer")?.optString("customer_code"))}}
 fun contact(id:Long)=JSONObject(call("GET","contacts/$id")).getJSONObject("contact")
 fun convertContact(id:Long)=JSONObject(call("POST","contacts/$id/convert-customer")).getJSONObject("customer")
 fun customers():List<CustomerItem>{val a=JSONObject(call("GET","customers")).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);val c=o.optJSONObject("contact");CustomerItem(o.getLong("id"),o.optString("customer_code"),(c?.optString("first_name")?:"")+" "+(c?.optString("last_name")?:""),c?.optString("company")?:"",c?.optString("mobile")?:"")}}
 fun customer(id:Long)=JSONObject(call("GET","customers/$id")).getJSONObject("customer")
 fun templates():List<TemplateItem>{val a=JSONArray(call("GET","message-templates"));return(0 until a.length()).map{val o=a.getJSONObject(it);TemplateItem(o.getLong("id"),o.optString("name"),o.optString("situation"),o.optString("body"))}}
 fun whatsapp(type:String,id:Long,situation:String)=JSONObject(call("GET","whatsapp/$type/$id?situation="+URLEncoder.encode(situation,"UTF-8")))
 fun followUps()=JSONObject(call("GET","follow-ups")).getJSONArray("data")
 fun companies():List<CompanyItem>{val a=JSONObject(call("GET","companies")).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);CompanyItem(o.getLong("id"),o.optString("name"),o.optString("email"),o.optString("phone"),o.optString("website"))}}
 fun tasks():List<TaskItem>{val a=JSONObject(call("GET","tasks")).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);TaskItem(o.getLong("id"),o.optString("title"),o.optString("due_at"),o.optString("priority"),o.optString("status"),o.optJSONObject("assignee")?.optString("name")?:"Unassigned")}}
 fun completeTask(id:Long)=JSONObject(call("POST","tasks/$id/complete")).getJSONObject("task")
 fun quotations()=JSONObject(call("GET","quotations")).getJSONArray("data")
 fun payments()=JSONObject(call("GET","payments")).getJSONArray("data")
}