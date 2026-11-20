package com.kantor.checkin

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationManager
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import kotlinx.coroutines.launch
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker
import org.osmdroid.views.overlay.Polygon

/**
 * Daftar titik kunjungan: 1 card per titik, tiap card bawa map sendiri
 * (pin titik + radius + posisi saya) + tombol rute Google Maps.
 */
class VisitActivity : AppCompatActivity() {

    private lateinit var listBox: LinearLayout
    private lateinit var tvInfo: TextView
    private var myLoc: Location? = null
    private val cardMaps = mutableListOf<MapView>()

    private fun token() = getSharedPreferences("cfg", MODE_PRIVATE).getString("auth_token", "") ?: ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        Configuration.getInstance().load(this, getSharedPreferences("osm", MODE_PRIVATE))
        setContentView(R.layout.activity_visit)
        BottomNav.wire(this, BottomNav.VISIT)
        listBox = findViewById(R.id.listBox)
        tvInfo = findViewById(R.id.tvInfo)
        tvInfo.text = "Memuat titik..."
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.setOnRefreshListener { load() }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), 1)
        }
        checkLocationEnabled()
    }

    override fun onRequestPermissionsResult(rc: Int, p: Array<out String>, r: IntArray) {
        super.onRequestPermissionsResult(rc, p, r)
        // load() jalan lewat onResume yang otomatis dipanggil setelah dialog izin.
    }

    /** Ingatkan bila layanan lokasi HP mati (jarak & status jangkauan butuh GPS). */
    private fun checkLocationEnabled() {
        val lm = getSystemService(LOCATION_SERVICE) as LocationManager
        val on = try {
            lm.isProviderEnabled(LocationManager.GPS_PROVIDER) ||
                lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
        } catch (_: Exception) { true }
        if (!on) {
            androidx.appcompat.app.AlertDialog.Builder(this)
                .setTitle("Lokasi HP mati")
                .setMessage("Layanan lokasi (GPS) belum aktif — jarak dan status jangkauan tidak bisa dihitung. Nyalakan dulu.")
                .setPositiveButton("Nyalakan") { _, _ ->
                    try { startActivity(Intent(android.provider.Settings.ACTION_LOCATION_SOURCE_SETTINGS)) }
                    catch (_: Exception) { }
                }
                .setNegativeButton("Nanti", null)
                .show()
        }
    }

    /** Lokasi akurat aman: null bila provider null/mati, tanpa NPE. Maks ~20 detik. */
    private suspend fun safeCurrentLocation(): Location? {
        return try {
            kotlinx.coroutines.withTimeoutOrNull(20_000) {
                if (ContextCompat.checkSelfPermission(
                        this@VisitActivity, Manifest.permission.ACCESS_FINE_LOCATION
                    ) != PackageManager.PERMISSION_GRANTED
                ) return@withTimeoutOrNull null
                val client = LocationServices.getFusedLocationProviderClient(this@VisitActivity)
                val task = try {
                    client.getCurrentLocation(Priority.PRIORITY_HIGH_ACCURACY, null)
                } catch (_: Exception) { null }
                // Task bisa null di sebagian HP/ROM (lokasi mati) — jangan dipanggil langsung.
                val fresh = try { task?.resultOrNull() } catch (_: Exception) { null }
                if (fresh != null) return@withTimeoutOrNull fresh
                // Fallback lokasi terakhir hanya bila masih segar (<2 menit),
                // kalau basi (>2 mnt) tolak — lebih baik "GPS null" daripada koordinat ngaco.
                val last = try { client.lastLocation.resultOrNull() } catch (_: Exception) { null }
                if (last != null &&
                    (android.os.SystemClock.elapsedRealtimeNanos() - last.elapsedRealtimeNanos) / 1_000_000_000 <= 120
                ) return@withTimeoutOrNull last
                null
            }
        } catch (_: Exception) { null }
    }

    private fun load() {
        lifecycleScope.launch {
            // Lokasi diambil PARALEL tanpa menghalangi daftar; kartu di-render
            // ulang saat lokasi tiba agar jarak/status jangkauan muncul.
            var visits: VisitPointsRes? = null
            lifecycleScope.launch {
                val loc = try { safeCurrentLocation() } catch (_: Exception) { null }
                if (loc != null) {
                    myLoc = loc
                    visits?.let { render(it.data) }
                }
            }
            try {
                // Daftar titik dimuat DULUAN tanpa menunggu GPS (GPS sering lama di dalam
                // ruangan).
                val api = MainActivity().let {
                    val log = okhttp3.logging.HttpLoggingInterceptor().setLevel(okhttp3.logging.HttpLoggingInterceptor.Level.BASIC)
                    val client = okhttp3.OkHttpClient.Builder()
                        .connectTimeout(15, java.util.concurrent.TimeUnit.SECONDS)
                        .readTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                        .addInterceptor(log).build()
                    val base = getSharedPreferences("cfg", MODE_PRIVATE).getString("base_url", MainActivity.DEFAULT_URL)!!.trim().let { if (it.endsWith("/")) it else "$it/" }
                    retrofit2.Retrofit.Builder().baseUrl(base).client(client)
                        .addConverterFactory(retrofit2.converter.gson.GsonConverterFactory.create(com.google.gson.Gson())).build()
                        .create(ApiService::class.java)
                }
                val res = api.visitPoints("Bearer ${token()}", null)
                visits = res
                tvInfo.text = "Titik hari ini (${res.date}): ${res.data.size}"
                render(res.data)
            } catch (e: Exception) {
                if (e !is kotlinx.coroutines.CancellationException) toast("Gagal muat titik: ${e.message}")
                findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.isRefreshing = false
            }
        }
    }

    private suspend fun com.google.android.gms.tasks.Task<Location?>.resultOrNull(): Location? =
        kotlinx.coroutines.suspendCancellableCoroutine { c ->
            addOnSuccessListener { c.resume(it, null) }
            addOnFailureListener { c.resume(null, null) }
        }

    private fun distM(aLat: Double, aLng: Double, bLat: Double, bLng: Double): Float {
        val r = FloatArray(1)
        Location.distanceBetween(aLat, aLng, bLat, bLng, r)
        return r[0]
    }

    override fun onResume() {
        super.onResume()
        cardMaps.forEach { try { it.onResume() } catch (_: Exception) { } }
        // Muat ulang tiap kembali ke halaman: tidak pernah blank, data selalu segar.
        load()
    }

    override fun onPause() {
        cardMaps.forEach { try { it.onPause() } catch (_: Exception) { } }
        super.onPause()
    }

    private fun render(visits: List<VisitPoint>) {
        listBox.removeAllViews()
        cardMaps.clear()
        for (p in visits) {
            val d = myLoc?.let { distM(it.latitude, it.longitude, p.latitude, p.longitude) }
            // Bila titik punya polygon: dalam jangkauan = di dalam SALAH SATU area (toleransi ikut server).
            val rings = parsePolygonRings(p.polygon)
            val inRange = if (!rings.isNullOrEmpty() && myLoc != null) {
                rings.any { pointInPolygon(myLoc!!.latitude, myLoc!!.longitude, it) }
            } else d != null && d <= p.radius_m
            listBox.addView(visitCard(p, d, inRange))
        }
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.isRefreshing = false
    }

    private fun dp(v: Int) = (v * resources.displayMetrics.density).toInt()

    /** Jarak: < 1 km tampil meter, ≥ 1 km tampil km 4 digit koma. */
    private fun fmtDist(d: Float): String =
        if (d >= 1000f) "±%.4f km".format(d / 1000f) else "±%.0f m".format(d)

    /** Satu card per titik kunjungan: info kantor + jarak + tombol absen. */
    private fun visitCard(p: VisitPoint, d: Float?, inRange: Boolean): com.google.android.material.card.MaterialCardView {
        val card = com.google.android.material.card.MaterialCardView(this).apply {
            radius = dp(12).toFloat()
            cardElevation = dp(4).toFloat()
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply { setMargins(0, 0, 0, dp(12)) }
            setContentPadding(dp(16), dp(16), dp(16), dp(16))
        }
        val box = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            )
        }

        // Judul + badge status
        val head = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = android.view.Gravity.CENTER_VERTICAL
        }
        val title = TextView(this).apply {
            text = "🏢 ${p.name}"
            textSize = 16f
            setTypeface(null, android.graphics.Typeface.BOLD)
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
        }
        head.addView(title)
        head.addView(statusBadge(if (p.status == "done") "Selesai ✅" else "Pending"))
        box.addView(head)

        // Map sendiri per card: pin titik + radius + posisi saya. Kamera anteng di titik.
        val cardMap = buildCardMap(p)
        box.addView(cardMap)

        // Baris info
        if (p.kebun != null) box.addView(infoRow("Kebun", p.kebun + (if (p.objek != null) " • ${p.objek}" else "")))
        box.addView(infoRow("Area", if (!p.polygon.isNullOrBlank()) "Polygon 🗺️" else "${p.radius_m} m"))
        box.addView(infoRow("Jarak dari saya", if (d == null) "GPS null" else fmtDist(d)))
        box.addView(infoRow("Koordinat", "%.6f, %.6f".format(p.latitude, p.longitude)))
        box.addView(statusBadge(if (d == null) "Menunggu GPS..." else if (inRange) "✅ Dalam jangkauan" else "⚠️ Di luar jangkauan"))

        // Tombol posisi saya (di atas map card)
        val btnPos = Button(this).apply {
            text = "📍 Posisi Saya"
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply { topMargin = dp(8) }
            setOnClickListener { centerOnMe(cardMap, p) }
        }
        box.addView(btnPos)

        // Tombol absen + rute GMaps berdampingan
        val actRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply { topMargin = dp(8) }
        }
        val btn = Button(this).apply {
            text = if (p.status == "done") "Selesai ✅" else "📷 Absen di sini"
            isEnabled = p.status != "done"
            alpha = if (isEnabled) 1f else 0.5f
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 2f).apply { marginEnd = dp(4) }
            setOnClickListener { openCheckin(p) }
        }
        val btnRoute = Button(this).apply {
            text = "🧭 Rute"
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f).apply { marginStart = dp(4) }
            setOnClickListener { openRoute(p) }
        }
        actRow.addView(btn)
        actRow.addView(btnRoute)
        box.addView(actRow)

        card.addView(box)
        return card
    }

    /** Ikon pin lokasi bawaan OSM yang diwarnai (titik = hijau, posisi saya = merah). */
    private fun pinIcon(color: Int): android.graphics.drawable.Drawable? {
        return try {
            androidx.core.content.ContextCompat.getDrawable(
                this, org.osmdroid.library.R.drawable.marker_default
            )?.mutate()?.apply {
                setTint(color)
            }
        } catch (_: Exception) { null }
    }

    /** Ikon pin bulat berwarna (titik = hijau, posisi saya = merah). */
    private fun dotIcon(color: Int): android.graphics.drawable.BitmapDrawable {
        val s = dp(40)
        val bmp = android.graphics.Bitmap.createBitmap(s, s, android.graphics.Bitmap.Config.ARGB_8888)
        val c = android.graphics.Canvas(bmp)
        c.drawCircle(s / 2f, s / 2f, s / 2f - 2f,
            android.graphics.Paint().apply { this.color = color; isAntiAlias = true })
        c.drawCircle(s / 2f, s / 2f, s / 2f - 5f,
            android.graphics.Paint().apply {
                this.color = android.graphics.Color.WHITE
                style = android.graphics.Paint.Style.STROKE
                strokeWidth = 5f
                isAntiAlias = true
            })
        return android.graphics.drawable.BitmapDrawable(resources, bmp)
    }

    /** Map kecil per card: pin titik + radius + posisi saya. Kamera dikunci di titik. */
    private fun buildCardMap(p: VisitPoint): MapView {
        val mv = MapView(this)
        mv.layoutParams = LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT, dp(220)
        ).apply { topMargin = dp(8); bottomMargin = dp(4) }
        mv.setTileSource(TileSourceFactory.MAPNIK)
        mv.setMultiTouchControls(true)
        // Peta di dalam ScrollView: jangan biarkan induk mencuri gesture —
        // selama jari di atas peta, scroll halaman dimatikan agar peta bisa digeser/zoom.
        mv.setOnTouchListener { v, event ->
            when (event.action) {
                android.view.MotionEvent.ACTION_DOWN, android.view.MotionEvent.ACTION_MOVE ->
                    v.parent?.requestDisallowInterceptTouchEvent(true)
                android.view.MotionEvent.ACTION_UP, android.view.MotionEvent.ACTION_CANCEL ->
                    v.parent?.requestDisallowInterceptTouchEvent(false)
            }
            false // teruskan ke osmdroid agar peta tetap merespons
        }
        mv.controller.setZoom(16.0)
        mv.controller.setCenter(GeoPoint(p.latitude, p.longitude))
        Marker(mv).apply {
            position = GeoPoint(p.latitude, p.longitude)
            title = p.name
            pinIcon(0xFF1B9E57.toInt())?.let { icon = it } ?: run { icon = dotIcon(0xFF1B9E57.toInt()) } // hijau = titik kunjungan
            setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
            mv.overlays.add(this)
        }
        Polygon().apply {
            // Polygon GeoJSON per titik bila ada (menggantikan radius); else lingkaran radius.
            val polyRings = parsePolygonRings(p.polygon)
            if (!polyRings.isNullOrEmpty()) {
                // Hanya ring pertama digambar sebagai overlay utama...
                setPoints(polyRings[0])
                fillPaint.color = 0x332196F3
                outlinePaint.color = 0xFF1976D2.toInt()
                outlinePaint.strokeWidth = 4f
                mv.overlays.add(this)
                // ...ring sisanya sebagai overlay tambahan.
                for (i in 1 until polyRings.size) {
                    Polygon().apply {
                        setPoints(polyRings[i])
                        fillPaint.color = 0x332196F3
                        outlinePaint.color = 0xFF1976D2.toInt()
                        outlinePaint.strokeWidth = 4f
                        mv.overlays.add(this)
                    }
                }
                // Zoom pas ke batas semua area.
                try { mv.zoomToBoundingBox(boundingBox(polyRings.flatten()).increaseByScale(1.2f), false) } catch (_: Exception) {
                    mv.controller.setZoom(15.0)
                    mv.controller.setCenter(GeoPoint(p.latitude, p.longitude))
                }
            } else {
                setPoints(Polygon.pointsAsCircle(GeoPoint(p.latitude, p.longitude), p.radius_m.toDouble()))
                mv.overlays.add(this)
            }
        }
        myLoc?.let { addMeMarker(mv, it) }
        mv.invalidate()
        cardMaps.add(mv)
        return mv
    }

    /** Parse GeoJSON Polygon/MultiPolygon/Feature/FeatureCollection -> daftar ring luar.
     *  Null bila tidak valid. Kompatibel data lama (Polygon tunggal). */
    private fun parsePolygonRings(raw: String?): List<List<GeoPoint>>? {
        if (raw.isNullOrBlank()) return null
        return try {
            val gj = org.json.JSONObject(raw)
            val rings = mutableListOf<org.json.JSONArray>()
            fun addRing(a: org.json.JSONArray?) {
                if (a != null && a.length() >= 3) rings.add(a)
            }
            fun addCoords(type: String, coords: org.json.JSONArray?) {
                if (coords == null) return
                when (type) {
                    "Polygon" -> addRing(coords.optJSONArray(0))
                    "MultiPolygon" -> for (i in 0 until coords.length()) addRing(coords.optJSONArray(i)?.optJSONArray(0))
                }
            }
            when (gj.optString("type")) {
                "Feature" -> {
                    val g = gj.optJSONObject("geometry")
                    if (g != null) addCoords(g.optString("type"), g.optJSONArray("coordinates"))
                }
                "FeatureCollection" -> {
                    val fs = gj.optJSONArray("features")
                    if (fs != null) for (i in 0 until fs.length()) {
                        val g = fs.optJSONObject(i)?.optJSONObject("geometry") ?: continue
                        addCoords(g.optString("type"), g.optJSONArray("coordinates"))
                    }
                }
                else -> addCoords(gj.optString("type"), gj.optJSONArray("coordinates"))
            }
            val out = rings.mapNotNull { ring ->
                val pts = mutableListOf<GeoPoint>()
                for (i in 0 until ring.length()) {
                    val c = ring.optJSONArray(i) ?: continue
                    val lng = c.optDouble(0, Double.NaN); val lat = c.optDouble(1, Double.NaN)
                    if (lat.isNaN() || lng.isNaN()) continue
                    pts.add(GeoPoint(lat, lng))
                }
                if (pts.size >= 3) pts else null
            }
            if (out.isNotEmpty()) out else null
        } catch (_: Exception) { null }
    }

    /** Kompatibilitas: ring pertama saja. */
    private fun parsePolygonPoints(raw: String?): List<GeoPoint>? = parsePolygonRings(raw)?.firstOrNull()

    private fun boundingBox(pts: List<GeoPoint>): org.osmdroid.util.BoundingBox {
        var n = -90.0; var s = 90.0; var e = -180.0; var w = 180.0
        pts.forEach { n = maxOf(n, it.latitude); s = minOf(s, it.latitude); e = maxOf(e, it.longitude); w = minOf(w, it.longitude) }
        return org.osmdroid.util.BoundingBox(n, e, s, w)
    }

    /** Ray casting di bidang lat/lng. */
    private fun pointInPolygon(lat: Double, lng: Double, poly: List<GeoPoint>): Boolean {
        var inside = false
        var j = poly.size - 1
        for (i in poly.indices) {
            val xi = poly[i].longitude; val yi = poly[i].latitude
            val xj = poly[j].longitude; val yj = poly[j].latitude
            if ((yi > lat) != (yj > lat) && lng < (xj - xi) * (lat - yi) / (yj - yi) + xi) inside = !inside
            j = i
        }
        return inside
    }

    private fun addMeMarker(mv: MapView, loc: Location) {
        (mv.tag as? Marker)?.let { mv.overlays.remove(it) }
        Marker(mv).apply {
            position = GeoPoint(loc.latitude, loc.longitude)
            title = "Posisi saya"
            pinIcon(0xFFD63A3A.toInt())?.let { icon = it } ?: run { icon = dotIcon(0xFFD63A3A.toInt()) } // merah = posisi saya
            setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
            mv.overlays.add(this)
            mv.tag = this
        }
        mv.invalidate()
    }

    /** Tombol "Posisi Saya": fit — posisi HP + area tujuan kelihatan sekaligus. */
    private fun centerOnMe(mv: MapView, p: VisitPoint) {
        if (androidx.core.content.ContextCompat.checkSelfPermission(
                this, android.Manifest.permission.ACCESS_FINE_LOCATION
            ) != android.content.pm.PackageManager.PERMISSION_GRANTED
        ) {
            toast("Berikan izin lokasi dulu")
            return
        }
        lifecycleScope.launch {
            try {
                val loc = safeCurrentLocation()
                if (loc == null) { toast("GPS null — nyalakan lokasi & ke area terbuka"); return@launch }
                myLoc = loc
                addMeMarker(mv, loc)
                // Batas area tujuan: semua polygon bila ada, else titik.
                val target = parsePolygonRings(p.polygon)?.flatten()?.let { boundingBox(it) }
                    ?: org.osmdroid.util.BoundingBox(p.latitude, p.longitude, p.latitude, p.longitude)
                val box = org.osmdroid.util.BoundingBox(
                    maxOf(loc.latitude, target.latNorth),
                    maxOf(loc.longitude, target.lonEast),
                    minOf(loc.latitude, target.latSouth),
                    minOf(loc.longitude, target.lonWest)
                ).increaseByScale(1.3f)
                // Tunggu layout peta siap baru fit, agar tidak ngaco.
                mv.post {
                    try { mv.zoomToBoundingBox(box, false) }
                    catch (_: Exception) {
                        mv.controller.setZoom(15.0)
                        mv.controller.animateTo(GeoPoint(loc.latitude, loc.longitude))
                    }
                }
            } catch (e: Exception) { if (e !is kotlinx.coroutines.CancellationException) toast("Gagal dapat lokasi: ${e.message}") }
        }
    }

    /** Buka Google Maps: rute dari lokasi sekarang ke titik tujuan. */
    private fun openRoute(p: VisitPoint) {
        val nav = Intent(
            Intent.ACTION_VIEW,
            Uri.parse("google.navigation:q=${p.latitude},${p.longitude}&mode=d")
        ).apply { setPackage("com.google.android.apps.maps") }
        try {
            startActivity(nav)
        } catch (_: Exception) {
            startActivity(
                Intent(
                    Intent.ACTION_VIEW,
                    Uri.parse("https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}")
                )
            )
        }
    }

    private fun infoRow(label: String, value: String): LinearLayout {
        return LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            setPadding(0, dp(2), 0, dp(2))
            addView(TextView(context).apply {
                text = label
                textSize = 12f
                setTextColor(0xFF757575.toInt())
                layoutParams = LinearLayout.LayoutParams(dp(110), LinearLayout.LayoutParams.WRAP_CONTENT)
            })
            addView(TextView(context).apply {
                text = value
                textSize = 13f
                layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            })
        }
    }

    private fun statusBadge(text: String): TextView {
        return TextView(this).apply {
            this.text = text
            textSize = 12f
            setPadding(dp(10), dp(4), dp(10), dp(4))
            setBackgroundColor(0xFFE8F0E8.toInt())
        }
    }

    /** Buka kamera check-in untuk titik ini (nama titik diteruskan untuk label). */
    private fun openCheckin(p: VisitPoint) {
        startActivity(
            Intent(this, CheckinActivity::class.java)
                .putExtra("kunjungan_id", p.id)
                .putExtra("kunjungan_name", p.name)
                .putExtra("kunjungan_lat", p.latitude)
                .putExtra("kunjungan_lng", p.longitude)
                .putExtra("kunjungan_radius", p.radius_m)
        )
    }

    private fun toast(m: String) = Toast.makeText(this, m, Toast.LENGTH_LONG).show()
}
