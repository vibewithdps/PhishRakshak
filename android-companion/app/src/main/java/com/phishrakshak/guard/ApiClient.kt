package com.phishrakshak.guard

import android.content.Context
import org.json.JSONObject
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL

class ApiClient(context: Context) {
    private val appContext = context.applicationContext
    private val settings = AppSettings(appContext)

    companion object {
        private const val LIVE_BACKEND_URL = "https://phishrakshak-backend.onrender.com"
        private const val CONNECT_TIMEOUT = 10000
        private const val READ_TIMEOUT = 10000
    }

    private fun backendBaseUrl(): String {
        val savedUrl = settings.backendUrl.trim().trimEnd('/')

        if (
            savedUrl.isBlank() ||
            savedUrl.contains("localhost", ignoreCase = true) ||
            savedUrl.contains("127.0.0.1") ||
            savedUrl.contains("10.0.2.2")
        ) {
            return LIVE_BACKEND_URL
        }

        return savedUrl
    }

    private fun apiUrl(path: String): URL {
        val cleanPath = path.trimStart('/')
        return URL("${backendBaseUrl()}/api/$cleanPath")
    }

    fun login(email: String, password: String): String? {
        return try {
            val connection = (apiUrl("login").openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = CONNECT_TIMEOUT
                readTimeout = READ_TIMEOUT
                doOutput = true
                setRequestProperty("Accept", "application/json")
                setRequestProperty("Content-Type", "application/json")
            }

            val body = JSONObject()
                .put("email", email.trim())
                .put("password", password)
                .toString()

            OutputStreamWriter(connection.outputStream).use { writer ->
                writer.write(body)
                writer.flush()
            }

            val responseText = if (connection.responseCode in 200..299) {
                connection.inputStream.bufferedReader().use { it.readText() }
            } else {
                connection.errorStream?.bufferedReader()?.use { it.readText() }.orEmpty()
            }

            if (connection.responseCode !in 200..299 || responseText.isBlank()) {
                return null
            }

            val json = JSONObject(responseText)

            val tokenFromRoot = json.optString("token", "")
            if (tokenFromRoot.isNotBlank()) {
                settings.apiToken = tokenFromRoot
                return tokenFromRoot
            }

            val accessTokenFromRoot = json.optString("access_token", "")
            if (accessTokenFromRoot.isNotBlank()) {
                settings.apiToken = accessTokenFromRoot
                return accessTokenFromRoot
            }

            val data = json.optJSONObject("data")
            val tokenFromData = data?.optString("token", "").orEmpty()
            if (tokenFromData.isNotBlank()) {
                settings.apiToken = tokenFromData
                return tokenFromData
            }

            val accessTokenFromData = data?.optString("access_token", "").orEmpty()
            if (accessTokenFromData.isNotBlank()) {
                settings.apiToken = accessTokenFromData
                return accessTokenFromData
            }

            null
        } catch (_: Exception) {
            null
        }
    }

    fun scan(content: String, type: String): ProtectionResult {
        val cleanContent = content.trim()
        val cleanType = normalizeType(type)

        if (cleanContent.isBlank()) {
            return ProtectionResult(
                isPhishing = false,
                confidence = 0.0,
                category = "Empty Content",
                explanation = "No content found for scanning."
            )
        }

        val token = settings.apiToken.trim()

        if (token.isBlank()) {
            return LocalDetector.detect(cleanContent, cleanType)
        }

        return try {
            val connection = (apiUrl("scan").openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = CONNECT_TIMEOUT
                readTimeout = READ_TIMEOUT
                doOutput = true
                setRequestProperty("Accept", "application/json")
                setRequestProperty("Content-Type", "application/json")
                setRequestProperty("Authorization", "Bearer $token")
            }

            val body = JSONObject()
                .put("type", cleanType)
                .put("content", cleanContent)
                .toString()

            OutputStreamWriter(connection.outputStream).use { writer ->
                writer.write(body)
                writer.flush()
            }

            val responseText = if (connection.responseCode in 200..299) {
                connection.inputStream.bufferedReader().use { it.readText() }
            } else {
                connection.errorStream?.bufferedReader()?.use { it.readText() }.orEmpty()
            }

            if (connection.responseCode !in 200..299 || responseText.isBlank()) {
                return LocalDetector.detect(cleanContent, cleanType)
            }

            val json = JSONObject(responseText)
            val data = json.optJSONObject("data") ?: json

            ProtectionResult(
                isPhishing = data.optBoolean("is_phishing", false),
                confidence = data.optDouble("confidence", 0.2),
                category = data.optString("category", "Safe"),
                explanation = data.optString("explanation", "Scan completed.")
            )
        } catch (_: Exception) {
            LocalDetector.detect(cleanContent, cleanType)
        }
    }

    fun scanSms(message: String): ProtectionResult {
        return scan(message, "sms")
    }

    fun scanEmail(emailContent: String): ProtectionResult {
        return scan(emailContent, "email")
    }

    fun scanCall(numberOrNote: String): ProtectionResult {
        return scan(numberOrNote, "call")
    }

    fun logout() {
        settings.apiToken = ""
    }

    private fun normalizeType(type: String): String {
        return when (type.trim().lowercase()) {
            "mail" -> "email"
            "gmail" -> "email"
            "sms_message" -> "sms"
            "website" -> "url"
            "website_url" -> "url"
            "apk_detail" -> "apk"
            "spam_call" -> "call"
            "phone" -> "call"
            "number" -> "call"
            else -> type.trim().lowercase()
        }
    }
}