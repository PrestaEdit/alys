package app.prestaedit.alys.filesaver

import android.net.Uri
import android.util.Log
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.Fragment
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject
import java.io.File

class FileSaverCoordinator : Fragment() {

    private var pendingFilePath: String = ""
    private var pendingEventClass: String = DEFAULT_EVENT

    private val fileSaver =
        registerForActivityResult(ActivityResultContracts.CreateDocument("application/octet-stream")) { uri ->
            if (uri == null) {
                Log.d(TAG, "Save cancelled by user")
                dispatchResult(success = false, error = "cancelled")
                return@registerForActivityResult
            }
            writeFile(uri)
        }

    private fun writeFile(uri: Uri) {
        try {
            val sourceFile = File(pendingFilePath)
            if (!sourceFile.exists()) {
                Log.e(TAG, "Source file not found: $pendingFilePath")
                dispatchResult(success = false, error = "Source file not found")
                return
            }
            requireContext().contentResolver.openOutputStream(uri)?.use { out ->
                sourceFile.inputStream().use { inp -> inp.copyTo(out) }
            }
            Log.d(TAG, "File saved successfully: ${sourceFile.name}")
            dispatchResult(success = true)
        } catch (e: Exception) {
            Log.e(TAG, "Error writing file: ${e.message}", e)
            dispatchResult(success = false, error = e.message ?: "Unknown error")
        }
    }

    private fun dispatchResult(success: Boolean, error: String = "") {
        val payload = JSONObject().apply {
            put("success", success)
            if (error.isNotEmpty()) put("error", error)
        }
        NativeActionCoordinator.dispatchEvent(requireActivity(), pendingEventClass, payload.toString())
    }

    fun launchSaver(filePath: String, filename: String, eventClass: String) {
        pendingFilePath = filePath
        pendingEventClass = eventClass
        fileSaver.launch(filename)
    }

    companion object {
        private const val TAG = "FileSaverCoordinator"
        const val DEFAULT_EVENT = "App\\Events\\Native\\FileSaved"

        fun install(activity: FragmentActivity): FileSaverCoordinator =
            activity.supportFragmentManager.findFragmentByTag(TAG) as? FileSaverCoordinator
                ?: FileSaverCoordinator().also {
                    activity.supportFragmentManager.beginTransaction()
                        .add(it, TAG)
                        .commitNow()
                }
    }
}
