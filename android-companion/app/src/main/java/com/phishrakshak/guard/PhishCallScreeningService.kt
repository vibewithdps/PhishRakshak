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
            try {
                val settings = AppSettings(this)

                /*
                 * Important:
                 * Call screening ko fast response chahiye.
                 * Isliye pehle local scan use kar rahe hain.
                 * Backend scan slow hua to call screening late ho sakti hai.
                 */
                val localResult = LocalDetector.detect(content, "call")

                val shouldReject =
                    settings.callProtectionEnabled &&
                    settings.autoRejectCalls &&
                    localResult.isPhishing &&
                    localResult.confidence >= 0.85

                val shouldSilence =
                    settings.callProtectionEnabled &&
                    localResult.isPhishing &&
                    localResult.confidence >= 0.65

                if (localResult.isPhishing) {
                    NotificationHelper.show(
                        this,
                        "⚠️ Spam Call Alert",
                        "${localResult.category} • ${(localResult.confidence * 100).roundToInt()}% risk from $number"
                    )
                }

                val responseBuilder = CallResponse.Builder()

                if (shouldReject) {
                    responseBuilder
                        .setDisallowCall(true)
                        .setRejectCall(true)
                        .setSkipCallLog(false)
                        .setSkipNotification(false)
                } else if (shouldSilence && Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                    responseBuilder
                        .setSilenceCall(true)
                        .setSkipCallLog(false)
                        .setSkipNotification(false)
                } else {
                    responseBuilder
                        .setDisallowCall(false)
                        .setSkipCallLog(false)
                        .setSkipNotification(false)
                }

                respondToCall(callDetails, responseBuilder.build())

                /*
                 * Backend scan history save ke liye background call.
                 * Iska result call block decision me wait nahi karega.
                 */
                try {
                    ApiClient(this).scanCall(content)
                } catch (_: Exception) {
                    // Ignore backend failure. Local protection already handled.
                }
            } catch (_: Exception) {
                respondToCall(
                    callDetails,
                    CallResponse.Builder()
                        .setDisallowCall(false)
                        .setSkipCallLog(false)
                        .setSkipNotification(false)
                        .build()
                )
            }
        }
    }
}