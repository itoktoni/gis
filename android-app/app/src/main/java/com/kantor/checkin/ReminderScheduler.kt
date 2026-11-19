package com.kantor.checkin

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import java.util.Calendar

/**
 * Memuat config pengingat dari server (GET /api/absensi/reminder-config)
 * setiap aplikasi dibuka, lalu menjadwalkan alarm lokal harian.
 */
object ReminderScheduler {
    private const val PREF_TIMES = "reminder_times"
    const val PREF_INTERVAL = "reminder_interval_override"

    /** Interval efektif: override HP bila valid, else server. */
    fun effectiveInterval(p: android.content.SharedPreferences, server: Double): Double {
        val ov = p.getString(PREF_INTERVAL, "")?.replace(',', '.')?.toDoubleOrNull()
        return if (ov != null && ov > 0 && ov <= 24) ov else server
    }

    /** Hitung jam bunyi: start + tiap interval, maks 96. Jendela boleh
     *  lintas tengah malam (mis. start 18:00 end 06:00 → 18,21,00,03,06). */
    fun computeTimes(start: String, end: String, intervalHours: Double, max: Int): List<String> {
        fun toMin(hm: String): Int {
            val p = hm.split(":")
            return (p.getOrNull(0)?.toIntOrNull() ?: 0) * 60 + (p.getOrNull(1)?.toIntOrNull() ?: 0)
        }
        // round (bukan truncate): 0.1 jam * 60 = 6 menit tepat, bukan 5
        val step = maxOf(1, kotlin.math.round(intervalHours * 60).toInt())
        val startMin = toMin(start)
        val endMin = toMin(end)
        // Rentang memutar: bila end < start, lanjut lewat tengah malam.
        val span = if (endMin >= startMin) endMin - startMin else (24 * 60 - startMin) + endMin
        val out = mutableListOf<String>()
        var m = startMin
        var n = 0
        val cap = maxOf(1, minOf(max, 96))
        while (m - startMin <= span && n < cap) {
            val mm = m % (24 * 60)
            out.add("%02d:%02d".format(mm / 60, mm % 60))
            m += step; n++
        }
        return out
    }

    fun refresh(ctx: Context) {
        val app = ctx.applicationContext
        val p = app.getSharedPreferences("cfg", Context.MODE_PRIVATE)
        val token = p.getString("auth_token", "") ?: ""
        if (token.isEmpty()) return
        CoroutineScope(Dispatchers.IO).launch {
            try {
                val base = (p.getString("base_url", MainActivity.DEFAULT_URL) ?: MainActivity.DEFAULT_URL)
                    .trim().let { if (it.endsWith("/")) it else "$it/" }
                val api = retrofit2.Retrofit.Builder().baseUrl(base)
                    .client(okhttp3.OkHttpClient.Builder().build())
                    .addConverterFactory(retrofit2.converter.gson.GsonConverterFactory.create())
                    .build().create(ApiService::class.java)
                val cfg = api.reminderConfig("Bearer $token")
                val iv = effectiveInterval(p, cfg.interval_hours)
                // Override HP → hitung ulang lokal; kalau ikut server, pakai jam
                // dari server apa adanya (max_per_day server tetap dihormati).
                val times = if (kotlin.math.abs(iv - cfg.interval_hours) < 1e-9) cfg.times
                else computeTimes(cfg.start, cfg.end, iv, 96)
                val eff = cfg.copy(times = times)
                p.edit().putString(PREF_TIMES, times.joinToString(",")).apply()
                schedule(app, eff)
            } catch (_: Exception) {
                // Offline: pakai jadwal tersimpan terakhir
                val saved = (p.getString(PREF_TIMES, "") ?: "").split(",").filter { it.matches(Regex("\\d{2}:\\d{2}")) }
                if (saved.isNotEmpty()) schedule(app, ReminderConfig(enabled = true, times = saved, title = "Waktunya Check-In", message = "Jangan lupa check-in kehadiran hari ini."))
            }
        }
    }

    fun schedule(ctx: Context, cfg: ReminderConfig) {
        val am = ctx.getSystemService(Context.ALARM_SERVICE) as AlarmManager
        // Hapus jadwal lama dulu
        for (i in 0 until 96) am.cancel(pending(ctx, i, null, null))
        if (!cfg.enabled || cfg.times.isEmpty()) return
        val canExact = Build.VERSION.SDK_INT < Build.VERSION_CODES.S || am.canScheduleExactAlarms()
        cfg.times.take(96).forEachIndexed { i, hm ->
            val (h, m) = hm.split(":").map { it.toIntOrNull() ?: 0 }
            val cal = Calendar.getInstance().apply {
                set(Calendar.HOUR_OF_DAY, h); set(Calendar.MINUTE, m)
                set(Calendar.SECOND, 0); set(Calendar.MILLISECOND, 0)
                if (timeInMillis <= System.currentTimeMillis()) add(Calendar.DAY_OF_YEAR, 1)
            }
            val pi = pending(ctx, i, cfg.title, cfg.message)
            if (canExact) am.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, cal.timeInMillis, pi)
            else am.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, cal.timeInMillis, pi)
        }
    }

    private fun pending(ctx: Context, idx: Int, title: String?, message: String?): PendingIntent {
        val it = Intent(ctx, ReminderReceiver::class.java).apply {
            action = "com.kantor.checkin.REMIND_$idx"
            title?.let { putExtra("title", it) }
            message?.let { putExtra("message", it) }
        }
        return PendingIntent.getBroadcast(
            ctx, 100 + idx, it,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
    }
}
