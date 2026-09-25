package com.omnigocrm
import android.content.Context
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import org.json.JSONObject
import org.json.JSONArray
class ApiClient(private val context:Context){
 private val session=SessionManager(context)
 private fun request(method:String,path:String,body:String?=null):String{
  val c=(URL(base()+path).openConnection() as HttpURLConnection)
  c.requestMethod=method;c.connectTimeout=15000;c.readTimeout=20000;c.setRequestProperty("Accept","application/json")
  session.token?.let{c.setRequestProperty("Authorization","Bearer $it")}
  if(body!=null){c.doOutput=true;c.setRequestProperty("Content-Type","application/json");c.outputStream.use{it.write(body.toByteArray())}}
  val text=(if(c.responseCode in 200..299)c.inputStream else c.errorStream)?.bufferedReader()?.use{it.readText()}?:""
  if(c.responseCode !in 200..299)throw IllegalStateException("HTTP "+c.responseCode+": "+text)
  return text
 }
 private fun base()="https://crm.example.com/api/"
 fun login(email:String,password:String):JSONObject{val j=JSONObject(request("POST","login",JSONObject().put("email",email).put("password",password).toString()));val u=j.getJSONObject("user");session.save(j.getString("token"),u.getString("name"),u.getString("role"));return j}
 fun logout(){try{request("POST","logout")}catch(_:Exception){}}
 fun dashboard()=JSONObject(request("GET","dashboard"))
 fun leads(search:String=""):List<LeadItem>{val q=if(search.isBlank())"" else "?search="+URLEncoder.encode(search,"UTF-8");val a=JSONObject(request("GET","leads"+q)).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);LeadItem(o.getLong("id"),o.optString("first_name"),o.optString("last_name"),o.optString("company"),o.optString("mobile"),o.optString("status"))}}
 fun lead(id:Long)=JSONObject(request("GET","leads/$id")).getJSONObject("lead")
 fun createLead(f:String,l:String,c:String,m:String,r:String)=JSONObject(request("POST","leads",JSONObject().put("first_name",f).put("last_name",l).put("company",c).put("mobile",m).put("requirement",r).put("status","new").toString())).getJSONObject("lead")
 fun convertLead(id:Long)=JSONObject(request("POST","leads/$id/convert-contact")).getJSONObject("contact")
 fun contacts():List<ContactItem>{val a=JSONObject(request("GET","contacts")).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);ContactItem(o.getLong("id"),o.optString("first_name"),o.optString("last_name"),o.optString("company"),o.optString("mobile"),o.optJSONObject("customer")?.optString("customer_code"))}}
 fun customers():List<CustomerItem>{val a=JSONObject(request("GET","customers")).getJSONArray("data");return(0 until a.length()).map{val o=a.getJSONObject(it);val c=o.optJSONObject("contact");CustomerItem(o.getLong("id"),o.optString("customer_code"),(c?.optString("first_name")?:"")+" "+(c?.optString("last_name")?:""),c?.optString("company")?:"",c?.optString("mobile")?:"")}}
 fun companies():List<CompanyItem>{val a=JSONObject(request("GET","companies")).optJSONArray("data")?:JSONArray();return(0 until a.length()).map{val o=a.getJSONObject(it);CompanyItem(o.getLong("id"),o.optString("name"),o.optString("phone"),o.optString("email"))}}
 fun tasks():List<TaskItem>{val a=JSONObject(request("GET","tasks")).optJSONArray("data")?:JSONArray();return(0 until a.length()).map{val o=a.getJSONObject(it);TaskItem(o.getLong("id"),o.optString("title"),o.optString("due_at"),o.optString("priority"),o.optString("status"))}}
 fun completeTask(id:Long)=request("POST","tasks/$id/complete")
 fun followUps()=JSONObject(request("GET","follow-ups")).optJSONArray("data")?:JSONArray()
 fun logCall(type:String,id:Long,phone:String,duration:Int){request("POST","calls",JSONObject().put("subject_type",type).put("subject_id",id).put("phone",phone).put("duration_seconds",duration).put("direction","outgoing").put("status","completed").toString())}
 fun whatsapp(type:String,id:Long,situation:String):String=JSONObject(request("GET","whatsapp/$type/$id?situation="+URLEncoder.encode(situation,"UTF-8"))).getString("url")
}