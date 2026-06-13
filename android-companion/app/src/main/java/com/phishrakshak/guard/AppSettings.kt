package com.phishrakshak.guard

import android.content.Context

class AppSettings(context: Context) {
    private val prefs = context.getSharedPreferences("phishrakshak_guard", Context.MODE_PRIVATE)

    var backendUrl: String
        get() = prefs.getString("backend_url", "http://10.0.2.2:8000") ?: "http://10.0.2.2:8000"
        set(value) = prefs.edit().putString("backend_url", value.trim().trimEnd('/')).apply()

    var apiToken: String
        get() = prefs.getString("api_token", "") ?: ""
        set(value) = prefs.edit().putString("api_token", value.trim()).apply()

    var autoRejectCalls: Boolean
        get() = prefs.getBoolean("auto_reject_calls", true)
        set(value) = prefs.edit().putBoolean("auto_reject_calls", value).apply()

    var autoDeleteSms: Boolean
        get() = prefs.getBoolean("auto_delete_sms", false)
        set(value) = prefs.edit().putBoolean("auto_delete_sms", value).apply()
}
