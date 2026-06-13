package com.phishrakshak.guard

import android.Manifest
import android.app.Activity
import android.app.role.RoleManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.provider.Settings
import android.provider.Telephony
import android.widget.Toast

class RoleRequestActivity : Activity() {

    companion object {
        private const val REQ_PERMISSIONS = 101
        private const val REQ_SMS_ROLE = 201
        private const val REQ_CALL_SCREENING_ROLE = 202

        fun open(context: Context) {
            val intent = Intent(context, RoleRequestActivity::class.java)
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            context.startActivity(intent)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        requestBasicPermissions()
    }

    private fun requestBasicPermissions() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            val permissions = mutableListOf(
                Manifest.permission.RECEIVE_SMS,
                Manifest.permission.READ_SMS,
                Manifest.permission.SEND_SMS,
                Manifest.permission.READ_PHONE_STATE,
                Manifest.permission.READ_CALL_LOG
            )

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                permissions.add(Manifest.permission.POST_NOTIFICATIONS)
            }

            val missing = permissions.filter {
                checkSelfPermission(it) != PackageManager.PERMISSION_GRANTED
            }

            if (missing.isNotEmpty()) {
                requestPermissions(missing.toTypedArray(), REQ_PERMISSIONS)
                return
            }
        }

        requestSmsRole()
    }

    private fun requestSmsRole() {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val roleManager = getSystemService(RoleManager::class.java)

                if (
                    roleManager != null &&
                    roleManager.isRoleAvailable(RoleManager.ROLE_SMS) &&
                    !roleManager.isRoleHeld(RoleManager.ROLE_SMS)
                ) {
                    startActivityForResult(
                        roleManager.createRequestRoleIntent(RoleManager.ROLE_SMS),
                        REQ_SMS_ROLE
                    )
                    return
                }
            } else {
                val defaultSmsPackage = Telephony.Sms.getDefaultSmsPackage(this)

                if (defaultSmsPackage != packageName) {
                    val intent = Intent(Telephony.Sms.Intents.ACTION_CHANGE_DEFAULT)
                    intent.putExtra(Telephony.Sms.Intents.EXTRA_PACKAGE_NAME, packageName)
                    startActivityForResult(intent, REQ_SMS_ROLE)
                    return
                }
            }
        } catch (_: Exception) {
            Toast.makeText(this, "Please set PhishRakshak as default SMS app manually.", Toast.LENGTH_LONG).show()
        }

        requestCallScreeningRole()
    }

    private fun requestCallScreeningRole() {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val roleManager = getSystemService(RoleManager::class.java)

                if (
                    roleManager != null &&
                    roleManager.isRoleAvailable(RoleManager.ROLE_CALL_SCREENING) &&
                    !roleManager.isRoleHeld(RoleManager.ROLE_CALL_SCREENING)
                ) {
                    startActivityForResult(
                        roleManager.createRequestRoleIntent(RoleManager.ROLE_CALL_SCREENING),
                        REQ_CALL_SCREENING_ROLE
                    )
                    return
                }
            } else {
                Toast.makeText(
                    this,
                    "Open Settings and select PhishRakshak as Caller ID / Spam app.",
                    Toast.LENGTH_LONG
                ).show()

                try {
                    startActivity(Intent(Settings.ACTION_SETTINGS))
                } catch (_: Exception) {
                    // Ignore
                }
            }
        } catch (_: Exception) {
            Toast.makeText(this, "Call protection role request failed. Enable manually in Settings.", Toast.LENGTH_LONG).show()
        }

        Toast.makeText(this, "Protection setup completed.", Toast.LENGTH_LONG).show()
        finish()
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)

        if (requestCode == REQ_PERMISSIONS) {
            requestSmsRole()
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)

        when (requestCode) {
            REQ_SMS_ROLE -> requestCallScreeningRole()
            REQ_CALL_SCREENING_ROLE -> {
                Toast.makeText(this, "Call protection setup completed.", Toast.LENGTH_LONG).show()
                finish()
            }
        }
    }
}