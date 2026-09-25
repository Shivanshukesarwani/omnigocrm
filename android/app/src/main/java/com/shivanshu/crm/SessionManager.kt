package com.shivanshu.crm

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences("omnigocrm_session", Context.MODE_PRIVATE)

    var token: String?
        get() = prefs.getString("token", null)
        private set(value) { prefs.edit().putString("token", value).apply() }

    var userName: String?
        get() = prefs.getString("user_name", null)
        private set(value) { prefs.edit().putString("user_name", value).apply() }

    var username: String?
        get() = prefs.getString("username", null)
        private set(value) { prefs.edit().putString("username", value).apply() }

    var email: String?
        get() = prefs.getString("email", null)
        private set(value) { prefs.edit().putString("email", value).apply() }

    fun save(token: String, name: String, username: String, email: String?) {
        this.token = token
        userName = name
        this.username = username
        this.email = email
    }

    fun clear() { prefs.edit().clear().apply() }
}
