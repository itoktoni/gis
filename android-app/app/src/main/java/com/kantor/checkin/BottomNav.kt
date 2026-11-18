package com.kantor.checkin

import android.content.Intent
import android.view.View
import androidx.appcompat.app.AppCompatActivity

/** Bottom bar bersama di semua halaman (tetap tampil saat pindah halaman). */
object BottomNav {
    const val HOME = 1
    const val CHECKIN = 2
    const val VISIT = 3
    const val REPORT = 4
    const val SETTING = 5
    const val PENDING = 6

    fun wire(a: AppCompatActivity, current: Int) {
        val bar = a.findViewById<View>(R.id.bottomNav) ?: return
        bar.visibility = View.VISIBLE
        fun go(id: Int, page: Int, intent: () -> Intent) {
            a.findViewById<View>(id)?.setOnClickListener {
                if (current == page) return@setOnClickListener
                a.startActivity(intent().addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT))
            }
        }
        go(R.id.navHome, HOME) { Intent(a, MainActivity::class.java) }
        go(R.id.navCheckin, CHECKIN) { Intent(a, CheckinActivity::class.java) }
        go(R.id.navVisit, VISIT) { Intent(a, VisitActivity::class.java) }
        go(R.id.navReport, REPORT) { Intent(a, ReportActivity::class.java) }
        go(R.id.navSetting, SETTING) { Intent(a, SettingActivity::class.java) }
        // Tandai halaman aktif: ikon + label jadi biru tebal, lainnya abu-abu.
        // Tanpa lingkaran/background — cukup warna.
        val primary = androidx.core.content.ContextCompat.getColor(a, R.color.apn_primary)
        val gray = 0xFF8A94A6.toInt()
        mapOf(R.id.navHome to HOME, R.id.navCheckin to CHECKIN, R.id.navVisit to VISIT,
            R.id.navReport to REPORT, R.id.navSetting to SETTING
        ).forEach { (id, page) ->
            val item = a.findViewById<android.view.View>(id) ?: return@forEach
            val active = page == current
            item.alpha = 1f
            (item as? android.view.ViewGroup)?.let { vg ->
                for (i in 0 until vg.childCount) {
                    when (val v = vg.getChildAt(i)) {
                        is android.widget.ImageView -> v.imageTintList =
                            android.content.res.ColorStateList.valueOf(if (active) primary else gray)
                        is android.widget.TextView -> {
                            v.setTextColor(if (active) primary else gray)
                            v.setTypeface(null, if (active) android.graphics.Typeface.BOLD else android.graphics.Typeface.NORMAL)
                        }
                    }
                }
            }
        }
    }
}
