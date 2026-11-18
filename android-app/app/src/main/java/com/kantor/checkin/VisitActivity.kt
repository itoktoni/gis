package com.kantor.checkin

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Location
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
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.setOnRefreshListener { load() }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), 1)
        } else load()
    }

    override fun onRequestPermissionsResult(rc: Int, p: Array<out String>, r: IntArray) {
        super.onRequestPermissionsResult(rc, p, r)
        load()
    }

    private fun load() {
        lifecycleScope.launch {
            try {
                myLoc = try {
                    if (androidx.core.content.ContextCompat.checkSelfPermission(
                            this@VisitActivity, android.Manifest.permission.ACCESS_FINE_LOCATION
                        ) != android.content.pm.PackageManager.PERMISSION_GRANTED
                    ) null
                    else LocationServices.getFusedLocationProviderClient(this@VisitActivity)
                        .getCurrentLocation(Priority.PRIORITY_HIGH_ACCURACY, null).resultOrNull()
                } catch (_: Exception) { null }
                val api = MainActivity().let {
                    val log = okhttp3.logging.HttpLoggingInterceptor().setLevel(okhttp3.logging.HttpLoggingInterceptor.Level.BASIC)
                    val client = okhttp3.OkHttpClient.Builder().addInterceptor(log).build()
                    val base = getSharedPreferences("cfg", MODE_PRIVATE).getString("base_url", MainActivity.DEFAULT_URL)!!.trim().let { if (it.endsWith("/")) it else "$it/" }
                    retrofit2.Retrofit.Builder().baseUrl(base).client(client)
                        .addConverterFactory(retrofit2.converter.gson.GsonConverterFactory.create(com.google.gson.Gson())).build()
                        .create(ApiService::class.java)
                }
                val res = api.visitPoints("Bearer ${token()}", null)
                tvInfo.text = "Titik hari ini (${res.date}): ${res.data.size}"
                render(res.data)
            } catch (e: Exception) { if (e !is kotlinx.coroutines.CancellationException) toast("Gagal muat titik: ${e.message}") }
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
            val inRange = d != null && d <= p.radius_m
            listBox.addView(visitCard(p, d, inRange))
        }
        findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(R.id.swipe)?.isRefreshing = false
    }

    private fun dp(v: Int) = (v * resources.displayMetrics.density).toInt()

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
        box.addView(infoRow("Radius", "${p.radius_m} m"))
        box.addView(infoRow("Jarak dari saya", if (d == null) "GPS null" else "±%.0f m".format(d)))
        box.addView(infoRow("Koordinat", "%.6f, %.6f".format(p.latitude, p.longitude)))
        box.addView(statusBadge(if (d == null) "Menunggu GPS..." else if (inRange) "✅ Dalam jangkauan" else "⚠️ Di luar jangkauan"))

        // Tombol posisi saya (di atas map card)
        val btnPos = Button(this).apply {
            text = "📍 Posisi Saya"
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply { topMargin = dp(8) }
            setOnClickListener { centerOnMe(cardMap) }
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

    /** Map kecil per card: pin titik + radius + posisi saya. Kamera dikunci di titik. */
    private fun buildCardMap(p: VisitPoint): MapView {
        val mv = MapView(this)
        mv.layoutParams = LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT, dp(220)
        ).apply { topMargin = dp(8); bottomMargin = dp(4) }
        mv.setTileSource(TileSourceFactory.MAPNIK)
        mv.setMultiTouchControls(true)
        mv.controller.setZoom(16.0)
        mv.controller.setCenter(GeoPoint(p.latitude, p.longitude))
        Marker(mv).apply {
            position = GeoPoint(p.latitude, p.longitude)
            title = p.name
            mv.overlays.add(this)
        }
        Polygon().apply {
            setPoints(Polygon.pointsAsCircle(GeoPoint(p.latitude, p.longitude), p.radius_m.toDouble()))
            mv.overlays.add(this)
        }
        myLoc?.let { addMeMarker(mv, it) }
        mv.invalidate()
        cardMaps.add(mv)
        return mv
    }

    private fun addMeMarker(mv: MapView, loc: Location) {
        (mv.tag as? Marker)?.let { mv.overlays.remove(it) }
        Marker(mv).apply {
            position = GeoPoint(loc.latitude, loc.longitude)
            title = "Posisi saya"
            setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_CENTER)
            mv.overlays.add(this)
            mv.tag = this
        }
        mv.invalidate()
    }

    /** Geser map card ke posisi HP saat ini. */
    private fun centerOnMe(mv: MapView) {
        if (androidx.core.content.ContextCompat.checkSelfPermission(
                this, android.Manifest.permission.ACCESS_FINE_LOCATION
            ) != android.content.pm.PackageManager.PERMISSION_GRANTED
        ) {
            toast("Berikan izin lokasi dulu")
            return
        }
        lifecycleScope.launch {
            try {
                val loc = LocationServices.getFusedLocationProviderClient(this@VisitActivity)
                    .getCurrentLocation(Priority.PRIORITY_HIGH_ACCURACY, null).resultOrNull()
                if (loc == null) { toast("GPS null — coba ke area terbuka"); return@launch }
                myLoc = loc
                addMeMarker(mv, loc)
                mv.controller.animateTo(GeoPoint(loc.latitude, loc.longitude))
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
        )
    }

    private fun toast(m: String) = Toast.makeText(this, m, Toast.LENGTH_LONG).show()
}
