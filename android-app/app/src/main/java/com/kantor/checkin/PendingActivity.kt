package com.kantor.checkin

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.launch

/** Daftar data yang belum di-sync (antrean offline di HP). */
class PendingActivity : AppCompatActivity() {

    private lateinit var listBox: LinearLayout
    private lateinit var tvCount: TextView
    private lateinit var btnSync: Button

    private fun prefs() = getSharedPreferences("cfg", MODE_PRIVATE)

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_pending)
        BottomNav.wire(this, BottomNav.PENDING)
        listBox = findViewById(R.id.pendingList)
        tvCount = findViewById(R.id.tvPendingCount)
        btnSync = findViewById(R.id.btnSyncNow)
        btnSync.setOnClickListener { doSync() }
    }

    override fun onResume() {
        super.onResume()
        render()
    }

    private fun render() {
        listBox.removeAllViews()
        val items = SyncHelper.list(this)
        tvCount.text = if (items.isEmpty()) "Tidak ada antrean — semua sudah terkirim ✅"
        else "${items.size} data menunggu dikirim"
        btnSync.isEnabled = items.isNotEmpty()
        btnSync.alpha = if (items.isNotEmpty()) 1f else 0.5f
        for (it in items) {
            val row = TextView(this).apply {
                text = "🕒 ${it.ts}\n📍 %.6f, %.6f".format(it.lat, it.lng) +
                    (if (it.accuracy != null) " (±%.0f m)".format(it.accuracy) else "") +
                    (if (!it.address.isNullOrEmpty()) "\n${it.address}" else "")
                textSize = 13f
                setPadding(24, 20, 24, 20)
                background = android.graphics.drawable.GradientDrawable().apply {
                    shape = android.graphics.drawable.GradientDrawable.RECTANGLE
                    cornerRadius = 24f
                    setStroke(3, 0xFF2E69D2.toInt())
                    setColor(0xFFFFFFFF.toInt())
                }
            }
            val lp = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply { setMargins(0, 0, 0, 16) }
            row.layoutParams = lp
            listBox.addView(row)
        }
    }

    private fun doSync() {
        val token = prefs().getString("auth_token", "") ?: ""
        if (token.isEmpty()) { toast("Login dulu"); return }
        val cm = getSystemService(CONNECTIVITY_SERVICE) as android.net.ConnectivityManager
        val net = cm.activeNetwork ?: run { toast("Tidak ada sinyal — sync nanti"); return }
        btnSync.isEnabled = false
        btnSync.text = "Mengirim..."
        lifecycleScope.launch {
            try {
                val base = prefs().getString("base_url", MainActivity.DEFAULT_URL) ?: MainActivity.DEFAULT_URL
                val (ok, fail) = SyncHelper.syncAll(this@PendingActivity, token, base)
                render()
                toast("Sync selesai: $ok terkirim, $fail gagal")
            } catch (e: retrofit2.HttpException) {
                if (e.code() == 401) {
                    startActivity(Intent(this@PendingActivity, MainActivity::class.java)
                        .addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT))
                    toast("Sesi berakhir, login ulang")
                } else toast("Gagal: ${e.message}")
            } catch (e: SecurityException) {
                toast(e.message ?: "Sesi berakhir")
            } catch (e: Exception) {
                if (e !is kotlinx.coroutines.CancellationException) toast("Gagal: ${e.message}")
            } finally {
                render()
                btnSync.text = "🔄 SYNC SEKARANG"
            }
        }
    }

    private fun toast(m: String) = Toast.makeText(this, m, Toast.LENGTH_LONG).show()
}
