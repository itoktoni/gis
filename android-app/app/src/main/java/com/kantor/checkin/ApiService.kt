package com.kantor.checkin

import com.google.gson.annotations.SerializedName
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.*

data class LoginReq(val email: String, val password: String)
data class LoginUser(val id: Int, val name: String, val email: String, val role: String = "")
data class LoginRes(val access_token: String, val token_type: String? = null, val user: LoginUser? = null, val checkin_secret: String? = null)
data class SecretRes(val checkin_secret: String)
data class CheckinRes(
    val checkin_id: String,
    val signature_id: String,
    val validate_url: String,
    val photo_url: String,
    val status: String,
    val authentic: Boolean = true,
    val failures: List<String> = emptyList()
)

// Laporan monitoring: waktu + alamat + foto + status (valid / di luar jangkauan).
// Server mengirim status: valid | invalid | fake — fake TIDAK ditampilkan apa adanya,
// melainkan dipetakan ke "di luar jangkauan" agar laporan hanya punya 2 label.
data class CheckinItem(
    val id: String,
    val latitude: Double,
    val longitude: Double,
    val address: String?,
    val client_timestamp: String?,
    val photo_path: String,
    val created_at: String,
    val status: String? = null,
    val failure_reason: String? = null
)
data class PagedCheckins(val data: List<CheckinItem>)

// Statistik check-in per rentang tanggal (dashboard)
data class StatsRes(
    val from: String? = null,
    val to: String? = null,
    val total: Int = 0,
    val valid: Int = 0,
    val invalid: Int = 0,
    val fake: Int = 0
)

// Titik kunjungan harian (dari master Objek kebun)
data class VisitPoint(
    val id: String,
    val user_id: Int = 0,
    @SerializedName("date") val visit_date: String = "",
    val name: String,
    val latitude: Double,
    val longitude: Double,
    val radius_m: Int = 100,
    val status: String = "pending",
    val checkin_id: String? = null,
    val objek: String? = null,
    val kebun: String? = null,
    val polygon: String? = null
)
data class VisitPointsRes(val date: String, val data: List<VisitPoint>)

// Config pengingat check-in dari server (dijadwalkan jadi notifikasi lokal)
data class ReminderConfig(
    val enabled: Boolean = true,
    val times: List<String> = emptyList(),
    val interval_hours: Double = 3.0,
    val min_interval_hours: Double = 3.0,
    val max_per_day: Int = 3,
    val truncated_by_max: Boolean = false,
    val total_slots_in_window: Int = 0,
    val start: String = "06:00",
    val end: String = "17:00",
    val title: String = "",
    val message: String = ""
)

interface ApiService {
    @FormUrlEncoded
    @POST("api/login")
    suspend fun login(@Field("email") email: String, @Field("password") password: String): LoginRes

    @GET("api/absensi/secret")
    suspend fun secret(@Header("Authorization") token: String): SecretRes

    @Multipart
    @POST("api/absensi/checkin")
    suspend fun checkin(
        @Header("Authorization") token: String,
        @Part photo: MultipartBody.Part,
        @Part("latitude") lat: RequestBody,
        @Part("longitude") lng: RequestBody,
        @Part("accuracy") acc: RequestBody?,
        @Part("address") addr: RequestBody?,
        @Part("client_timestamp") ts: RequestBody,
        @Part("payload") payload: RequestBody,
        @Part("hmac") hmac: RequestBody,
        @Part("kunjungan_id") kunjunganId: RequestBody? = null
    ): CheckinRes

    @GET("api/absensi/checkins")
    suspend fun checkins(
        @Header("Authorization") token: String,
        @Query("from") from: String?,
        @Query("to") to: String?
    ): PagedCheckins

    @GET("api/absensi/kunjungan")
    suspend fun visitPoints(
        @Header("Authorization") token: String,
        @Query("date") date: String?
    ): VisitPointsRes

    @GET("api/absensi/stats")
    suspend fun stats(
        @Header("Authorization") token: String,
        @Query("from") from: String?,
        @Query("to") to: String?
    ): StatsRes

    @GET("api/absensi/reminder-config")
    suspend fun reminderConfig(
        @Header("Authorization") token: String
    ): ReminderConfig
}
