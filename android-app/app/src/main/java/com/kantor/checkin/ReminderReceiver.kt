package com.kantor.checkin

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import androidx.core.app.NotificationCompat

/** Pengingat check-in ala alarm HP: notifikasi prioritas tinggi + full-screen
 *  (muncul menutupi layar / layar kunci) + suara alarm + getar. */
class ReminderReceiver : BroadcastReceiver() {
    override fun onReceive(ctx: Context, intent: Intent) {
        val title = intent.getStringExtra("title") ?: "Waktunya Check-In"
        val message = intent.getStringExtra("message") ?: "Jangan lupa check-in kehadiran hari ini."
        val nm = ctx.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        val sound = android.media.RingtoneManager.getDefaultUri(android.media.RingtoneManager.TYPE_ALARM)
            ?: android.media.RingtoneManager.getDefaultUri(android.media.RingtoneManager.TYPE_NOTIFICATION)
        val channel = android.app.NotificationChannel(
            CHANNEL_ID, "Alarm Check-In",
            NotificationManager.IMPORTANCE_HIGH
        ).apply {
            setSound(sound, android.media.AudioAttributes.Builder()
                .setUsage(android.media.AudioAttributes.USAGE_ALARM).build())
            enableVibration(true)
            vibrationPattern = longArrayOf(0, 1000, 1000)
            lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
        }
        nm.createNotificationChannel(channel)
        // Layar alarm penuh; ketuk notifikasi biasa juga membuka layar alarm.
        val full = PendingIntent.getActivity(
            ctx, 0, Intent(ctx, AlarmActivity::class.java).apply {
                putExtra("title", title)
                putExtra("message", message)
            },
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        val notif = NotificationCompat.Builder(ctx, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_menu_my_calendar)
            .setContentTitle("⏰ $title")
            .setContentText(message)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setContentIntent(full)
            .setFullScreenIntent(full, true)
            .setCategory(NotificationCompat.CATEGORY_ALARM)
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setAutoCancel(true)
            .build()
        nm.notify(NOTIF_ID, notif)
    }

    companion object {
        const val CHANNEL_ID = "checkin_alarm"
        const val NOTIF_ID = 1001
    }
}
