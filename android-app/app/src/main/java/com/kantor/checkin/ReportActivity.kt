package com.kantor.checkin

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import coil.load
import com.google.gson.Gson
import kotlinx.coroutines.launch
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.text.SimpleDateFormat
import java.util.*

/**
 * Laporan monitoring: list waktu + alamat + foto + badge status.
 * Status hanya 2 label: VALID vs DI LUAR JANGKAUAN (fake dari server dipetakan ke luar jangkauan).
 */
class ReportActivity : AppCompatActivity() {

    private lateinit var adapter: ReportAdapter
    private lateinit var tvEmpty: TextView

    private fun prefs() = getSharedPreferences("cfg", MODE_PRIVATE)
    private fun token() = prefs().getString("auth_token", "") ?: ""
    private fun serverUrl(): String =
        prefs().getString("base_url", MainActivity.DEFAULT_URL)!!.trim().let {
            if (it.endsWith("/")) it else "$it/"
        }

    private fun api(): ApiService {
        val log = HttpLoggingInterceptor().setLevel(HttpLoggingInterceptor.Level.BASIC)
        val client = OkHttpClient.Builder().addInterceptor(log).build()
        return Retrofit.Builder().baseUrl(serverUrl()).client(client)
            .addConverterFactory(GsonConverterFactory.create(Gson())).build()
            .create(ApiService::class.java)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (token().isEmpty()) { finish(); return } // wajib login dulu
        setContentView(R.layout.activity_report)
        BottomNav.wire(this, BottomNav.REPORT)
        tvEmpty = findViewById(R.id.tvEmpty)

        // Default: hari ini saja
        val fmt = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        val today = Calendar.getInstance()
        findViewById<TextView>(R.id.etTo).text = fmt.format(today.time)
        findViewById<TextView>(R.id.etFrom).text = fmt.format(today.time)

        adapter = ReportAdapter(serverUrl()) { item -> showPhoto(item) }
        findViewById<RecyclerView>(R.id.rv).apply {
            layoutManager = LinearLayoutManager(this@ReportActivity)
            adapter = this@ReportActivity.adapter
        }

        findViewById<Button>(R.id.btnFilter).setOnClickListener { load() }
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.setOnRefreshListener { load() }
        findViewById<Button>(R.id.btnToday).setOnClickListener { setPresetToday(); load() }
        findViewById<Button>(R.id.btnWeek).setOnClickListener { setPresetWeek(); load() }
        findViewById<Button>(R.id.btnMonth).setOnClickListener { setPresetMonth(); load() }
        findViewById<Button>(R.id.btnPending).setOnClickListener {
            startActivity(Intent(this, PendingActivity::class.java))
        }
        load()
    }

    /** Preset rentang tanggal: hari ini / minggu ini (Senin–Minggu) / bulan ini. */
    private fun setPresetToday() {
        val s = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Calendar.getInstance().time)
        findViewById<TextView>(R.id.etFrom).text = s
        findViewById<TextView>(R.id.etTo).text = s
    }

    private fun setPresetWeek() {
        val cal = Calendar.getInstance()
        val dow = (cal.get(Calendar.DAY_OF_WEEK) + 5) % 7 // Senin = 0
        cal.add(Calendar.DAY_OF_YEAR, -dow)
        val fmt = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        findViewById<TextView>(R.id.etFrom).text = fmt.format(cal.time)
        findViewById<TextView>(R.id.etTo).text = fmt.format(Calendar.getInstance().time)
    }

    private fun setPresetMonth() {
        val cal = Calendar.getInstance()
        val fmt = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        findViewById<TextView>(R.id.etTo).text = fmt.format(cal.time)
        cal.set(Calendar.DAY_OF_MONTH, 1)
        findViewById<TextView>(R.id.etFrom).text = fmt.format(cal.time)
    }

    private fun load() {
        val from = findViewById<TextView>(R.id.etFrom).text.toString().trim().ifEmpty { null }
        val to = findViewById<TextView>(R.id.etTo).text.toString().trim().ifEmpty { null }
        lifecycleScope.launch {
            try {
                val res = api().checkins("Bearer ${token()}", from, to)
                adapter.submit(res.data)
                tvEmpty.text = if (res.data.isEmpty()) "Tidak ada check-in pada rentang ini." else ""
            } catch (e: retrofit2.HttpException) {
                if (e.code() == 401) { toast("Sesi berakhir, login ulang"); finish() }
                else toast("Gagal memuat: HTTP ${e.code()}")
            } catch (e: Exception) { if (e !is kotlinx.coroutines.CancellationException) toast("Gagal memuat: ${e.message}") }
            finally { findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.isRefreshing = false }
        }
    }

    private fun showPhoto(item: CheckinItem) {
        val url = serverUrl() + "storage/" + item.photo_path
        val view = layoutInflater.inflate(R.layout.dialog_checkin_photo, null)
        val img = view.findViewById<android.widget.ImageView>(R.id.dlgPhoto)
        img.load(url) { placeholder(android.R.drawable.ic_menu_gallery) }
        view.findViewById<TextView>(R.id.dlgAddr).text =
            item.address ?: "%.5f, %.5f".format(item.latitude, item.longitude)
        val dlg = AlertDialog.Builder(this)
            .setTitle(prettyTitle(item))
            .setView(view)
            .create()
        view.findViewById<Button>(R.id.dlgDownload).setOnClickListener { downloadPhoto(url, item) }
        view.findViewById<Button>(R.id.dlgClose).setOnClickListener { dlg.dismiss() }
        dlg.show()
    }

    private fun prettyTitle(item: CheckinItem): String = try {
        val inFmt = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
        val d = inFmt.parse(item.created_at.substring(0, 19)) ?: Date()
        SimpleDateFormat("dd MMM yyyy HH:mm", Locale("in", "ID")).format(d)
    } catch (_: Exception) { "Check-in" }

    private fun downloadPhoto(url: String, item: CheckinItem) {
        try {
            val name = "checkin_${item.id}_${System.currentTimeMillis()}.jpg"
            val req = android.app.DownloadManager.Request(android.net.Uri.parse(url))
                .setTitle(name)
                .setDescription(item.address ?: "Foto check-in")
                .setNotificationVisibility(android.app.DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                .setDestinationInExternalPublicDir(android.os.Environment.DIRECTORY_DOWNLOADS, name)
                .setAllowedOverMetered(true)
            val token = token()
            if (token.isNotEmpty()) req.addRequestHeader("Authorization", "Bearer $token")
            val dm = getSystemService(DOWNLOAD_SERVICE) as android.app.DownloadManager
            dm.enqueue(req)
            toast("Mengunduh foto...")
        } catch (e: Exception) { toast("Gagal mengunduh: ${e.message}") }
    }

    private fun toast(m: String) = Toast.makeText(this, m, Toast.LENGTH_LONG).show()
}
