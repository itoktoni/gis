package com.kantor.checkin

import android.content.Context
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.io.File

data class PendingItem(
    val id: String,
    val lat: Double,
    val lng: Double,
    val accuracy: Double?,
    val address: String?,
    val ts: String
)

/** Antrean offline terenkripsi: baca daftar + kirim ke server. */
object SyncHelper {

    private fun dir(ctx: Context) = File(ctx.filesDir, "pending")

    fun count(ctx: Context): Int =
        dir(ctx).listFiles { f -> f.name.endsWith(".meta.bin") }?.size ?: 0

    fun list(ctx: Context): List<PendingItem> {
        val out = mutableListOf<PendingItem>()
        val metas = dir(ctx).listFiles { f -> f.name.endsWith(".meta.bin") } ?: return out
        for (m in metas) {
            try {
                val id = m.name.removeSuffix(".meta.bin")
                val meta = org.json.JSONObject(PendingCrypto.decryptText(m.readBytes()))
                out.add(
                    PendingItem(
                        id = id,
                        lat = meta.getDouble("lat"),
                        lng = meta.getDouble("lng"),
                        accuracy = if (meta.isNull("accuracy")) null else meta.getDouble("accuracy"),
                        address = if (meta.isNull("address")) null else meta.getString("address"),
                        ts = meta.optString("ts", "-")
                    )
                )
            } catch (_: Exception) { }
        }
        return out.sortedBy { it.ts }
    }

    private fun api(base: String): ApiService {
        val b = base.trim().let { if (it.endsWith("/")) it else "$it/" }
        return Retrofit.Builder().baseUrl(b)
            .client(okhttp3.OkHttpClient.Builder().build())
            .addConverterFactory(GsonConverterFactory.create(com.google.gson.Gson()))
            .build().create(ApiService::class.java)
    }

    /** Kirim semua antrean. Return Pair(berhasil, gagal). */
    suspend fun syncAll(ctx: Context, token: String, base: String): Pair<Int, Int> =
        withContext(Dispatchers.IO) {
            var ok = 0
            var fail = 0
            val service = api(base)
            val metas = dir(ctx).listFiles { f -> f.name.endsWith(".meta.bin") } ?: emptyArray()
            for (m in metas) {
                try {
                    val id = m.name.removeSuffix(".meta.bin")
                    val meta = org.json.JSONObject(PendingCrypto.decryptText(m.readBytes()))
                    val jpg = File(dir(ctx), "$id.jpg.bin")
                    if (!jpg.exists()) { m.delete(); continue }
                    val tmp = File.createTempFile("sync_$id", ".jpg", ctx.cacheDir)
                    tmp.writeBytes(PendingCrypto.decryptBytes(jpg.readBytes()))
                    fun s(v: String) = v.toRequestBody("text/plain".toMediaType())
                    val part = okhttp3.MultipartBody.Part.createFormData(
                        "photo", "selfie.jpg", tmp.asRequestBody("image/jpeg".toMediaType()))
                    val acc = if (meta.isNull("accuracy")) null else s(meta.getDouble("accuracy").toString())
                    service.checkin(
                        "Bearer $token", part,
                        s(meta.getDouble("lat").toString()), s(meta.getDouble("lng").toString()),
                        acc, if (meta.isNull("address")) null else s(meta.getString("address")),
                        s(meta.getString("ts")), s(meta.getString("payload")), s(meta.getString("hmac"))
                    )
                    tmp.delete(); jpg.delete(); m.delete()
                    ok++
                } catch (e: retrofit2.HttpException) {
                    if (e.code() == 401) throw SecurityException("Sesi berakhir, login ulang")
                    fail++
                } catch (_: Exception) { fail++ }
            }
            Pair(ok, fail)
        }
}
