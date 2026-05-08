package app.prestaedit.alys.share

import android.content.Intent
import android.util.Log
import androidx.core.content.FileProvider
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction

object ShareFunctions {

    class File(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val filePath = parameters["filePath"] as? String
                ?: throw BridgeError.InvalidParameters("filePath is required")
            val title = parameters["title"] as? String ?: ""

            val source = java.io.File(filePath)
            if (!source.exists()) {
                throw BridgeError.ExecutionFailed("File not found: $filePath")
            }

            // Copy to cacheDir so FileProvider can always access it regardless of source path
            val cacheFile = java.io.File(activity.cacheDir, source.name)
            source.copyTo(cacheFile, overwrite = true)

            val authority = "${activity.packageName}.fileprovider"
            val uri = try {
                FileProvider.getUriForFile(activity, authority, cacheFile)
            } catch (e: Exception) {
                throw BridgeError.ExecutionFailed("FileProvider error: ${e.message}")
            }

            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                type = "application/octet-stream"
                putExtra(Intent.EXTRA_STREAM, uri)
                putExtra(Intent.EXTRA_SUBJECT, title)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }

            val chooser = Intent.createChooser(shareIntent, title)
            chooser.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)

            activity.runOnUiThread {
                activity.startActivity(chooser)
            }

            Log.d("ShareFunctions", "Shared file: ${cacheFile.absolutePath}")
            return emptyMap()
        }
    }
}
