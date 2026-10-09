package com.reco.reco_flutter

import android.content.Intent
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.embedding.android.FlutterActivity
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val channelName = "reco_flutter/app_shortcuts"
    private val shortcutExtra = "shortcut_action"
    private var shortcutsChannel: MethodChannel? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        shortcutsChannel = MethodChannel(flutterEngine.dartExecutor.binaryMessenger, channelName)
        shortcutsChannel?.setMethodCallHandler { call, result ->
            when (call.method) {
                "getInitialShortcutAction" -> result.success(extractShortcutAction(intent))
                else -> result.notImplemented()
            }
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        extractShortcutAction(intent)?.let { action ->
            shortcutsChannel?.invokeMethod("shortcutAction", action)
        }
    }

    private fun extractShortcutAction(intent: Intent?): String? {
        val extraAction = intent?.getStringExtra(shortcutExtra)
        if (!extraAction.isNullOrBlank()) {
            return extraAction
        }

        val data = intent?.data ?: return null
        if (data.scheme != "reco" || data.host != "shortcut") {
            return null
        }
        return data.lastPathSegment
    }
}
