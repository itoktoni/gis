package com.kantor.checkin

import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.gson.Gson
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.logging.HttpLoggingInterceptor
import org.json.JSONObject
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import android.content.Intent
import android.widget.EditText
import androidx.appcompat.app.AppCompatDelegate

/**
 * Layar 1: Login + Dashboard.
 * Belum login -> form login. Sudah login -> tombol BUKA CHECK-IN (kamera ada di layar 2).
 */
class MainActivity : AppCompatActivity() {

    private lateinit var tvInfo: TextView

    companion object {
        const val DEFAULT_URL = "http://192.168.1.44:8000/"
    }

    /** URL server dari Setting (tersimpan di HP), bukan hardcode. */
    fun serverUrl(): String =
        getSharedPreferences("cfg", MODE_PRIVATE)
            .getString("base_url", DEFAULT_URL)!!.trim().let {
                if (it.endsWith("/")) it else "$it/"
            }

    fun api(): ApiService {
        val log = HttpLoggingInterceptor().setLevel(HttpLoggingInterceptor.Level.BASIC)
        val client = OkHttpClient.Builder().addInterceptor(log).build()
        return Retrofit.Builder().baseUrl(serverUrl()).client(client)
            .addConverterFactory(GsonConverterFactory.create(Gson())).build()
            .create(ApiService::class.java)
    }

    /** Dialog Setting URL server + tombol Tes koneksi. */
    private fun showServerDialog() {
        val p = getSharedPreferences("cfg", MODE_PRIVATE)
        val input = EditText(this).apply {
            setText(serverUrl())
            hint = "http://192.168.1.x:8080/"
            setSingleLine()
        }
        val layout = android.widget.LinearLayout(this).apply {
            orientation = android.widget.LinearLayout.VERTICAL
            setPadding(48, 24, 48, 0)
            addView(input)
        }
        AlertDialog.Builder(this)
            .setTitle("Server URL")
            .setMessage("Alamat server Laravel (satu WiFi dengan HP).")
            .setView(layout)
            .setPositiveButton("Simpan") { _, _ ->
                var u = input.text.toString().trim().trimEnd('/')
                if (!u.startsWith("http")) u = "http://$u"
                p.edit()
                    .putString("base_url", "$u/")
                    .remove("test_mode")
                    .apply()
                doLogout(silent = true)
                toast("Server: $u/ — silakan login ulang")
            }
            .setNeutralButton("Tes") { _, _ ->
                lifecycleScope.launch {
                    try {
                        val ok = withContextTest(input.text.toString())
                        toast(if (ok) "Server terjangkau" else "Tidak terjangkau")
                    } catch (e: Exception) { toast("Tidak terjangkau: ${e.message}") }
                }
            }
            .setNegativeButton("Batal", null)
            .show()
    }

    /** Ping ringan: GET /api/signatures/stats, 401 = server hidup. */
    private suspend fun withContextTest(raw: String): Boolean {
        var u = raw.trim().trimEnd('/')
        if (!u.startsWith("http")) u = "http://$u"
        return try {
            val c = java.net.URL("$u/api/signatures/stats").openConnection() as java.net.HttpURLConnection
            c.connectTimeout = 4000; c.readTimeout = 4000
            c.responseCode == 401 || c.responseCode == 200
        } catch (_: Exception) { false }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        // Kunci light mode saja (tanpa dark mode)
        AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_NO)
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        tvInfo = findViewById(R.id.tvInfo)

        findViewById<Button>(R.id.btnLogin).setOnClickListener { doLogin() }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardCheckin).setOnClickListener {
            startActivity(Intent(this, CheckinActivity::class.java))
        }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardReport).setOnClickListener {
            startActivity(Intent(this, ReportActivity::class.java))
        }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardVisit).setOnClickListener {
            startActivity(Intent(this, VisitActivity::class.java))
        }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardSync).setOnClickListener { doSync() }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardServer).setOnClickListener { showServerDialog() }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardLogout).setOnClickListener { doLogout() }
        findViewById<com.google.android.material.floatingactionbutton.FloatingActionButton>(R.id.fabCheckin).setOnClickListener {
            startActivity(Intent(this, CheckinActivity::class.java))
        }
        findViewById<com.google.android.material.bottomappbar.BottomAppBar>(R.id.bottomBar)
            .setOnMenuItemClickListener { item ->
                when (item.itemId) {
                    R.id.nav_visit -> startActivity(Intent(this, VisitActivity::class.java))
                    R.id.nav_report -> startActivity(Intent(this, ReportActivity::class.java))
                    R.id.nav_setting -> showServerDialog()
                    else -> return@setOnMenuItemClickListener true
                }
                true
            }
        refreshSessionUI()
    }

    /** Kirim semua antrean offline terenkripsi ke server. */
    private fun doSync() {
        val p = getSharedPreferences("cfg", MODE_PRIVATE)
        val token = p.getString("auth_token", "") ?: ""
        if (token.isEmpty()) { toast("Login dulu"); return }
        if (!isOnlineNow()) { toast("Tidak ada sinyal — sync nanti"); return }
        val dir = java.io.File(filesDir, "pending")
        val metas = dir.listFiles { f -> f.name.endsWith(".meta.bin") } ?: emptyArray()
        if (metas.isEmpty()) { toast("Tidak ada antrean offline"); return }
        lifecycleScope.launch {
            var ok = 0
            var fail = 0
            for (m in metas) {
                try {
                    val id = m.name.removeSuffix(".meta.bin")
                    val meta = org.json.JSONObject(PendingCrypto.decryptText(m.readBytes()))
                    val jpg = java.io.File(dir, "$id.jpg.bin")
                    if (!jpg.exists()) { m.delete(); continue }
                    // Dekrip ke file temp untuk upload
                    val tmp = java.io.File.createTempFile("sync_$id", ".jpg", cacheDir)
                    tmp.writeBytes(PendingCrypto.decryptBytes(jpg.readBytes()))
                    fun s(v: String) = v.toRequestBody("text/plain".toMediaType())
                    val part = okhttp3.MultipartBody.Part.createFormData(
                        "photo", "selfie.jpg",
                        tmp.asRequestBody("image/jpeg".toMediaType()))
                    val acc = if (meta.isNull("accuracy")) null
                    else s(meta.getDouble("accuracy").toString())
                    api().checkin(
                        "Bearer $token", part,
                        s(meta.getDouble("lat").toString()),
                        s(meta.getDouble("lng").toString()),
                        acc,
                        if (meta.isNull("address")) null else s(meta.getString("address")),
                        s(meta.getString("ts")),
                        s(meta.getString("payload")),
                        s(meta.getString("hmac"))
                    )
                    tmp.delete(); jpg.delete(); m.delete()
                    ok++
                } catch (e: retrofit2.HttpException) {
                    if (e.code() == 401) {
                        doLogout(); toast("Sesi berakhir, login ulang"); return@launch
                    }
                    fail++
                } catch (e: Exception) { fail++ }
            }
            refreshSessionUI()
            toast("Sync selesai: $ok terkirim, $fail gagal")
        }
    }

    /** Jumlah antrean offline terenkripsi. */
    private fun pendingSyncCount(): Int =
        java.io.File(filesDir, "pending").listFiles { f -> f.name.endsWith(".meta.bin") }?.size ?: 0

    private fun isOnlineNow(): Boolean = try {        val cm = getSystemService(android.content.Context.CONNECTIVITY_SERVICE) as android.net.ConnectivityManager
        val net = cm.activeNetwork ?: return false
        val caps = cm.getNetworkCapabilities(net) ?: return false
        caps.hasTransport(android.net.NetworkCapabilities.TRANSPORT_WIFI) ||
            caps.hasTransport(android.net.NetworkCapabilities.TRANSPORT_CELLULAR) ||
            caps.hasTransport(android.net.NetworkCapabilities.TRANSPORT_ETHERNET)
    } catch (_: Exception) { false }

    override fun onResume() {
        super.onResume()
        refreshSessionUI() // update badge SYNC (n) sepulang dari kamera
        askNotifPermission()
        // Muat config pengingat dari server setiap aplikasi dibuka
        ReminderScheduler.refresh(this)
    }

    /** Izin notifikasi (Android 13+), sekali saja. */
    private fun askNotifPermission() {
        if (android.os.Build.VERSION.SDK_INT >= 33 &&
            androidx.core.content.ContextCompat.checkSelfPermission(
                this, android.Manifest.permission.POST_NOTIFICATIONS
            ) != android.content.pm.PackageManager.PERMISSION_GRANTED
        ) {
            androidx.core.app.ActivityCompat.requestPermissions(
                this, arrayOf(android.Manifest.permission.POST_NOTIFICATIONS), 101
            )
        }
    }

    private fun doLogin() {
        val email = findViewById<TextView>(R.id.etEmail).text.toString().trim()
        val pass = findViewById<TextView>(R.id.etPass).text.toString().trim()
        if (email.isEmpty() || pass.isEmpty()) { toast("Isi email dan password"); return }
        lifecycleScope.launch {
            try {
                val res = api().login(LoginReq(email, pass))
                val uid = res.user?.id ?: 0
                if (uid == 0) { toast("Login gagal: respons server tidak valid"); return@launch }
                // Sanctum: token opaque + ambil secret HMAC khusus absensi
                val sec = api().secret("Bearer ${res.access_token}")
                getSharedPreferences("cfg", MODE_PRIVATE).edit()
                    .putString("auth_token", res.access_token)
                    .putString("checkin_secret", sec.checkin_secret)
                    .putInt("user_id", uid)
                    .putString("user_email", email)
                    .putString("user_role", res.user?.role ?: "")
                    .apply()
                refreshSessionUI()
                // Langsung muat config pengingat setelah login
                ReminderScheduler.refresh(this@MainActivity)
                toast("Login OK — tekan BUKA CHECK-IN")
            } catch (e: retrofit2.HttpException) {
                toast("Login gagal (HTTP ${e.code()}): email/password salah atau server menolak")
            } catch (e: java.net.UnknownHostException) {
                toast("Login gagal: server ${serverUrl()} tidak terjangkau — cek Setting Server & WiFi")
            } catch (e: Exception) { toast("Login gagal: ${e.message}") }
        }
    }

    /** Tampilkan status login; grid menu + bottom nav hanya bila sudah login. */
    private fun refreshSessionUI() {
        val p = getSharedPreferences("cfg", MODE_PRIVATE)
        val loggedIn = !p.getString("auth_token", "").isNullOrEmpty() && p.getInt("user_id", 0) != 0
        val email = p.getString("user_email", "")
        findViewById<TextView>(R.id.tvUser).text =
            if (loggedIn) "Login sebagai: $email" else "Belum login"
        findViewById<android.view.View>(R.id.loginCard).visibility =
            if (loggedIn) android.view.View.GONE else android.view.View.VISIBLE
        findViewById<android.view.View>(R.id.menuGrid).visibility =
            if (loggedIn) android.view.View.VISIBLE else android.view.View.GONE
        findViewById<android.view.View>(R.id.bottomNav).visibility =
            if (loggedIn) android.view.View.VISIBLE else android.view.View.GONE
        val n = if (loggedIn) pendingSyncCount() else 0
        findViewById<android.view.View>(R.id.cardSync).visibility =
            if (n > 0) android.view.View.VISIBLE else android.view.View.GONE
        if (n > 0) findViewById<TextView>(R.id.tvSyncLabel).text = "Sync ($n)"
        if (!loggedIn) tvInfo.text = "Server: ${serverUrl()}"
        else tvInfo.text = ""
    }

    private fun doLogout(silent: Boolean = false) {
        getSharedPreferences("cfg", MODE_PRIVATE).edit()
            .remove("auth_token").remove("checkin_secret")
            .remove("user_id").remove("user_email").remove("user_role").apply()
        refreshSessionUI()
        if (!silent) toast("Logout")
    }

    private fun toast(m: String) = Toast.makeText(this, m, Toast.LENGTH_LONG).show()
}
