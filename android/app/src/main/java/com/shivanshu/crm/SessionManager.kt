package com.shivanshu.crm

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences("crm_session", Context.MODE_PRIVATE)
    var token: String?
        get() = prefs.getString("token", null)
        private set(value) { prefs.edit().putString("token", value).apply() }
    var userName: String?
        get() = prefs.getString("user_name", null)
        private set(value) { prefs.edit().putString("user_name", value).apply() }
    var role: String?
        get() = prefs.getString("role", null)
        private set(value) { prefs.edit().putString("role", value).apply() }

    fun save(token: String, name: String, role: String) { this.token = token; userName = name; this.role = role }
    fun clear() { prefs.edit().clear().apply() }
}
