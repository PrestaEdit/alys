package app.prestaedit.alys.battery

import android.content.Intent
import android.net.Uri
import android.os.Handler
import android.os.Looper
import android.os.PowerManager
import android.provider.Settings
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

object BatteryFunctions {

    class RequestUnrestricted(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val pm = activity.getSystemService(android.content.Context.POWER_SERVICE) as PowerManager
            if (pm.isIgnoringBatteryOptimizations(activity.packageName)) {
                return mapOf("status" to "already_unrestricted")
            }

            Handler(Looper.getMainLooper()).post {
                val intent = Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS).apply {
                    data = Uri.parse("package:${activity.packageName}")
                }
                activity.startActivity(intent)
            }

            return mapOf("status" to "requested")
        }
    }

    class CheckStatus(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val pm = activity.getSystemService(android.content.Context.POWER_SERVICE) as PowerManager
            return mapOf("unrestricted" to pm.isIgnoringBatteryOptimizations(activity.packageName))
        }
    }
}
