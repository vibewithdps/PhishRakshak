package com.phishrakshak.guard

import android.app.Activity
import android.os.Bundle
import android.widget.TextView

class ComposeSmsActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(TextView(this).apply {
            text = "PhishRakshak Guard is focused on protection. Use your trusted messaging app to compose SMS."
            textSize = 18f
            setPadding(32, 32, 32, 32)
        })
    }
}
