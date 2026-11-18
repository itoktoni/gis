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
        // Tandai halaman aktif (redupkan yang lain)
        mapOf(R.id.navHome to HOME, R.id.navCheckin to CHECKIN, R.id.navVisit to VISIT,
            R.id.navReport to REPORT, R.id.navSetting to SETTING
        ).forEach { (id, page) ->
            a.findViewById<View>(id)?.alpha = if (page == current) 1f else 0.55f
        }
    }
}
