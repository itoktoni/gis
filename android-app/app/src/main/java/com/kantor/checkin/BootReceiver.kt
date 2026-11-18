package com.kantor.checkin

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

/** Jadwalkan ulang pengingat setelah HP restart (alarm tidak bertahan). */
class BootReceiver : BroadcastReceiver() {
    override fun onReceive(ctx: Context, intent: Intent) {
        if (intent.action == Intent.ACTION_BOOT_COMPLETED) ReminderScheduler.refresh(ctx)
    }
}
