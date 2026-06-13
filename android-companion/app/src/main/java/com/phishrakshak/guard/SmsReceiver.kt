package com.phishrakshak.guard

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.provider.Telephony
import java.util.concurrent.Executors

class SmsReceiver : BroadcastReceiver() {
    private val executor = Executors.newSingleThreadExecutor()

    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action != Telephony.Sms.Intents.SMS_DELIVER_ACTION) {
            return
        }

        val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
        val sender = messages.firstOrNull()?.originatingAddress ?: "Unknown"
        val body = messages.joinToString(separator = "") { it.messageBody.orEmpty() }

        if (body.isBlank()) {
            return
        }

        val pendingResult = goAsync()

        executor.execute {
            try {
                val result = ApiClient(context).scan("From: $sender\nSMS: $body", "sms")

                if (result.isPhishing) {
                    NotificationHelper.show(
                        context,
                        "⚠️ Spam SMS Detected",
                        "${result.category} • ${Math.round(result.confidence * 100)}% risk. ${result.explanation}"
                    )

                    val settings = AppSettings(context)
                    if (settings.autoDeleteSms && result.confidence >= 0.90) {
                        SmsTrashHelper.tryDeleteLatestSms(context, sender, body)
                    }
                }
            } finally {
                pendingResult.finish()
            }
        }
    }
}
