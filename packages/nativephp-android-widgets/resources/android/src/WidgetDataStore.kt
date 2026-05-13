package com.nativephp.androidwidgets

import android.content.Context
import org.json.JSONObject

object WidgetDataStore {

    private const val PREFS_NAME = "nativephp_widget_data"
    private const val KEY_DATA = "widget_data"

    fun save(context: Context, data: WidgetData) {
        val json = JSONObject().apply {
            put("title", data.title)
            put("content", data.content)
            put("badge", data.badge)
        }.toString()

        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .edit()
            .putString(KEY_DATA, json)
            .apply()
    }

    fun load(context: Context): WidgetData {
        val raw = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_DATA, null) ?: return WidgetData()

        return try {
            val json = JSONObject(raw)
            WidgetData(
                title = json.optString("title"),
                content = json.optString("content"),
                badge = json.optString("badge"),
            )
        } catch (e: Exception) {
            WidgetData()
        }
    }
}
