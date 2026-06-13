package com.phishrakshak.guard

import android.content.Context
import android.net.Uri

object SmsTrashHelper {
    fun tryDeleteLatestSms(context: Context, sender: String, body: String): Boolean {
        return try {
            val uri = Uri.parse("content://sms/inbox")
            val cursor = context.contentResolver.query(
                uri,
                arrayOf("_id", "address", "body"),
                null,
                null,
                "date DESC LIMIT 10"
            ) ?: return false

            cursor.use {
                while (it.moveToNext()) {
                    val id = it.getLong(0)
                    val address = it.getString(1).orEmpty()
                    val smsBody = it.getString(2).orEmpty()

                    if (address.contains(sender.takeLast(8)) || smsBody.take(40) == body.take(40)) {
                        val deleted = context.contentResolver.delete(Uri.parse("content://sms/$id"), null, null)
                        return deleted > 0
                    }
                }
            }

            false
        } catch (_: Exception) {
            false
        }
    }
}
