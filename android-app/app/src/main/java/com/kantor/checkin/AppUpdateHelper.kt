package com.kantor.checkin

import android.app.DownloadManager
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.FileProvider
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.io.File
import java.net.HttpURLConnection
import java.net.URL

/** Update otomatis tanpa Play Store: cek /api/app/version -> download APK -> install. */
object AppUpdateHelper {

    data class Ver(val code: Int, val name: String, val apkUrl: String, val changelog: String, val force: Boolean)

    fun baseUrl(ctx: Context): String {
        val b = ctx.getSharedPreferences("cfg", Context.MODE_PRIVATE)
            .getString("base_url", MainActivity.DEFAULT_URL)!!.trim()
        return if (b.endsWith("/")) b else "$b/"
    }

    /** Dipanggil dari tombol "Cek Update" (manual=true) atau saat buka app (manual=false). */
    fun check(activity: AppCompatActivity, manual: Boolean = true) {
        activity.lifecycleScope.launch {
            try {
                val v = fetch(baseUrl(activity))
                val cur = activity.packageManager.getPackageInfo(activity.packageName, 0).let {
                    if (Build.VERSION.SDK_INT >= 28) it.longVersionCode.toInt() else @Suppress("DEPRECATION") it.versionCode
                }
                if (v.code <= cur) {
                    if (manual) Toast.makeText(activity, "Sudah versi terbaru (${BuildConfig.VERSION_NAME})", Toast.LENGTH_LONG).show()
                    return@launch
                }
                AlertDialog.Builder(activity)
                    .setTitle("Update v${v.name} tersedia")
                    .setMessage(v.changelog.ifBlank { "Versi baru tersedia, update sekarang?" })
                    .setCancelable(!v.force)
                    .setPositiveButton("UPDATE") { _, _ -> download(activity, v) }
                    .apply { if (!v.force) setNegativeButton("Nanti", null) }
                    .show()
            } catch (e: Exception) {
                if (manual) Toast.makeText(activity, "Cek update gagal: ${e.message}", Toast.LENGTH_LONG).show()
            }
        }
    }

    private suspend fun fetch(base: String): Ver = withContext(Dispatchers.IO) {
        val c = (URL("${base}api/app/version").openConnection() as HttpURLConnection).apply {
            connectTimeout = 8000; readTimeout = 8000
        }
        if (c.responseCode != 200) throw Exception("server HTTP ${c.responseCode}")
        val j = JSONObject(c.inputStream.bufferedReader().readText())
        Ver(j.getInt("version_code"), j.optString("version_name", ""),
            j.getString("apk_url"), j.optString("changelog", ""), j.optBoolean("force", false))
    }

    private fun download(activity: AppCompatActivity, v: Ver) {
        // Android 8+: wajib izin "install unknown apps"
        if (Build.VERSION.SDK_INT >= 26 && !activity.packageManager.canRequestPackageInstalls()) {
            Toast.makeText(activity, "Izinkan 'Install unknown apps' dulu", Toast.LENGTH_LONG).show()
            try {
                activity.startActivity(Intent(android.provider.Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES,
                    Uri.parse("package:${activity.packageName}")))
            } catch (_: Exception) { }
            return
        }
        Toast.makeText(activity, "Mengunduh update...", Toast.LENGTH_SHORT).show()
        val file = File(activity.getExternalFilesDir(Environment.DIRECTORY_DOWNLOADS), "apn-update.apk")
        if (file.exists()) file.delete()
        val req = DownloadManager.Request(Uri.parse(v.apkUrl)).apply {
            setTitle("APN Monitoring v${v.name}")
            setDestinationUri(Uri.fromFile(file))
            setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
            setMimeType("application/vnd.android.package-archive")
        }
        val dm = activity.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
        val id = dm.enqueue(req)
        val receiver = object : BroadcastReceiver() {
            override fun onReceive(c: Context, i: Intent) {
                if (i.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1) != id) return
                try { activity.unregisterReceiver(this) } catch (_: Exception) { }
                install(activity, file)
            }
        }
        activity.registerReceiver(receiver, IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE),
            Context.RECEIVER_NOT_EXPORTED)
    }

    private fun install(activity: AppCompatActivity, file: File) {
        try {
            val uri = FileProvider.getUriForFile(activity, "${activity.packageName}.provider", file)
            activity.startActivity(Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, "application/vnd.android.package-archive")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
            })
        } catch (e: Exception) {
            Toast.makeText(activity, "Gagal install: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }
}
