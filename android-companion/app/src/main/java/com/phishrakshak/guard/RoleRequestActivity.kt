package com.phishrakshak.guard

import android.app.Activity
import android.app.role.RoleManager
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.provider.Settings
import android.widget.Toast

class RoleRequestActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        requestCallScreeningRole()
    }

    private fun requestCallScreeningRole() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val roleManager = getSystemService(RoleManager::class.java)
            if (roleManager.isRoleAvailable(RoleManager.ROLE_CALL_SCREENING)) {
                if (roleManager.isRoleHeld(RoleManager.ROLE_CALL_SCREENING)) {
                    Toast.makeText(this, "Call screening already enabled ✅", Toast.LENGTH_SHORT).show()
                    finish()
                    return
                }

                startActivityForResult(roleManager.createRequestRoleIntent(RoleManager.ROLE_CALL_SCREENING), 3001)
                return
            }
        }

        Toast.makeText(this, "Open Default apps and set PhishRakshak as call screening app.", Toast.LENGTH_LONG).show()
        startActivity(Intent(Settings.ACTION_MANAGE_DEFAULT_APPS_SETTINGS))
        finish()
    }

    @Deprecated("Deprecated in Android framework, still fine for this small compatibility activity.")
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        finish()
    }
}
