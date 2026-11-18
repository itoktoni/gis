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
        findViewById<TextView>(R.id.tvAppVersion).text =
            "Versi ${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})"

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
