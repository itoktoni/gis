package com.kantor.checkin

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Geocoder
import android.location.LocationManager
import android.os.Build
import android.os.Bundle
import android.provider.Settings
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageCapture
import androidx.camera.core.ImageCaptureException
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.gson.Gson
import kotlinx.coroutines.async
import kotlinx.coroutines.launch
import kotlinx.coroutines.suspendCancellableCoroutine
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.logging.HttpLoggingInterceptor
import org.json.JSONObject
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.io.File
import java.text.SimpleDateFormat
import java.util.*
import kotlin.coroutines.resume
import kotlin.coroutines.resumeWithException

/**
 * Layar 2 (setelah login): kamera + stempel lokasi/waktu + tombol CAPTURE & KIRIM.
 * 1 tombol: foto + HMAC-sign(payload) -> server verifikasi -> simpan.
 */
class CheckinActivity : AppCompatActivity() {

    private lateinit var preview: PreviewView
    private lateinit var tvStamp: TextView
    private lateinit var tvStatus: TextView
    private lateinit var tvWarn: TextView
    private lateinit var btnDevSettings: Button
    private var imageCapture: ImageCapture? = null
    private var lensFacing = CameraSelector.LENS_FACING_BACK
    private var lastLoc: android.location.Location? = null
    private var lastAddr: String? = null
    private var lastAddrAt: Long = 0

    // Metadata GNSS (sinyal RF asli, tidak ikut dipalsukan mock app)
    private var satCount: Int? = null
    private var satUsedInFix: Int? = null
    private var meanCn0: Double? = null
    private var gnssCallback: android.location.GnssStatus.Callback? = null

    companion object { const val REQ_PERMS = 100 }

    private fun prefs() = getSharedPreferences("cfg", MODE_PRIVATE)
    private fun token() = prefs().getString("auth_token", "") ?: ""
    private fun userId() = prefs().getInt("user_id", 0)
    private fun isPrivileged() =
        prefs().getString("user_role", "") in setOf("admin", "developer")
    private fun secret() = prefs().getString("checkin_secret", "") ?: ""
    private fun serverUrl(): String =
        prefs().getString("base_url", MainActivity.DEFAULT_URL)!!.trim().let {
            if (it.endsWith("/")) it else "$it/"
        }
    private fun isTestMode() = false // testing dinonaktifkan permanen

    private fun api(): ApiService {
        val log = HttpLoggingInterceptor().setLevel(HttpLoggingInterceptor.Level.BASIC)
        val client = OkHttpClient.Builder().addInterceptor(log).build()
        return Retrofit.Builder().baseUrl(serverUrl()).client(client)
            .addConverterFactory(GsonConverterFactory.create(Gson())).build()
            .create(ApiService::class.java)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (token().isEmpty() || userId() == 0) { finish(); return } // wajib login dulu
        setContentView(R.layout.activity_checkin)
        BottomNav.wire(this, BottomNav.CHECKIN)
        preview = findViewById(R.id.preview)
        tvStamp = findViewById(R.id.tvStamp)
        tvStatus = findViewById(R.id.tvStatus)
        tvWarn = findViewById(R.id.tvWarn)
        btnDevSettings = findViewById(R.id.btnDevSettings)
        btnDevSettings.setOnClickListener {
            try { startActivity(Intent(Settings.ACTION_APPLICATION_DEVELOPMENT_SETTINGS)) }
            catch (_: Exception) { startActivity(Intent(Settings.ACTION_SETTINGS)) }
        }

        ActivityCompat.requestPermissions(
            this,
            arrayOf(Manifest.permission.CAMERA, Manifest.permission.ACCESS_FINE_LOCATION),
            REQ_PERMS
        )
        if (hasCameraPermission()) startCameraSafe()
        if (hasLocationPermission()) {
            startLocationUpdates()
            startGnssMonitoring()
        }
        stampHandler.post(stampTick)

        findViewById<Button>(R.id.btnFlip).setOnClickListener {
            lensFacing = if (lensFacing == CameraSelector.LENS_FACING_FRONT)
                CameraSelector.LENS_FACING_BACK else CameraSelector.LENS_FACING_FRONT
            startCameraSafe()
        }
        findViewById<Button>(R.id.btnLoc).setOnClickListener { doRelocate() }
        findViewById<Button>(R.id.btnCapture).setOnClickListener { doCaptureAndSend() }
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.setOnRefreshListener {
            updateStamp()
            val lt = lightThreat()
            refreshCaptureButton(lt)
            if (!isPrivileged()) showWarn(lt?.let { "$it.\nCheck-in dikunci sampai mock dimatikan." })
            findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.isRefreshing = false
        }
        // Label titik kunjungan bila dibuka dari daftar titik
        intent.getStringExtra("kunjungan_name")?.takeIf { it.isNotEmpty() }?.let {
            tvStatus.text = "📍 Titik: $it"
        }
    }

    override fun onResume() {
        super.onResume()
        stampHandler.removeCallbacks(stampTick)
        stampHandler.post(stampTick)
        if (orientationListener.canDetectOrientation()) orientationListener.enable()
        val threat = mockLocationThreat()
        val devOn = isDevModeOn()
        // Tanpa popup: tampilkan label info + kunci tombol (tombol back tetap bebas)
        if (!isPrivileged()) {
            val reason = threat ?: if (devOn) "Developer options aktif" else null
            refreshCaptureButton(reason)
            showWarn(
                threat?.let { "$it.\nCheck-in dikunci sampai mock dimatikan." }
                    ?: if (devOn) "Developer options aktif.\nCheck-in dikunci sampai dimatikan." else null
            )
        } else {
            refreshCaptureButton(null)
            showWarn(threat?.let { "Mode admin: $it (tetap boleh kirim, tercatat PALSU)" })
        }
        btnDevSettings.visibility = if (devOn) android.view.View.VISIBLE else android.view.View.GONE
    }

    override fun onPause() {
        super.onPause()
        stopGnssMonitoring()
        orientationListener.disable()
        stampHandler.removeCallbacks(stampTick)
    }

    // ---------- Kamera ----------

    private fun startCamera() {
        val provider = ProcessCameraProvider.getInstance(this).get()
        val rotation = preview.display?.rotation ?: android.view.Surface.ROTATION_0
        val prev = Preview.Builder().setTargetRotation(rotation).build()
            .also { it.setSurfaceProvider(preview.surfaceProvider) }
        imageCapture = ImageCapture.Builder()
            .setTargetRotation(rotation)
            .setCaptureMode(ImageCapture.CAPTURE_MODE_MINIMIZE_LATENCY).build()
        provider.unbindAll()
        val wanted = if (lensFacing == CameraSelector.LENS_FACING_FRONT)
            CameraSelector.DEFAULT_FRONT_CAMERA else CameraSelector.DEFAULT_BACK_CAMERA
        val selector = try {
            provider.bindToLifecycle(this, wanted, prev, imageCapture)
            return
        } catch (_: Exception) {
            if (wanted == CameraSelector.DEFAULT_FRONT_CAMERA)
                CameraSelector.DEFAULT_BACK_CAMERA else CameraSelector.DEFAULT_FRONT_CAMERA
        }
        provider.bindToLifecycle(this, selector, prev, imageCapture)
    }

    /** Ikuti putaran HP supaya foto belakang tidak selalu landscape. */
    private val orientationListener by lazy {
        object : android.view.OrientationEventListener(this) {
            override fun onOrientationChanged(degrees: Int) {
                if (degrees == ORIENTATION_UNKNOWN) return
                val rotation = when (degrees) {
                    in 45 until 135 -> android.view.Surface.ROTATION_270
                    in 135 until 225 -> android.view.Surface.ROTATION_180
                    in 225 until 315 -> android.view.Surface.ROTATION_90
                    else -> android.view.Surface.ROTATION_0
                }
                imageCapture?.targetRotation = rotation
            }
        }
    }

    /** Pantau satelit GNSS asli (jumlah, usedInFix, rata-rata C/N0). */
    private fun startGnssMonitoring() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.R || !hasLocationPermission()) return
        val lm = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        val cb = object : android.location.GnssStatus.Callback() {
            override fun onSatelliteStatusChanged(status: android.location.GnssStatus) {
                var used = 0
                var sumCn0 = 0.0
                for (i in 0 until status.satelliteCount) {
                    if (status.usedInFix(i)) used++
                    sumCn0 += status.getCn0DbHz(i)
                }
                satCount = status.satelliteCount
                satUsedInFix = used
                meanCn0 = if (status.satelliteCount > 0) sumCn0 / status.satelliteCount else null
            }
        }
        gnssCallback = cb
        try {
            lm.registerGnssStatusCallback(ContextCompat.getMainExecutor(this), cb)
        } catch (_: SecurityException) { }
    }

    private fun stopGnssMonitoring() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.R) return
        try {
            val lm = getSystemService(Context.LOCATION_SERVICE) as LocationManager
            gnssCallback?.let { lm.unregisterGnssStatusCallback(it) }
        } catch (_: Exception) { }
    }

    private fun startCameraSafe() {
        try { startCamera() }
        catch (e: SecurityException) { toast("Izin kamera belum diberikan") }
        catch (e: Exception) { toast("Kamera tidak bisa dibuka: ${e.message}") }
    }

    private fun hasCameraPermission() =
        ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) ==
            PackageManager.PERMISSION_GRANTED

    private fun hasLocationPermission() =
        ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED

    override fun onRequestPermissionsResult(
        requestCode: Int, permissions: Array<out String>, grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == REQ_PERMS) {
            if (hasCameraPermission()) startCameraSafe()
            if (hasLocationPermission()) startLocationUpdates()
        }
    }

    // ---------- Stempel lokasi/waktu live ----------

    private val stampHandler = android.os.Handler(android.os.Looper.getMainLooper())
    private val stampTick = object : Runnable {
        override fun run() {
            updateStamp()
            stampHandler.postDelayed(this, 1000)
        }
    }

    private fun updateStamp() {
        val wib = java.util.TimeZone.getTimeZone("Asia/Jakarta")
        val time = SimpleDateFormat("dd MMM yyyy HH:mm:ss", Locale("in", "ID")).apply { timeZone = wib }.format(Date())
        val loc = lastLoc
        tvStamp.text = if (loc == null) "$time\nMencari GPS..."
        else "$time\n${lastAddr ?: "-"}\n%.6f, %.6f".format(loc.latitude, loc.longitude)
        // Banner overlay WYSIWYG di preview — sama persis dengan yang ditempel ke foto.
        try {
            findViewById<TextView>(R.id.tvPrevDate)?.text = time
            findViewById<TextView>(R.id.tvPrevAddr)?.text = lastAddr ?: "-"
            findViewById<TextView>(R.id.tvPrevCoord)?.text =
                if (loc == null) "Mencari GPS..."
                else "%.6f, %.6f".format(loc.latitude, loc.longitude) +
                    if (loc.hasAccuracy()) " (±%.0f m)".format(loc.accuracy) else ""
        } catch (_: Exception) { }
        // Kunci tombol kirim selama ancaman mock/developer terdeteksi (cek ringan tiap detik)
        val lt = lightThreat()
        refreshCaptureButton(lt)
        if (!isPrivileged()) showWarn(lt?.let { "$it.\nCheck-in dikunci sampai mock dimatikan." })
        if (btnDevSettings.visibility != android.view.View.VISIBLE && isDevModeOn()) {
            btnDevSettings.visibility = android.view.View.VISIBLE
        } else if (btnDevSettings.visibility == android.view.View.VISIBLE && !isDevModeOn()) {
            btnDevSettings.visibility = android.view.View.GONE
        }
    }

    /** Status kunci tombol capture terakhir, agar toast hanya sekali saat transisi. */
    private var lastBlocked: Boolean? = null

    private fun refreshCaptureButton(reason: String?) {
        val b = findViewById<Button>(R.id.btnCapture) ?: return
        b.isEnabled = reason == null
        b.alpha = if (reason == null) 1f else 0.4f
        if (reason != null && lastBlocked != true) toast("Check-in dikunci: $reason")
        lastBlocked = reason != null
    }

    /** Cek ringan (tanpa scan paket) untuk dipanggil tiap detik. Admin dilewati. */
    private fun lightThreat(): String? {
        if (isPrivileged()) return null
        try {
            val m = Settings.Secure.getString(contentResolver, "mock_location")
            if (!m.isNullOrEmpty() && m != "0") return "Aplikasi mock location aktif"
        } catch (_: Exception) { }
        if (isDevModeOn()) return "Developer options aktif"
        lastLoc?.let { if (isMockLocation(it)) return "Lokasi PALSU (mock provider)" }
        return null
    }

    private fun startLocationUpdates() {
        if (!hasLocationPermission()) return
        val client = LocationServices.getFusedLocationProviderClient(this)
        try {
            client.getCurrentLocation(Priority.PRIORITY_HIGH_ACCURACY, null)
                .addOnSuccessListener { l ->
                    if (l != null) { lastLoc = l; lastAddrAt = 0; updateStamp() }
                }
        } catch (_: SecurityException) { }
        val req = com.google.android.gms.location.LocationRequest
            .Builder(Priority.PRIORITY_HIGH_ACCURACY, 5000).build()
        try {
            client.requestLocationUpdates(
                req, ContextCompat.getMainExecutor(this),
                object : com.google.android.gms.location.LocationCallback() {
                    override fun onLocationResult(r: com.google.android.gms.location.LocationResult) {
                        val l = r.lastLocation ?: return
                        if (isMockLocation(l) && !isPrivileged()) {
                            showWarn("Lokasi terdeteksi PALSU (mock provider).\nCheck-in dikunci sampai mock dimatikan.")
                            return
                        }
                        lastLoc = l
                        if (System.currentTimeMillis() - lastAddrAt > 30000 || lastAddr == null) {
                            lastAddrAt = System.currentTimeMillis()
                            lifecycleScope.launch(kotlinx.coroutines.Dispatchers.IO) {
                                lastAddr = try {
                                    Geocoder(this@CheckinActivity, Locale.getDefault())
                                        .getFromLocation(l.latitude, l.longitude, 1)
                                        ?.firstOrNull()?.getAddressLine(0)
                                } catch (_: Exception) { null }
                            }
                        }
                        updateStamp()
                    }
                })
        } catch (_: SecurityException) { }
    }

    private fun doRelocate() {
        val lm = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        val gpsOn = try {
            lm.isProviderEnabled(LocationManager.GPS_PROVIDER) ||
                lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
        } catch (_: Exception) { false }
        if (!gpsOn) {
            AlertDialog.Builder(this)
                .setTitle("Lokasi mati")
                .setMessage("GPS/Location HP belum aktif. Nyalakan dulu supaya koordinat akurat.")
                .setPositiveButton("Buka Pengaturan") { _, _ ->
                    startActivity(Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS))
                }
                .setNegativeButton("Nanti", null)
                .show()
            return
        }
        if (!hasLocationPermission()) {
            ActivityCompat.requestPermissions(
                this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), REQ_PERMS)
            toast("Berikan izin lokasi dulu")
            return
        }
        tvStamp.text = "Mencari lokasi baru..."
        lifecycleScope.launch {
            try {
                val fresh = currentLocation()
                if (fresh == null) {
                    toast("GPS null — coba ke area terbuka"); updateStamp(); return@launch
                }
                lastLoc = fresh
                lastAddr = try {
                    Geocoder(this@CheckinActivity, Locale.getDefault())
                        .getFromLocation(fresh.latitude, fresh.longitude, 1)
                        ?.firstOrNull()?.getAddressLine(0)
                } catch (_: Exception) { null }
                lastAddrAt = System.currentTimeMillis()
                updateStamp()
                val acc = if (fresh.hasAccuracy()) "±%.0f m".format(fresh.accuracy) else "?"
                toast("Lokasi diperbarui $acc")
            } catch (e: Exception) { if (e !is kotlinx.coroutines.CancellationException) toast("Relokasi gagal: ${e.message}"); updateStamp() }
        }
    }

    // ---------- Capture + kirim ----------

    private fun doCaptureAndSend() {
        lifecycleScope.launch {
            try {
                val privileged = isPrivileged()
                if (!privileged) {
                    mockLocationThreat()?.let {
                        showWarn("$it.\nCheck-in dikunci sampai mock dimatikan.")
                        refreshCaptureButton(it); tvStatus.text = ""; return@launch
                    }
                }
                val online = isOnline()
                // Sampling sensor + lookup IP paralel dengan foto/lokasi (hemat ~4-8 detik)
                val motionDeferred = lifecycleScope.async(kotlinx.coroutines.Dispatchers.Default) { sampleMotion() }
                val ipDeferred = lifecycleScope.async(kotlinx.coroutines.Dispatchers.IO) { fetchIpGeo(online) }
                tvStatus.text = "Memotret..."
                val rawPhoto = takePhoto()
                tvStatus.text = "Menyiapkan foto..."
                val downsized = downscaleIfNeeded(rawPhoto)
                tvStatus.text = "Mengambil lokasi..."
                val loc = currentLocation() ?: lastLoc
                    ?: run { toast("GPS null, nyalakan lokasi"); tvStatus.text = ""; return@launch }
                if (!privileged && isMockLocation(loc)) {
                    showWarn("Lokasi terdeteksi PALSU (mock provider).\nCheck-in dikunci sampai mock dimatikan.")
                    refreshCaptureButton("Lokasi PALSU (mock provider)")
                    tvStatus.text = ""
                    return@launch
                }
                val addr = lastAddr ?: try {
                    Geocoder(this@CheckinActivity, Locale.getDefault())
                        .getFromLocation(loc.latitude, loc.longitude, 1)
                        ?.firstOrNull()?.getAddressLine(0)
                } catch (_: Exception) { null }

                tvStatus.text = "Mengirim..."
                val ts = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US).format(Date())
                val mock = isMockLocation(loc)
                // Lokasi independen: NETWORK_PROVIDER (menara/WiFi), umumnya tak ikut dipalsukan.
                // Abaikan bila basi (>5 menit) — lastKnown bisa cache kota lain dan menyesatkan.
                val netLoc: android.location.Location? = lastKnownLoc(LocationManager.NETWORK_PROVIDER)?.takeIf {
                    (android.os.SystemClock.elapsedRealtimeNanos() - it.elapsedRealtimeNanos) / 1_000_000_000 <= 300
                }
                // GPS mentah (GPS_PROVIDER, bukan fused): sering masih asli walau fused di-mock.
                // Dipakai server sebagai "dugaan lokasi asli" saat mock terdeteksi.
                val gpsRaw: android.location.Location? = lastKnownLoc(LocationManager.GPS_PROVIDER)?.takeIf {
                    (android.os.SystemClock.elapsedRealtimeNanos() - it.elapsedRealtimeNanos) / 1_000_000_000 <= 300
                }
                // Geolokasi IP diambil paralel di awal (ipDeferred); tinggal tunggu hasilnya
                val ipResult = try { ipDeferred.await() }
                catch (_: Exception) { Triple(Triple(null, null, null), null, "cancelled") }
                var ipPub: String? = ipResult.first.first
                var ipLat: Double? = ipResult.first.second
                var ipLng: Double? = ipResult.first.third
                var ipCity: String? = ipResult.second
                var ipErr: String? = ipResult.third
                val motion = try { motionDeferred.await() } catch (_: Exception) { Triple(true, 0.0, 0.0) }
                // Pengaman ganda: mock bisa dinyalakan selama jeda sampling/IP lookup di atas
                // (dilewati untuk admin/developer — server tetap menandai PALSU)
                if (!privileged) {
                    (mockLocationThreat() ?: lightThreat())?.let {
                        showWarn("$it.\nCheck-in dikunci sampai mock dimatikan.")
                        refreshCaptureButton(it); tvStatus.text = ""; return@launch
                    }
                    if (isMockLocation(loc)) {
                        showWarn("Lokasi terdeteksi PALSU (mock provider).\nCheck-in dikunci sampai mock dimatikan.")
                        refreshCaptureButton("Lokasi PALSU (mock provider)")
                        tvStatus.text = ""
                        return@launch
                    }
                } else {
                    toast("Mode admin: lokasi mock tetap dikirim (ditandai server)")
                }
                // Template di HP: banner tanggal+alamat+koordinat+logo ditempel di sini.
                // Server TIDAK lagi membingkai (payload framed=true) — tapi tetap hash + RSA-sign.
                tvStatus.text = "Menempel template..."
                val wib = java.util.TimeZone.getTimeZone("Asia/Jakarta")
                val bannerDate = SimpleDateFormat("dd MMM yyyy HH:mm:ss", Locale("in", "ID")).apply { timeZone = wib }.format(Date())
                val bannerCoord = "%.6f, %.6f".format(loc.latitude, loc.longitude)
                val photo = framePhoto(downsized, bannerDate, addr, bannerCoord)
                val payloadJson = JSONObject()
                    .put("user_id", userId())
                    .put("timestamp", ts)
                    .put("lat", loc.latitude)
                    .put("lng", loc.longitude)
                    .put("accuracy", if (loc.hasAccuracy()) loc.accuracy.toDouble() else JSONObject.NULL)
                    .put("image_hash", sha256Hex(photo))
                    .put("framed", true)
                    .put("banner_date", bannerDate)
                    .put("banner_addr", addr ?: JSONObject.NULL)
                    .put("banner_coord", bannerCoord)
                    .put("mock", mock)
                    .put("dev_mode", isDevModeOn())
                    .put("front", lensFacing == CameraSelector.LENS_FACING_FRONT)
                    .put("rooted", try { com.scottyab.rootbeer.RootBeer(this@CheckinActivity).isRooted } catch (_: Exception) { false })
                    .put("xposed", isXposedInstalled())
                    .put("net_lat", netLoc?.latitude ?: JSONObject.NULL)
                    .put("net_lng", netLoc?.longitude ?: JSONObject.NULL)
                    .put("net_acc", if (netLoc?.hasAccuracy() == true) netLoc.accuracy.toDouble() else JSONObject.NULL)
                    .put("gps_raw_lat", gpsRaw?.latitude ?: JSONObject.NULL)
                    .put("gps_raw_lng", gpsRaw?.longitude ?: JSONObject.NULL)
                    .put("gps_raw_acc", if (gpsRaw?.hasAccuracy() == true) gpsRaw.accuracy.toDouble() else JSONObject.NULL)
                    .put("gps_raw_mock", gpsRaw?.let { isMockLocation(it) } ?: JSONObject.NULL)
                    .put("ip_pub", ipPub ?: JSONObject.NULL)
                    .put("ip_lat", ipLat ?: JSONObject.NULL)
                    .put("ip_lng", ipLng ?: JSONObject.NULL)
                    .put("ip_city", ipCity ?: JSONObject.NULL)
                    .put("ip_err", ipErr?.take(120) ?: JSONObject.NULL)
                    .put("source", "fused")
                    .put("motion_still", motion.first)
                    .put("motion_accel_var", motion.second)
                    .put("motion_gyro_max", motion.third)
                    .put("sat_count", satCount ?: JSONObject.NULL)
                    .put("sat_used_in_fix", satUsedInFix ?: JSONObject.NULL)
                    .put("mean_cn0", meanCn0 ?: JSONObject.NULL)
                    .put("gps_time", loc.time)
                    .put("offline", !online)
                    .put("loc_age_ms", (android.os.SystemClock.elapsedRealtimeNanos() - loc.elapsedRealtimeNanos) / 1_000_000)
                    .toString()
                val hmac = hmacSha256(secret(), payloadJson)
                if (!online) {
                    // Tanpa sinyal: simpan di HP (foto + payload HMAC), kirim nanti via SYNC.
                    savePending(photo, loc, addr, ts, payloadJson, hmac, offline = true)
                    tvStatus.text = "📴 Disimpan offline — sync saat ada sinyal."
                    toast("Disimpan offline")
                    return@launch
                }
                val part = MultipartBody.Part.createFormData(
                    "photo", "selfie.jpg", photo.asRequestBody("image/jpeg".toMediaType()))
                fun s(v: String) = v.toRequestBody("text/plain".toMediaType())
                val vpId = intent.getStringExtra("kunjungan_id")
                val res = api().checkin(
                    "Bearer ${token()}", part,
                    s(loc.latitude.toString()), s(loc.longitude.toString()),
                    if (loc.hasAccuracy()) s(loc.accuracy.toString()) else null,
                    addr?.let { s(it) },
                    s(ts), s(payloadJson), s(hmac),
                    vpId?.let { s(it) }
                )
                // Server yang menilai asli/palsu — HP hanya tampil netral.
                tvStatus.text = "Check-in tersimpan."
                toast("Check-in tersimpan")
            } catch (e: retrofit2.HttpException) {
                if (e.code() == 401) {
                    prefs().edit().remove("auth_token").remove("checkin_secret")
                        .remove("user_id").remove("user_email").apply()
                    toast("Sesi berakhir, login ulang"); finish()
                } else {
                    // Tampilkan pesan server (mis. 429 jeda minimal: kapan boleh coba lagi)
                    val msg = try {
                        val body = e.response()?.errorBody()?.string()
                        org.json.JSONObject(body ?: "").optString("message").takeIf { it.isNotEmpty() }
                    } catch (_: Exception) { null }
                    toast(msg ?: "Gagal: ${e.message}"); tvStatus.text = ""
                }
            } catch (e: Exception) { if (e !is kotlinx.coroutines.CancellationException) toast("Gagal: ${e.message}"); tvStatus.text = "" }
        }
    }

    /** lastKnownLocation dengan cek permission eksplisit (lolos lint MissingPermission). */
    private fun lastKnownLoc(provider: String): android.location.Location? {
        if (androidx.core.content.ContextCompat.checkSelfPermission(
                this, android.Manifest.permission.ACCESS_FINE_LOCATION
            ) != android.content.pm.PackageManager.PERMISSION_GRANTED
        ) return null
        return try {
            (getSystemService(Context.LOCATION_SERVICE) as LocationManager).getLastKnownLocation(provider)
        } catch (_: SecurityException) { null }
    }

    private suspend fun currentLocation() = suspendCancellableCoroutine<android.location.Location?> { c ->
        if (androidx.core.content.ContextCompat.checkSelfPermission(
                this, android.Manifest.permission.ACCESS_FINE_LOCATION
            ) != android.content.pm.PackageManager.PERMISSION_GRANTED
        ) {
            c.resume(null)
            return@suspendCancellableCoroutine
        }
        LocationServices.getFusedLocationProviderClient(this)
            .getCurrentLocation(Priority.PRIORITY_HIGH_ACCURACY, null)
            .addOnSuccessListener { c.resume(it) }
            .addOnFailureListener { c.resumeWithException(it) }
    }

    /** Ada koneksi internet (WiFi/seluler) atau tidak. */
    fun isOnline(): Boolean = try {
        val cm = getSystemService(Context.CONNECTIVITY_SERVICE) as android.net.ConnectivityManager
        val net = cm.activeNetwork ?: return false
        val caps = cm.getNetworkCapabilities(net) ?: return false
        caps.hasTransport(android.net.NetworkCapabilities.TRANSPORT_WIFI) ||
            caps.hasTransport(android.net.NetworkCapabilities.TRANSPORT_CELLULAR) ||
            caps.hasTransport(android.net.NetworkCapabilities.TRANSPORT_ETHERNET)
    } catch (_: Exception) { false }

    /** Jumlah antrean offline. */
    fun pendingCount(): Int =
        File(filesDir, "pending").listFiles { f -> f.name.endsWith(".meta.bin") }?.size ?: 0

    /** Simpan check-in ke antrean offline TERENKRIPSI (foto + payload HMAC yang sudah di-sign). */
    private fun savePending(
        photo: File, loc: android.location.Location, addr: String?,
        ts: String, payloadJson: String, hmac: String, offline: Boolean
    ) {
        val dir = File(filesDir, "pending").apply { mkdirs() }
        val id = System.currentTimeMillis().toString()
        // Foto dienkripsi: klaim lokasi/waktu/alamat aman di dalam payload terenkripsi
        File(dir, "$id.jpg.bin").writeBytes(PendingCrypto.encryptBytes(photo.readBytes()))
        JSONObject()
            .put("lat", loc.latitude)
            .put("lng", loc.longitude)
            .put("accuracy", if (loc.hasAccuracy()) loc.accuracy.toDouble() else JSONObject.NULL)
            .put("address", addr ?: JSONObject.NULL)
            .put("ts", ts)
            .put("payload", payloadJson)
            .put("hmac", hmac)
            .put("offline", offline)
            .toString()
            .let { File(dir, "$id.meta.bin").writeBytes(PendingCrypto.encryptText(it)) }
    }

    /** Sampel sensor gerak 3 detik: diam vs bergerak (silang vs perpindahan GPS). */
    private suspend fun sampleMotion(): Triple<Boolean, Double, Double> =
        suspendCancellableCoroutine { c ->
            try {
                val sm = getSystemService(Context.SENSOR_SERVICE) as android.hardware.SensorManager
                val acc = sm.getDefaultSensor(android.hardware.Sensor.TYPE_ACCELEROMETER)
                val gyr = sm.getDefaultSensor(android.hardware.Sensor.TYPE_GYROSCOPE)
                if (acc == null && gyr == null) { c.resume(Triple(true, 0.0, 0.0)); return@suspendCancellableCoroutine }
                val accMags = mutableListOf<Double>()
                var gyroMax = 0.0
                val listener = object : android.hardware.SensorEventListener {
                    override fun onSensorChanged(e: android.hardware.SensorEvent) {
                        if (e.sensor.type == android.hardware.Sensor.TYPE_ACCELEROMETER) {
                            accMags.add(kotlin.math.sqrt((e.values[0]*e.values[0] + e.values[1]*e.values[1] + e.values[2]*e.values[2]).toDouble()))
                        } else {
                            val m = kotlin.math.sqrt((e.values[0]*e.values[0] + e.values[1]*e.values[1] + e.values[2]*e.values[2]).toDouble())
                            if (m > gyroMax) gyroMax = m
                        }
                    }
                    override fun onAccuracyChanged(s: android.hardware.Sensor?, a: Int) { }
                }
                acc?.let { sm.registerListener(listener, it, android.hardware.SensorManager.SENSOR_DELAY_NORMAL) }
                gyr?.let { sm.registerListener(listener, it, android.hardware.SensorManager.SENSOR_DELAY_NORMAL) }
                android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
                    try { sm.unregisterListener(listener) } catch (_: Exception) { }
                    val mean = if (accMags.isEmpty()) 9.81 else accMags.average()
                    val variance = if (accMags.isEmpty()) 0.0 else accMags.map { (it - mean) * (it - mean) }.average()
                    // Ambang longgar: hanya flag bila sangat diam tapi GPS berpindah jauh
                    val still = variance < 0.15 && gyroMax < 0.3
                    if (!c.isCompleted) c.resume(Triple(still, variance, gyroMax))
                }, 2000)
            } catch (e: Exception) {
                if (!c.isCompleted) c.resume(Triple(true, 0.0, 0.0))
            }
        }

    /** Geolokasi IP publik (best-effort, timeout pendek). Dijalankan paralel via async. */
    private fun fetchIpGeo(online: Boolean): Triple<Triple<String?, Double?, Double?>, String?, String?> {
        if (!online) return Triple(Triple(null, null, null), null, "offline")
        try {
            val c = java.net.URL("http://ip-api.com/json/?fields=status,message,query,lat,lon,city,country").openConnection() as java.net.HttpURLConnection
            c.connectTimeout = 2500; c.readTimeout = 2500
            val jo = JSONObject(c.inputStream.bufferedReader().readText())
            if (jo.optString("status") == "success") {
                return Triple(
                    Triple(jo.optString("query", null),
                        jo.optDouble("lat", Double.NaN).takeIf { !it.isNaN() },
                        jo.optDouble("lon", Double.NaN).takeIf { !it.isNaN() }),
                    (jo.optString("city", "") + ", " + jo.optString("country", "")).trim().trim(','), null)
            }
        } catch (_: Exception) { }
        try {
            val c = java.net.URL("https://ipwho.is/?fields=success,message,ip,latitude,longitude,city,country").openConnection() as java.net.HttpURLConnection
            c.connectTimeout = 2500; c.readTimeout = 2500
            val jo = JSONObject(c.inputStream.bufferedReader().readText())
            if (jo.optBoolean("success", false)) {
                return Triple(
                    Triple(jo.optString("ip", null),
                        jo.optDouble("latitude", Double.NaN).takeIf { !it.isNaN() },
                        jo.optDouble("longitude", Double.NaN).takeIf { !it.isNaN() }),
                    (jo.optString("city", "") + ", " + jo.optString("country", "")).trim().trim(','), null)
            }
            return Triple(Triple(null, null, null), null, "ipwho: " + jo.optString("message", "?"))
        } catch (e: Exception) {
            return Triple(Triple(null, null, null), null, "net: " + (e.message ?: e.toString()))
        }
    }

    /** Template check-in di HP: banner bawah (tanggal+alamat+koordinat) + logo APN kiri-atas.
     *  Hasilnya yang di-upload; server tidak lagi membingkai (tetap hash + RSA-sign). */
    private fun framePhoto(f: File, dateStr: String, addr: String?, coord: String): File {
        try {
            val opts = android.graphics.BitmapFactory.Options().apply { inMutable = true }
            var bmp = android.graphics.BitmapFactory.decodeFile(f.absolutePath, opts)
                ?: return f
            // Pastikan mutable + orientasi benar (abaikan EXIF: decode manual lalu putar bila perlu)
            bmp = bmp.copy(android.graphics.Bitmap.Config.ARGB_8888, true)
            val w = bmp.width; val h = bmp.height
            val c = android.graphics.Canvas(bmp)
            val density = resources.displayMetrics.density

            // --- Logo APN kiri-atas (lebar ~28% foto) ---
            try {
                val logo = android.graphics.BitmapFactory.decodeResource(resources, R.drawable.apn_logo)
                if (logo != null) {
                    val targetW = (w * 0.28).toInt()
                    val scale = targetW.toFloat() / logo.width
                    val targetH = (logo.height * scale).toInt()
                    val resized = android.graphics.Bitmap.createScaledBitmap(logo, targetW, targetH, true)
                    val m = (w * 0.03).toFloat()
                    // PNG sudah transparan — tempel langsung tanpa latar.
                    c.drawBitmap(resized, m, m, null)
                    if (resized != logo) resized.recycle()
                    logo.recycle()
                }
            } catch (_: Exception) { }

            // --- Banner bawah ---
            val datePx = (w / 26f).coerceAtLeast(20f * density)
            val smallPx = (datePx * 0.6f).coerceAtLeast(13f * density)
            val pad = datePx * 0.8f
            val datePaint = android.graphics.Paint().apply {
                color = android.graphics.Color.YELLOW; textSize = datePx
                typeface = android.graphics.Typeface.DEFAULT_BOLD; isAntiAlias = true
            }
            val smallPaint = android.graphics.Paint().apply {
                color = android.graphics.Color.WHITE; textSize = smallPx; isAntiAlias = true
            }
            // Bungkus alamat maks 2 baris sesuai lebar
            val addrLines = mutableListOf<String>()
            for (word in (addr ?: "-").split("\\s+".toRegex())) {
                val cur = if (addrLines.isEmpty()) "" else addrLines.removeAt(addrLines.size - 1) + " "
                val trial = cur + word
                if (smallPaint.measureText(trial) > w - pad * 2 && cur.isNotBlank()) {
                    addrLines.add(cur.trim())
                    if (addrLines.size >= 2) break
                    addrLines.add(word)
                } else addrLines.add(trial)
            }
            if (addrLines.size > 2) addrLines.subList(2, addrLines.size).clear()
            val lineH = smallPx * 1.45f
            val barH = (pad * 2 + datePx * 1.5f + lineH * (addrLines.size + 1)).toInt()
            c.drawRect(0f, (h - barH).toFloat(), w.toFloat(), h.toFloat(),
                android.graphics.Paint().apply { color = android.graphics.Color.BLACK })
            var y = (h - barH + pad + datePx)
            c.drawText(dateStr, pad, y, datePaint)
            y += datePx * 1.5f
            for (line in addrLines) { y += smallPx * 0.45f; c.drawText(line, pad, y + smallPx * 0.55f, smallPaint); y += lineH - smallPx * 0.45f }
            c.drawText(coord, pad, y + smallPx, smallPaint)

            val out = File(cacheDir, "framed_${System.currentTimeMillis()}.jpg")
            java.io.FileOutputStream(out).use { bmp.compress(android.graphics.Bitmap.CompressFormat.JPEG, 90, it) }
            bmp.recycle()
            return out
        } catch (_: Exception) { return f }
    }

    /** Kecilkan foto bila sisi terpanjang > 1600px agar upload jauh lebih cepat.
     *  EXIF orientasi diterapkan ke piksel dulu (compress menghapus EXIF). */
    private fun downscaleIfNeeded(f: File, maxDim: Int = 1600): File {
        try {
            val bounds = android.graphics.BitmapFactory.Options().apply { inJustDecodeBounds = true }
            android.graphics.BitmapFactory.decodeFile(f.absolutePath, bounds)
            if (bounds.outWidth <= 0) return f
            val exif = try { androidx.exifinterface.media.ExifInterface(f.absolutePath) } catch (_: Exception) { null }
            val orient = exif?.getAttributeInt(
                androidx.exifinterface.media.ExifInterface.TAG_ORIENTATION,
                androidx.exifinterface.media.ExifInterface.ORIENTATION_NORMAL
            ) ?: androidx.exifinterface.media.ExifInterface.ORIENTATION_NORMAL
            var sample = 1
            while (bounds.outWidth / sample > maxDim || bounds.outHeight / sample > maxDim) sample *= 2
            if (sample <= 1 && orient == androidx.exifinterface.media.ExifInterface.ORIENTATION_NORMAL) return f
            val opts = android.graphics.BitmapFactory.Options().apply { inSampleSize = maxOf(sample, 1) }
            val raw = android.graphics.BitmapFactory.decodeFile(f.absolutePath, opts) ?: return f
            val m = android.graphics.Matrix()
            when (orient) {
                androidx.exifinterface.media.ExifInterface.ORIENTATION_ROTATE_90 -> m.postRotate(90f)
                androidx.exifinterface.media.ExifInterface.ORIENTATION_ROTATE_180 -> m.postRotate(180f)
                androidx.exifinterface.media.ExifInterface.ORIENTATION_ROTATE_270 -> m.postRotate(270f)
                androidx.exifinterface.media.ExifInterface.ORIENTATION_FLIP_HORIZONTAL -> m.postScale(-1f, 1f)
                androidx.exifinterface.media.ExifInterface.ORIENTATION_FLIP_VERTICAL -> m.postScale(1f, -1f)
                androidx.exifinterface.media.ExifInterface.ORIENTATION_TRANSPOSE -> { m.postRotate(90f); m.postScale(-1f, 1f) }
                androidx.exifinterface.media.ExifInterface.ORIENTATION_TRANSVERSE -> { m.postRotate(270f); m.postScale(-1f, 1f) }
            }
            val bmp = if (m.isIdentity) raw
            else android.graphics.Bitmap.createBitmap(raw, 0, 0, raw.width, raw.height, m, true).also { raw.recycle() }
            val out = File(cacheDir, "send_${System.currentTimeMillis()}.jpg")
            java.io.FileOutputStream(out).use { bmp.compress(android.graphics.Bitmap.CompressFormat.JPEG, 85, it) }
            bmp.recycle()
            return out
        } catch (_: Exception) { return f }
    }

    private suspend fun takePhoto() = suspendCancellableCoroutine<File> { c ->
        val f = File(cacheDir, "selfie_${System.currentTimeMillis()}.jpg")
        imageCapture!!.takePicture(
            ImageCapture.OutputFileOptions.Builder(f).build(),
            ContextCompat.getMainExecutor(this),
            object : ImageCapture.OnImageSavedCallback {
                override fun onImageSaved(o: ImageCapture.OutputFileResults) { c.resume(f) }
                override fun onError(e: ImageCaptureException) { c.resumeWithException(e) }
            })
    }

    private fun hmacSha256(keyHex: String, data: String): String {
        val key = keyHex.chunked(2).map { it.toInt(16).toByte() }.toByteArray()
        val mac = javax.crypto.Mac.getInstance("HmacSHA256")
        mac.init(javax.crypto.spec.SecretKeySpec(key, "HmacSHA256"))
        return mac.doFinal(data.toByteArray()).joinToString("") { "%02x".format(it) }
    }

    private fun sha256Hex(f: File): String {
        val d = java.security.MessageDigest.getInstance("SHA-256")
        f.inputStream().use { ins ->
            val b = ByteArray(8192); var n: Int
            while (ins.read(b).also { n = it } > 0) d.update(b, 0, n)
        }
        return d.digest().joinToString("") { "%02x".format(it) }
    }

    // ---------- Anti fake-GPS / developer ----------

    private fun isDevModeOn(): Boolean = try {
        Settings.Global.getInt(contentResolver, Settings.Global.DEVELOPMENT_SETTINGS_ENABLED, 0) == 1
    } catch (_: Exception) { false }

    /** Label info pengganti popup: null = sembunyikan. */
    private fun showWarn(msg: String?) {
        if (msg.isNullOrEmpty()) {
            // Jangan hapus label titik kunjungan di tvStatus; tvWarn khusus peringatan
            tvWarn.visibility = android.view.View.GONE
        } else {
            tvWarn.text = "⚠️ $msg"
            tvWarn.visibility = android.view.View.VISIBLE
        }
    }

    private fun isMockLocation(l: android.location.Location): Boolean {
        // API 31+: isMock. Di bawah itu: isFromMockProvider. Cek keduanya.
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            try { if (l.isMock) return true } catch (_: Exception) { }
        }
        @Suppress("DEPRECATION")
        return try { l.isFromMockProvider } catch (_: Exception) { false }
    }

    /** Cek framework Xposed/LSPosed terinstal (dipakai modul cheat UnMock-GPS/HideMockLocation). */
    private fun isXposedInstalled(): Boolean {
        return try {
            val pkgs = setOf(
                "org.lsposed.manager", "de.robv.android.xposed.installer",
                "com.solohsu.android.edxp.manager", "org.meowcat.edxposed.manager"
            )
            pkgs.any { pkg ->
                try {
                    if (Build.VERSION.SDK_INT >= 33) {
                        packageManager.getPackageInfo(pkg, PackageManager.PackageInfoFlags.of(0))
                    } else {
                        @Suppress("DEPRECATION")
                        packageManager.getPackageInfo(pkg, 0)
                    }
                    true
                } catch (_: Exception) { false }
            }
        } catch (_: Exception) { false }
    }

    private fun mockLocationThreat(): String? {
        val cr = contentResolver
        try {
            val mockApp = Settings.Secure.getString(cr, "mock_location")
            if (!mockApp.isNullOrEmpty() && mockApp != "0") {
                return "Aplikasi mock location aktif ($mockApp)"
            }
        } catch (_: Exception) { }
        // Paket RootBeer: perangkat root = jalur umum fake GPS level sistem
        try {
            if (com.scottyab.rootbeer.RootBeer(this).isRooted) {
                return "Perangkat di-root (risiko fake GPS sistem)"
            }
        } catch (_: Exception) { }
        // UnMock-GPS & HideMockLocation adalah MODUL CHEAT Xposed (menyembunyikan flag
        // mock dari aplikasi). Kehadiran Xposed manager = risiko bypass deteksi mock.
        try {
            val xposedPkgs = setOf(
                "org.lsposed.manager", "de.robv.android.xposed.installer",
                "com.solohsu.android.edxp.manager", "org.meowcat.edxposed.manager"
            )
            for (pkg in xposedPkgs) {
                try {
                    if (Build.VERSION.SDK_INT >= 33) {
                        packageManager.getPackageInfo(pkg, PackageManager.PackageInfoFlags.of(0))
                    } else {
                        @Suppress("DEPRECATION")
                        packageManager.getPackageInfo(pkg, 0)
                    }
                    return "Framework Xposed/LSPosed terinstal ($pkg) — risiko bypass deteksi mock"
                } catch (_: Exception) { }
            }
        } catch (_: Exception) { }
        val devOn = isDevModeOn()
        if (devOn) {
            val pm = packageManager
            val mockApps = pm.getInstalledApplications(0).filter { app ->
                if (app.packageName == packageName) return@filter false
                try {
                    val info = if (Build.VERSION.SDK_INT >= 33) {
                        pm.getPackageInfo(app.packageName,
                            PackageManager.PackageInfoFlags.of(PackageManager.GET_PERMISSIONS.toLong()))
                    } else {
                        @Suppress("DEPRECATION")
                        pm.getPackageInfo(app.packageName, PackageManager.GET_PERMISSIONS)
                    }
                    info.requestedPermissions?.contains("android.permission.ACCESS_MOCK_LOCATION") == true
                } catch (_: Exception) { false }
            }
            if (mockApps.isNotEmpty()) {
                return "Developer options aktif + ${mockApps.size} aplikasi fake-GPS terinstal"
            }
        }
        return null
    }

    private fun toast(m: String) = Toast.makeText(this, m, Toast.LENGTH_LONG).show()
}
