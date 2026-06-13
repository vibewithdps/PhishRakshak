package com.phishrakshak.guard

import android.Manifest
import android.app.Activity
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.provider.Telephony
import android.view.Gravity
import android.widget.Button
import android.widget.CheckBox
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast

class MainActivity : Activity() {
    private lateinit var settings: AppSettings
    private lateinit var backendUrlInput: EditText
    private lateinit var tokenInput: EditText
    private lateinit var emailInput: EditText
    private lateinit var passwordInput: EditText
    private lateinit var callCheck: CheckBox
    private lateinit var smsDeleteCheck: CheckBox

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        settings = AppSettings(this)
        requestNotificationPermissionIfNeeded()
        NotificationHelper.ensureChannel(this)
        buildUi()
    }

    private fun buildUi() {
        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(34, 36, 34, 36)
        }

        val title = TextView(this).apply {
            text = "PhishRakshak Guard"
            textSize = 28f
            gravity = Gravity.CENTER
        }
        root.addView(title)

        val subtitle = TextView(this).apply {
            text = "Native companion for SMS and call protection. PWA handles dashboard + Gmail."
            textSize = 15f
            setPadding(0, 16, 0, 22)
            gravity = Gravity.CENTER
        }
        root.addView(subtitle)

        backendUrlInput = EditText(this).apply {
            hint = "Backend URL, example: http://192.168.1.5:8000"
            setText(settings.backendUrl)
        }
        root.addView(backendUrlInput)

        emailInput = EditText(this).apply {
            hint = "Login email"
        }
        root.addView(emailInput)

        passwordInput = EditText(this).apply {
            hint = "Login password"
        }
        root.addView(passwordInput)

        val loginButton = Button(this).apply {
            text = "Login and Save API Token"
            setOnClickListener {
                settings.backendUrl = backendUrlInput.text.toString()
                Thread {
                    val token = ApiClient(this@MainActivity).login(
                        emailInput.text.toString(),
                        passwordInput.text.toString()
                    )
                    runOnUiThread {
                        if (token.isNullOrBlank()) {
                            Toast.makeText(this@MainActivity, "Login failed. Check backend URL/email/password.", Toast.LENGTH_LONG).show()
                        } else {
                            settings.apiToken = token
                            tokenInput.setText(token)
                            Toast.makeText(this@MainActivity, "Login token saved ✅", Toast.LENGTH_SHORT).show()
                        }
                    }
                }.start()
            }
        }
        root.addView(loginButton)

        tokenInput = EditText(this).apply {
            hint = "Laravel API token from login response"
            setText(settings.apiToken)
            setSingleLine(false)
            minLines = 2
        }
        root.addView(tokenInput)

        callCheck = CheckBox(this).apply {
            text = "Auto reject high-risk spam calls"
            isChecked = settings.autoRejectCalls
        }
        root.addView(callCheck)

        smsDeleteCheck = CheckBox(this).apply {
            text = "Optional: delete/quarantine high-risk SMS when default SMS role is active"
            isChecked = settings.autoDeleteSms
        }
        root.addView(smsDeleteCheck)

        val saveButton = Button(this).apply {
            text = "Save Settings"
            setOnClickListener {
                settings.backendUrl = backendUrlInput.text.toString()
                settings.apiToken = tokenInput.text.toString()
                settings.autoRejectCalls = callCheck.isChecked
                settings.autoDeleteSms = smsDeleteCheck.isChecked
                Toast.makeText(this@MainActivity, "Saved ✅", Toast.LENGTH_SHORT).show()
            }
        }
        root.addView(saveButton)

        val smsRoleButton = Button(this).apply {
            text = "Make Default SMS App"
            setOnClickListener { requestDefaultSmsRole() }
        }
        root.addView(smsRoleButton)

        val callRoleButton = Button(this).apply {
            text = "Enable Call Screening"
            setOnClickListener {
                startActivity(Intent(this@MainActivity, RoleRequestActivity::class.java))
            }
        }
        root.addView(callRoleButton)

        val testButton = Button(this).apply {
            text = "Test Local Notification"
            setOnClickListener {
                NotificationHelper.show(
                    this@MainActivity,
                    "PhishRakshak Guard Active",
                    "SMS and call protection companion is ready."
                )
            }
        }
        root.addView(testButton)

        val note = TextView(this).apply {
            text = "Important: Android allows SMS trash/control only when this app is selected as default SMS app. Calls require Call Screening role."
            textSize = 14f
            setPadding(0, 24, 0, 0)
        }
        root.addView(note)

        setContentView(ScrollView(this).apply { addView(root) })
    }

    private fun requestDefaultSmsRole() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.KITKAT) {
            val currentDefault = Telephony.Sms.getDefaultSmsPackage(this)
            if (currentDefault == packageName) {
                Toast.makeText(this, "Already default SMS app ✅", Toast.LENGTH_SHORT).show()
                return
            }

            val intent = Intent(Telephony.Sms.Intents.ACTION_CHANGE_DEFAULT).apply {
                putExtra(Telephony.Sms.Intents.EXTRA_PACKAGE_NAME, packageName)
            }
            startActivity(intent)
        }
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= 33 && checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(arrayOf(Manifest.permission.POST_NOTIFICATIONS), 2001)
        }
    }
}
