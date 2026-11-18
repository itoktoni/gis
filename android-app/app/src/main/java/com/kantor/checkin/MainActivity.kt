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
        const val DEFAULT_URL = "https://apnkalteng2.web.id/"
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
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardSync).setOnClickListener {
            startActivity(Intent(this, PendingActivity::class.java))
        }
        findViewById<com.google.android.material.card.MaterialCardView>(R.id.cardServer).setOnClickListener {
            startActivity(Intent(this, SettingActivity::class.java))
        }
        setupDashboard()
        BottomNav.wire(this, BottomNav.HOME)
        refreshSessionUI()
    }

    /** Jumlah antrean offline terenkripsi. */
    private fun pendingSyncCount(): Int = SyncHelper.count(this)

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
                val res = api().login(email, pass)
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
            } catch (e: Exception) { if (e !is kotlinx.coroutines.CancellationException) toast("Login gagal: ${e.message}") }
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
        findViewById<android.view.View>(R.id.dashCard).visibility =
            if (loggedIn) android.view.View.VISIBLE else android.view.View.GONE
        findViewById<android.view.View>(R.id.bottomNav).visibility =
            if (loggedIn) android.view.View.VISIBLE else android.view.View.GONE
        val n = if (loggedIn) pendingSyncCount() else 0
        findViewById<android.view.View>(R.id.cardSync).visibility =
            if (n > 0) android.view.View.VISIBLE else android.view.View.GONE
        if (n > 0) findViewById<TextView>(R.id.tvSyncLabel).text = "Sync ($n)"
        if (!loggedIn) tvInfo.text = ""
        else tvInfo.text = ""
        if (loggedIn) loadDashboard()
    }

    // ---------- Dashboard widget ----------
    private var dashFrom: String? = null
    private var dashTo: String? = null
    private var dashLabel = "HARI"

    private fun todayStr(): String =
        java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US).format(java.util.Calendar.getInstance().time)

    private fun setupDashboard() {
        if (dashFrom == null) { dashFrom = todayStr(); dashTo = todayStr() }
        markChip(R.id.chipToday)
        findViewById<Button>(R.id.chipToday).setOnClickListener { setDashRange(todayStr(), todayStr(), "HARI", R.id.chipToday) }
        findViewById<Button>(R.id.chipWeek).setOnClickListener {
            val cal = java.util.Calendar.getInstance()
            val dow = (cal.get(java.util.Calendar.DAY_OF_WEEK) + 5) % 7
            cal.add(java.util.Calendar.DAY_OF_YEAR, -dow)
            val fmt = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US)
            setDashRange(fmt.format(cal.time), todayStr(), "MGGU", R.id.chipWeek)
        }
        findViewById<Button>(R.id.chipMonth).setOnClickListener {
            val cal = java.util.Calendar.getInstance()
            cal.set(java.util.Calendar.DAY_OF_MONTH, 1)
            val fmt = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US)
            setDashRange(fmt.format(cal.time), todayStr(), "BLN", R.id.chipMonth)
        }
        findViewById<Button>(R.id.chipYear).setOnClickListener {
            val cal = java.util.Calendar.getInstance()
            cal.set(java.util.Calendar.DAY_OF_YEAR, 1)
            val fmt = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US)
            setDashRange(fmt.format(cal.time), todayStr(), "THN", R.id.chipYear)
        }
        findViewById<Button>(R.id.chipRange).setOnClickListener { showRangeDialog() }
    }

    private fun setDashRange(from: String, to: String, label: String, chipId: Int = R.id.chipToday) {
        dashFrom = from; dashTo = to; dashLabel = label
        markChip(chipId)
        loadDashboard()
    }

    /** Tandai chip filter yang aktif (biru), lainnya putih. */
    private fun markChip(selectedId: Int) {
        val primary = androidx.core.content.ContextCompat.getColor(this, R.color.apn_primary)
        val ids = listOf(R.id.chipToday, R.id.chipWeek, R.id.chipMonth, R.id.chipYear, R.id.chipRange)
        ids.forEach { id ->
            val b = findViewById<com.google.android.material.button.MaterialButton>(id)
            if (id == selectedId) {
                b.backgroundTintList = android.content.res.ColorStateList.valueOf(primary)
                b.setTextColor(android.graphics.Color.WHITE)
                b.strokeColor = android.content.res.ColorStateList.valueOf(primary)
            } else {
                b.backgroundTintList = android.content.res.ColorStateList.valueOf(android.graphics.Color.TRANSPARENT)
                b.setTextColor(primary)
                b.strokeColor = android.content.res.ColorStateList.valueOf(primary)
                b.strokeWidth = (1 * resources.displayMetrics.density).toInt()
            }
        }
    }

    private fun showRangeDialog() {
        val etFrom = EditText(this).apply { hint = "Dari (yyyy-mm-dd)"; setText(dashFrom ?: ""); setSingleLine() }
        val etTo = EditText(this).apply { hint = "Sampai (yyyy-mm-dd)"; setText(dashTo ?: ""); setSingleLine() }
        val layout = android.widget.LinearLayout(this).apply {
            orientation = android.widget.LinearLayout.VERTICAL
            setPadding(48, 24, 48, 0)
            addView(etFrom); addView(etTo)
        }
        AlertDialog.Builder(this)
            .setTitle("Rentang Tanggal")
            .setView(layout)
            .setPositiveButton("Tampilkan") { _, _ ->
                val f = etFrom.text.toString().trim()
                val t = etTo.text.toString().trim()
                if (f.isNotEmpty() && t.isNotEmpty()) setDashRange(f, t, "$f s.d. $t", R.id.chipRange)
                else toast("Isi kedua tanggal")
            }
            .setNegativeButton("Batal", null)
            .show()
    }

    private fun loadDashboard() {
        val p = getSharedPreferences("cfg", MODE_PRIVATE)
        val token = p.getString("auth_token", "") ?: ""
        if (token.isEmpty()) return
        if (dashFrom == null) { dashFrom = todayStr(); dashTo = todayStr() }
        findViewById<TextView>(R.id.tvDashPeriod).text = dashLabel
        lifecycleScope.launch {
            try {
                val res = api().stats("Bearer $token", dashFrom, dashTo)
                findViewById<TextView>(R.id.tvStatValid).text = res.valid.toString()
                findViewById<TextView>(R.id.tvStatInvalid).text = res.invalid.toString()
            } catch (e: Exception) {
                if (e !is kotlinx.coroutines.CancellationException) toast("Dashboard gagal: ${e.message}")
            }
            try {
                val visit = api().visitPoints("Bearer $token", todayStr())
                findViewById<TextView>(R.id.tvStatVisit).text = visit.data.size.toString()
            } catch (_: Exception) { }
        }
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
