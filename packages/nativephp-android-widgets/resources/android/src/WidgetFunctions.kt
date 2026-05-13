package com.nativephp.androidwidgets

import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

object WidgetFunctions {

    class Update(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val title = parameters["title"] as? String ?: ""
            val content = parameters["content"] as? String ?: ""
            val badge = parameters["badge"] as? String ?: ""

            WidgetDataStore.save(activity, WidgetData(title = title, content = content, badge = badge))
            NativeWidgetProvider.requestUpdate(activity)

            return BridgeResponse.success(emptyMap<String, Any>())
        }
    }
}
