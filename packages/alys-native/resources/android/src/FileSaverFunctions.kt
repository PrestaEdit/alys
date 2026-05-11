package app.prestaedit.alys.filesaver

import android.os.Handler
import android.os.Looper
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

object FileSaverFunctions {

    class Save(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val filePath = parameters["filePath"] as? String
                ?: return mapOf("error" to "filePath is required")
            val filename = parameters["filename"] as? String
                ?: filePath.substringAfterLast('/')
            val eventClass = parameters["event"] as? String
                ?: FileSaverCoordinator.DEFAULT_EVENT

            Handler(Looper.getMainLooper()).post {
                val coordinator = FileSaverCoordinator.install(activity)
                coordinator.launchSaver(filePath, filename, eventClass)
            }

            return emptyMap()
        }
    }
}
