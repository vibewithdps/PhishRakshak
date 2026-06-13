package com.phishrakshak.guard

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

class MmsReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        NotificationHelper.show(
            context,
            "PhishRakshak MMS Notice",
            "MMS received. Open the message only if sender is trusted."
        )
    }
}
