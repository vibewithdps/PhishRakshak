package com.phishrakshak.guard

object LocalDetector {
    private val smsRiskWords = listOf(
        "otp", "kyc", "account blocked", "upi", "pin", "cvv", "bit.ly", "tinyurl",
        "refund", "cashback", "loan approved", "click", "verify", "password",
        "ओटीपी", "केवाईसी", "खाता बंद", "लिंक", "पिन"
    )

    private val callRiskWords = listOf(
        "unknown", "spam", "kyc", "otp", "loan", "insurance", "digital arrest",
        "trai", "sim block", "remote access", "anydesk", "teamviewer"
    )

    fun detect(content: String, type: String): ProtectionResult {
        val lower = content.lowercase()
        val words = if (type == "call") callRiskWords else smsRiskWords
        val score = words.count { lower.contains(it) }

        return when {
            score >= 3 -> ProtectionResult(
                isPhishing = true,
                confidence = 0.92,
                category = if (type == "call") "Spam Call / Vishing Scam" else "SMS Phishing Scam",
                explanation = "High-risk local scam keywords found."
            )
            score >= 1 -> ProtectionResult(
                isPhishing = true,
                confidence = 0.76,
                category = if (type == "call") "Suspicious Call" else "Suspicious SMS",
                explanation = "Some suspicious words found. Verify before taking action."
            )
            else -> ProtectionResult.safe()
        }
    }
}
