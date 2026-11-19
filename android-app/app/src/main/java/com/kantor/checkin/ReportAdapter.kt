package com.kantor.checkin

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import coil.load
import java.text.SimpleDateFormat
import java.util.*

class ReportAdapter(
    private val baseUrl: String,
    private val onClick: (CheckinItem) -> Unit
) : RecyclerView.Adapter<ReportAdapter.Holder>() {

    private val items = mutableListOf<CheckinItem>()

    fun submit(list: List<CheckinItem>) {
        items.clear(); items.addAll(list); notifyDataSetChanged()
    }

    class Holder(v: View) : RecyclerView.ViewHolder(v) {
        val photo: ImageView = v.findViewById(R.id.itemPhoto)
        val time: TextView = v.findViewById(R.id.itemTime)
        val addr: TextView = v.findViewById(R.id.itemAddr)
        val status: TextView = v.findViewById(R.id.itemStatus)
    }

    override fun onCreateViewHolder(p: ViewGroup, t: Int): Holder =
        Holder(LayoutInflater.from(p.context).inflate(R.layout.item_checkin, p, false))

    override fun getItemCount() = items.size

    override fun onBindViewHolder(h: Holder, pos: Int) {
        val it = items[pos]
        h.photo.load(baseUrl + "storage/" + it.photo_path) {
            crossfade(true)
            placeholder(android.R.drawable.ic_menu_gallery)
        }
        h.time.text = prettyTime(it.created_at)
        h.addr.text = it.address ?: "%.5f, %.5f".format(it.latitude, it.longitude)
        // Hanya 2 label: VALID vs DI LUAR JANGKAUAN (fake dipetakan ke luar jangkauan).
        val isValid = it.status.equals("valid", ignoreCase = true)
        h.status.text = if (isValid) "✓ VALID" else "⚠ DI LUAR JANGKAUAN"
        val bg = h.status.background?.mutate() as? android.graphics.drawable.GradientDrawable
        bg?.setColor(
            if (isValid) androidx.core.content.ContextCompat.getColor(h.itemView.context, R.color.apn_success)
            else androidx.core.content.ContextCompat.getColor(h.itemView.context, R.color.apn_danger)
        )
        h.itemView.setOnClickListener { onClick(items[h.bindingAdapterPosition]) }
    }

    private fun prettyTime(iso: String): String = try {
        val inFmt = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
        val d = inFmt.parse(iso.substring(0, 19)) ?: Date()
        SimpleDateFormat("dd MMM yyyy HH:mm", Locale("in", "ID")).format(d)
    } catch (_: Exception) { iso }
}
