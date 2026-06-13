package com.phishrakshak.guard

import android.content.Context

class AppSettings(context: Context) {
    private val prefs = context.applicationContext.getSharedPreferences(
        "phishrakshak_settings",
        Context.MODE_PRIVATE
    )

    var backendUrl: String
        get() = prefs.getString(
            "backend_url",
            "https://phishrakshak-backend.onrender.com"
        ).orEmpty()
        set(value) = prefs.edit().putString("backend_url", value).apply()

    var apiToken: String
        get() = prefs.getString("api_token", "").orEmpty()
        set(value) = prefs.edit().putString("api_token", value).apply()

    var smsProtectionEnabled: Boolean
        get() = prefs.getBoolean("sms_protection_enabled", true)
        set(value) = prefs.edit().putBoolean("sms_protection_enabled", value).apply()

    var callProtectionEnabled: Boolean
        get() = prefs.getBoolean("call_protection_enabled", true)
        set(value) = prefs.edit().putBoolean("call_protection_enabled", value).apply()

    var autoRejectCalls: Boolean
        get() = prefs.getBoolean("auto_reject_calls", true)
        set(value) = prefs.edit().putBoolean("auto_reject_calls", value).apply()

    /*
     * Old code me MainActivity.kt aur SmsReceiver.kt autoDeleteSms use kar rahe hain.
     * Isliye ye property required hai.
     */
    var autoDeleteSms: Boolean
        get() = prefs.getBoolean("auto_delete_sms", false)
        set(value) = prefs.edit().putBoolean("auto_delete_sms", value).apply()

    /*
     * New name alias: agar future me autoTrashSms use karo to bhi same setting chale.
     */
    var autoTrashSms: Boolean
        get() = autoDeleteSms
        set(value) {
            autoDeleteSms = value
        }
}