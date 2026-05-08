package app.prestaedit.alys.filepicker

import android.net.Uri
import android.provider.OpenableColumns
import android.util.Base64
import android.util.Log
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.Fragment
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject

class FilePickerCoordinator : Fragment() {

    private var pendingEventClass: String = DEFAULT_EVENT

    private val filePicker =
        registerForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
            uri ?: return@registerForActivityResult
            handleFile(uri)
        }

    private fun handleFile(uri: Uri) {
        try {
            val cr = requireContext().contentResolver
            val filename = getDisplayName(uri) ?: "file.alys"
            val bytes = cr.openInputStream(uri)?.use { it.readBytes() }
                ?: return
            val content = Base64.encodeToString(bytes, Base64.NO_WRAP)
            val payload = JSONObject().apply {
                put("filename", filename)
                put("content", content)
            }
            Log.d(TAG, "File picked: $filename (${bytes.size} bytes)")
            NativeActionCoordinator.dispatchEvent(requireActivity(), pendingEventClass, payload.toString())
        } catch (e: Exception) {
            Log.e(TAG, "Error reading file: ${e.message}", e)
        }
    }

    private fun getDisplayName(uri: Uri): String? {
        requireContext().contentResolver.query(uri, null, null, null, null)?.use { cursor ->
            val col = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (col >= 0 && cursor.moveToFirst()) return cursor.getString(col)
        }
        return uri.lastPathSegment
    }

    fun launchPicker(mimeTypes: Array<String>, eventClass: String) {
        pendingEventClass = eventClass
        filePicker.launch(mimeTypes)
    }

    companion object {
        private const val TAG = "FilePickerCoordinator"
        const val DEFAULT_EVENT = "App\\Events\\Native\\FileChosen"

        fun install(activity: FragmentActivity): FilePickerCoordinator =
            activity.supportFragmentManager.findFragmentByTag(TAG) as? FilePickerCoordinator
                ?: FilePickerCoordinator().also {
                    activity.supportFragmentManager.beginTransaction()
                        .add(it, TAG)
                        .commitNow()
                }
    }
}
