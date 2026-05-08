package app.prestaedit.alys.filepicker

import android.os.Handler
import android.os.Looper
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

object FilePickerFunctions {

    class Pick(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val mimeType = parameters["mime"] as? String ?: "*/*"
            val eventClass = parameters["event"] as? String ?: FilePickerCoordinator.DEFAULT_EVENT

            Handler(Looper.getMainLooper()).post {
                val coordinator = FilePickerCoordinator.install(activity)
                coordinator.launchPicker(arrayOf(mimeType), eventClass)
            }

            return emptyMap()
        }
    }
}
