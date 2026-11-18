package com.kantor.checkin

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.launch

/** Halaman Setting: form alamat server + tes koneksi + simpan. */
class SettingActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_setting)
        BottomNav.wire(this, BottomNav.SETTING)

        val p = getSharedPreferences("cfg", MODE_PRIVATE)
        val etServer = findViewById<EditText>(R.id.etServer)
        val tvResult = findViewById<TextView>(R.id.tvResult)
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.setOnRefreshListener {
            etServer.setText(p.getString("base_url", MainActivity.DEFAULT_URL) ?: MainActivity.DEFAULT_URL)
            tvResult.text = ""
            findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.isRefreshing = false
        }
        findViewById<TextView>(R.id.tvAppVersion).text =
            "Versi ${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})"

        val etReminder = findViewById<EditText>(R.id.etReminderInterval)
        val tvReminderInfo = findViewById<TextView>(R.id.tvReminderInfo)
        etReminder.setText(p.getString(ReminderScheduler.PREF_INTERVAL, "") ?: "")
        fun refreshReminderInfo() {
            val token = p.getString("auth_token", "") ?: ""
            lifecycleScope.launch {
                try {
                    val base = (p.getString("base_url", MainActivity.DEFAULT_URL) ?: MainActivity.DEFAULT_URL)
                        .trim().let { if (it.endsWith("/")) it else "$it/" }
                    val api = retrofit2.Retrofit.Builder().baseUrl(base)
                        .client(okhttp3.OkHttpClient.Builder().build())
                        .addConverterFactory(retrofit2.converter.gson.GsonConverterFactory.create())
                        .build().create(ApiService::class.java)
                    val cfg = api.reminderConfig("Bearer $token")
                    val iv = ReminderScheduler.effectiveInterval(p, cfg.interval_hours)
                    val times = ReminderScheduler.computeTimes(cfg.start, cfg.end, iv, 48)
                    tvReminderInfo.text = "Server: tiap ${cfg.interval_hours} jam\nHP ini: tiap $iv jam (${times.size}x: ${times.take(6).joinToString(", ")}${if (times.size > 6) "..." else ""})"
                } catch (_: Exception) {
                    tvReminderInfo.text = "Tidak bisa memuat config server (offline)"
                }
            }
        }
        refreshReminderInfo()
        findViewById<Button>(R.id.btnSaveReminder).setOnClickListener {
            val v = etReminder.text.toString().trim().replace(',', '.')
            val d = v.toDoubleOrNull()
            if (v.isEmpty()) {
                p.edit().remove(ReminderScheduler.PREF_INTERVAL).apply()
                Toast.makeText(this, "Override dihapus — ikut server", Toast.LENGTH_LONG).show()
            } else if (d == null || d <= 0 || d > 24) {
                Toast.makeText(this, "Isi angka 0 < jam ≤ 24 (cth: 0.2)", Toast.LENGTH_LONG).show()
                return@setOnClickListener
            } else {
                p.edit().putString(ReminderScheduler.PREF_INTERVAL, v).apply()
                Toast.makeText(this, "Interval disimpan: $v jam", Toast.LENGTH_LONG).show()
            }
            refreshReminderInfo()
            ReminderScheduler.refresh(this)
        }
        findViewById<Button>(R.id.btnLogoutSetting).setOnClickListener {
            p.edit().remove("auth_token").remove("checkin_secret")
                .remove("user_id").remove("user_email").remove("user_role").apply()
            Toast.makeText(this, "Logout", Toast.LENGTH_LONG).show()
            startActivity(Intent(this, MainActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP))
            finish()
        }
        etServer.setText(p.getString("base_url", MainActivity.DEFAULT_URL) ?: MainActivity.DEFAULT_URL)

        findViewById<Button>(R.id.btnTest).setOnClickListener {
            lifecycleScope.launch {
                tvResult.text = "Mengetes..."
                try {
                    tvResult.text = if (testServer(etServer.text.toString())) "✅ Server terjangkau" else "❌ Tidak terjangkau"
                } catch (e: Exception) {
                    tvResult.text = "❌ ${e.message}"
                }
            }
        }

        findViewById<Button>(R.id.btnSaveServer).setOnClickListener {
            var u = etServer.text.toString().trim().trimEnd('/')
            if (!u.startsWith("http")) u = "http://$u"
            p.edit().putString("base_url", "$u/").remove("test_mode").apply()
            // Token lama tidak berlaku untuk server baru
            p.edit().remove("auth_token").remove("checkin_secret")
                .remove("user_id").remove("user_email").remove("user_role").apply()
            Toast.makeText(this, "Server disimpan: $u/ — silakan login ulang", Toast.LENGTH_LONG).show()
            finish()
        }

        updateNotifStatus()
        findViewById<Button>(R.id.btnTestNotif).setOnClickListener {
            sendBroadcast(Intent(this, ReminderReceiver::class.java).apply {
                action = "com.kantor.checkin.REMIND_TEST"
                putExtra("title", "Tes Notifikasi ✅")
                putExtra("message", "Notifikasi HP ini AKTIF. Pengingat check-in akan masuk sesuai jadwal.")
            })
            Toast.makeText(this, "Notifikasi tes dikirim — cek bilah notifikasi", Toast.LENGTH_LONG).show()
        }
    }

    override fun onResume() {
        super.onResume()
        updateNotifStatus()
    }

    /** Status izin notifikasi + alarm tepat waktu; arahkan ke pengaturan bila mati. */
    private fun updateNotifStatus() {
        val tv = findViewById<TextView>(R.id.tvNotifStatus)
        val nm = getSystemService(NOTIFICATION_SERVICE) as android.app.NotificationManager
        val notifOn = nm.areNotificationsEnabled()
        val am = getSystemService(ALARM_SERVICE) as android.app.AlarmManager
        val exactOk = android.os.Build.VERSION.SDK_INT < android.os.Build.VERSION_CODES.S || am.canScheduleExactAlarms()
        tv.text = when {
            !notifOn -> "❌ Notifikasi MATI — pengingat tidak akan masuk. Buka Pengaturan > Aplikasi > APN Monitoring > Notifikasi, lalu nyalakan."
            !exactOk -> "⚠️ Alarm tepat waktu dibatasi — pengingat bisa terlambat. Izinkan di Pengaturan > Aplikasi > Alarm & pengingat."
            else -> "✅ Notifikasi AKTIF, alarm tepat waktu diizinkan."
        }
        tv.setOnClickListener {
            if (!notifOn) {
                try {
                    startActivity(Intent(android.provider.Settings.ACTION_APP_NOTIFICATION_SETTINGS).apply {
                        putExtra(android.provider.Settings.EXTRA_APP_PACKAGE, packageName)
                    })
                } catch (_: Exception) { }
            }
        }
    }

    /** Ping ringan: GET /api/signatures/stats, 401/200 = server hidup. */
    private suspend fun testServer(raw: String): Boolean {
        var u = raw.trim().trimEnd('/')
        if (!u.startsWith("http")) u = "http://$u"
        return try {
            val c = java.net.URL("$u/api/absensi/reminder-config").openConnection() as java.net.HttpURLConnection
            c.connectTimeout = 4000; c.readTimeout = 4000
            c.responseCode == 401 || c.responseCode == 200
        } catch (_: Exception) { false }
    }
}
