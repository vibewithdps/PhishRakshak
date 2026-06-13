package com.phishrakshak.guard

data class ProtectionResult(
    val isPhishing: Boolean,
    val confidence: Double,
    val category: String,
    val explanation: String
) {
    companion object {
        fun safe(): ProtectionResult = ProtectionResult(
            isPhishing = false,
            confidence = 0.20,
            category = "Safe",
            explanation = "No high-risk pattern found locally."
        )
    }
}
