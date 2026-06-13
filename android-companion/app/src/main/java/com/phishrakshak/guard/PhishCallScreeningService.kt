package com.phishrakshak.guard

import android.os.Build
import android.telecom.Call
import android.telecom.CallScreeningService
import java.util.concurrent.Executors
import kotlin.math.roundToInt

class PhishCallScreeningService : CallScreeningService() {
    private val executor = Executors.newSingleThreadExecutor()

    override fun onScreenCall(callDetails: Call.Details) {
        val number = callDetails.handle?.schemeSpecificPart ?: "Unknown"
        val content = "Incoming call from $number. Unknown caller spam call risk check."

        executor.execute {
            val result = ApiClient(this).scan(content, "call")
            val settings = AppSettings(this)
            val shouldReject = settings.autoRejectCalls && result.isPhishing && result.confidence >= 0.85

            if (result.isPhishing) {
                NotificationHelper.show(
                    this,
                    "⚠️ Spam Call Alert",
                    "${result.category} • ${(result.confidence * 100).roundToInt()}% risk from $number"
                )
            }

            val responseBuilder = CallResponse.Builder()

            if (shouldReject) {
                responseBuilder
                    .setDisallowCall(true)
                    .setRejectCall(true)
                    .setSkipCallLog(false)
                    .setSkipNotification(false)
            } else if (result.isPhishing && Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                responseBuilder
                    .setSilenceCall(true)
                    .setSkipCallLog(false)
                    .setSkipNotification(false)
            }

            respondToCall(callDetails, responseBuilder.build())
        }
    }
}
