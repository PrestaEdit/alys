package com.nativephp.androidwidgets

import android.appwidget.AppWidgetManager
import android.appwidget.AppWidgetProvider
import android.content.ComponentName
import android.content.Context
import android.view.View
import android.widget.RemoteViews

class NativeWidgetProvider : AppWidgetProvider() {

    override fun onUpdate(
        context: Context,
        appWidgetManager: AppWidgetManager,
        appWidgetIds: IntArray,
    ) {
        val data = WidgetDataStore.load(context)
        for (id in appWidgetIds) {
            updateWidget(context, appWidgetManager, id, data)
        }
    }

    private fun updateWidget(
        context: Context,
        manager: AppWidgetManager,
        widgetId: Int,
        data: WidgetData,
    ) {
        val views = RemoteViews(context.packageName, R.layout.nativephp_widget_layout)
        views.setTextViewText(R.id.nativephp_widget_title, data.title)
        views.setTextViewText(R.id.nativephp_widget_content, data.content)

        if (data.badge.isNotEmpty()) {
            views.setTextViewText(R.id.nativephp_widget_badge, data.badge)
            views.setViewVisibility(R.id.nativephp_widget_badge, View.VISIBLE)
        } else {
            views.setViewVisibility(R.id.nativephp_widget_badge, View.GONE)
        }

        manager.updateAppWidget(widgetId, views)
    }

    companion object {
        fun requestUpdate(context: Context) {
            val manager = AppWidgetManager.getInstance(context)
            val ids = manager.getAppWidgetIds(
                ComponentName(context, NativeWidgetProvider::class.java),
            )
            if (ids.isNotEmpty()) {
                NativeWidgetProvider().onUpdate(context, manager, ids)
            }
        }
    }
}
