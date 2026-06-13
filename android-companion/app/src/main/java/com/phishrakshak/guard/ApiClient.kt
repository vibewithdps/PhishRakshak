package com.phishrakshak.guard

import android.content.Context
import org.json.JSONObject
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL

class ApiClient(context: Context) {
    private val appContext = context.applicationContext
    private val settings = AppSettings(appContext)

    fun login(email: String, password: String): String? {
        return try {
            val url = URL(settings.backendUrl.trimEnd('/') + "/api/login")
            val connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = 5000
                readTimeout = 5000
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

            if (connection.responseCode !in 200..299) {
                return null
            }

            val responseText = connection.inputStream.bufferedReader().use { it.readText() }
            JSONObject(responseText).optString("token").takeIf { it.isNotBlank() }
        } catch (_: Exception) {
            null
        }
    }

    fun scan(content: String, type: String): ProtectionResult {
        val token = settings.apiToken

        if (token.isBlank()) {
            return LocalDetector.detect(content, type)
        }

        return try {
            val url = URL(settings.backendUrl.trimEnd('/') + "/api/scan")
            val connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                connectTimeout = 4500
                readTimeout = 4500
                doOutput = true
                setRequestProperty("Accept", "application/json")
                setRequestProperty("Content-Type", "application/json")
                setRequestProperty("Authorization", "Bearer $token")
            }

            val body = JSONObject()
                .put("type", type)
                .put("content", content)
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

            if (connection.responseCode !in 200..299) {
                return LocalDetector.detect(content, type)
            }

            val data = JSONObject(responseText).getJSONObject("data")

            ProtectionResult(
                isPhishing = data.optBoolean("is_phishing", false),
                confidence = data.optDouble("confidence", 0.2),
                category = data.optString("category", "Safe"),
                explanation = data.optString("explanation", "Scan completed.")
            )
        } catch (_: Exception) {
            LocalDetector.detect(content, type)
        }
    }
}
