package com.omnigocrm
import android.content.Context
class SessionManager(context:Context){
 private val p=context.getSharedPreferences("omnigocrm",Context.MODE_PRIVATE)
 var token:String? get()=p.getString("token",null);private set(v){p.edit().putString("token",v).apply()}
 var name:String? get()=p.getString("name",null);private set(v){p.edit().putString("name",v).apply()}
 var role:String? get()=p.getString("role",null);private set(v){p.edit().putString("role",v).apply()}
 fun save(t:String,n:String,r:String){token=t;name=n;role=r}
 fun clear(){p.edit().clear().apply()}
}