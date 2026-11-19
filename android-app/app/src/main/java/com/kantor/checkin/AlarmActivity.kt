package com.kantor.checkin

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.media.AudioAttributes
import android.media.MediaPlayer
import android.media.RingtoneManager
import android.os.Build
import android.os.Bundle
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.view.WindowManager
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Layar alarm penuh seperti alarm HP: muncul di atas layar kunci,
 * bunyi alarm berulang + getar sampai satpam bertindak.
 * BUKA CHECK-IN = hentikan bunyi, buka kamera. TUNDA = bunyi lagi 10 mnt.
 */
class AlarmActivity : AppCompatActivity() {

    private var player: MediaPlayer? = null
    private var vibrator: Vibrator? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        // Tampil di atas lockscreen + nyalakan layar (seperti alarm bawaan).
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O_MR1) {
            setShowWhenLocked(true)
            setTurnScreenOn(true)
        } else {
            @Suppress("DEPRECATION")
            window.addFlags(
                WindowManager.LayoutParams.FLAG_SHOW_WHEN_LOCKED or
                    WindowManager.LayoutParams.FLAG_TURN_SCREEN_ON or
                    WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON
            )
        }
        setContentView(R.layout.activity_alarm)

        val title = intent.getStringExtra("title") ?: "Waktunya Check-In"
        val message = intent.getStringExtra("message") ?: "Segera foto check-in patroli."
        findViewById<TextView>(R.id.tvAlarmTitle).text = title
        findViewById<TextView>(R.id.tvAlarmMsg).text = message
        findViewById<TextView>(R.id.tvAlarmClock).text =
            SimpleDateFormat("HH:mm", Locale("in", "ID")).apply {
                timeZone = java.util.TimeZone.getTimeZone("Asia/Jakarta")
            }.format(Date())

        startAlarmSound()

        findViewById<Button>(R.id.btnAlarmGo).setOnClickListener {
            stopAlarmSound()
            startActivity(Intent(this, CheckinActivity::class.java))
            finish()
        }
        findViewById<Button>(R.id.btnAlarmSnooze).setOnClickListener {
            snooze(title, message)
            stopAlarmSound()
            Toast.makeText(this, "Ditunda 10 menit", Toast.LENGTH_LONG).show()
            finish()
        }
    }

    override fun onDestroy() {
        stopAlarmSound()
        super.onDestroy()
    }

    /** Bunyi alarm berulang + getar pola alarm sampai dihentikan. */
    private fun startAlarmSound() {
        try {
            val uri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
                ?: RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
            player = MediaPlayer().apply {
                setAudioAttributes(
                    AudioAttributes.Builder()
                        .setUsage(AudioAttributes.USAGE_ALARM)
                        .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                        .build()
                )
                setDataSource(this@AlarmActivity, uri)
                isLooping = true
                prepare()
                start()
            }
        } catch (_: Exception) { }
        try {
            vibrator = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                (getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as VibratorManager).defaultVibrator
            } else {
                @Suppress("DEPRECATION")
                getSystemService(Context.VIBRATOR_SERVICE) as Vibrator
            }
            val pattern = longArrayOf(0, 1000, 1000)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                vibrator?.vibrate(VibrationEffect.createWaveform(pattern, 0))
            } else {
                @Suppress("DEPRECATION")
                vibrator?.vibrate(pattern, 0)
            }
        } catch (_: Exception) { }
    }

    private fun stopAlarmSound() {
        try { player?.takeIf { it.isPlaying }?.stop() } catch (_: Exception) { }
        try { player?.release() } catch (_: Exception) { }
        player = null
        try { vibrator?.cancel() } catch (_: Exception) { }
    }

    /** Jadwalkan ulang sekali 10 menit dari sekarang. */
    private fun snooze(title: String, message: String) {
        try {
            val am = getSystemService(Context.ALARM_SERVICE) as AlarmManager
            val it = Intent(this, ReminderReceiver::class.java).apply {
                action = "com.kantor.checkin.REMIND_SNOOZE"
                putExtra("title", title)
                putExtra("message", message)
            }
            val pi = PendingIntent.getBroadcast(
                this, 999, it,
                PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
            )
            val at = System.currentTimeMillis() + 10 * 60 * 1000
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S && am.canScheduleExactAlarms()) {
                am.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, at, pi)
            } else {
                am.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, at, pi)
            }
        } catch (_: Exception) { }
    }
}
