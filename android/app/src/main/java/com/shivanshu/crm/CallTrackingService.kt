package com.shivanshu.crm

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.media.MediaRecorder
import android.os.Build
import android.os.IBinder
import android.telephony.PhoneStateListener
import android.telephony.TelephonyCallback
import android.telephony.TelephonyManager
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.concurrent.Executors

class CallTrackingService : Service() {
    private var recorder: MediaRecorder? = null
    private var recordingFile: File? = null
    private var callStart: Long = 0L
    private var callEverStarted = false
    private lateinit var telephony: TelephonyManager
    private val executor = Executors.newSingleThreadExecutor()

    private var subjectType = "lead"
    private var subjectId = 0L
    private var phone = ""

    private val phoneCallback = object : TelephonyCallback(), TelephonyCallback.CallStateListener {
        override fun onCallStateChanged(state: Int) = handleState(state)
    }
    @Suppress("DEPRECATION")
    private val legacyListener = object : PhoneStateListener() {
        override fun onCallStateChanged(state: Int, phoneNumber: String?) { handleState(state) }
    }

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
        startForeground(2101, NotificationHelper.notification(this, "CRM call tracking", "Call activity is being tracked for the selected CRM record."))
        telephony = getSystemService(TELEPHONY_SERVICE) as TelephonyManager
        try {
            if (Build.VERSION.SDK_INT >= 31) telephony.registerTelephonyCallback(mainExecutor, phoneCallback)
            else telephony.listen(legacyListener, PhoneStateListener.LISTEN_CALL_STATE)
        } catch (_: SecurityException) { }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        subjectType = intent?.getStringExtra("subject_type") ?: subjectType
        subjectId = intent?.getLongExtra("subject_id", subjectId) ?: subjectId
        phone = intent?.getStringExtra("phone") ?: phone
        return START_NOT_STICKY
    }

    private fun handleState(state: Int) {
        when (state) {
            TelephonyManager.CALL_STATE_OFFHOOK -> {
                if (!callEverStarted) { callEverStarted = true; callStart = System.currentTimeMillis(); tryStartRecording() }
            }
            TelephonyManager.CALL_STATE_IDLE -> {
                if (callEverStarted) finishCall()
            }
        }
    }

    private fun tryStartRecording() {
        try {
            val dir = File(filesDir, "call-recordings")
            if (!dir.exists()) dir.mkdirs()
            val stamp = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US).format(Date())
            recordingFile = File(dir, "call_${stamp}.m4a")
            recorder = (if (Build.VERSION.SDK_INT >= 31) MediaRecorder(this) else MediaRecorder()).apply {
                setAudioSource(MediaRecorder.AudioSource.VOICE_COMMUNICATION)
                setOutputFormat(MediaRecorder.OutputFormat.MPEG_4)
                setAudioEncoder(MediaRecorder.AudioEncoder.AAC)
                setOutputFile(recordingFile!!.absolutePath)
                prepare()
                start()
            }
        } catch (_: Exception) {
            recorder?.release(); recorder = null; recordingFile = null
        }
    }

    private fun finishCall() {
        val duration = ((System.currentTimeMillis() - callStart) / 1000L).toInt().coerceAtLeast(0)
        try { recorder?.stop() } catch (_: Exception) { recordingFile?.delete() }
        recorder?.release(); recorder = null
        val file = recordingFile
        executor.execute {
            try {
                val api = ApiClient(this)
                val callId = api.logCall(subjectType, subjectId.toString(), phone, duration)
                if (callId.isNotBlank() && file?.exists() == true) {
                    api.uploadRecording(callId, file)
                    // Keep the file in the app-private directory. The app has no playback UI for sales users.
                }
            } finally {
                stopSelf()
            }
        }
    }

    override fun onDestroy() {
        try {
            if (Build.VERSION.SDK_INT >= 31) telephony.unregisterTelephonyCallback(phoneCallback)
            else @Suppress("DEPRECATION") telephony.listen(legacyListener, PhoneStateListener.LISTEN_NONE)
        } catch (_: Exception) { }
        recorder?.release(); recorder = null
        executor.shutdownNow()
        super.onDestroy()
    }
    override fun onBind(intent: Intent?): IBinder? = null

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= 26) {
            val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            manager.createNotificationChannel(NotificationChannel("crm_calls", "CRM Calls", NotificationManager.IMPORTANCE_LOW))
        }
    }
}

object NotificationHelper {
    fun notification(context: Context, title: String, text: String): android.app.Notification {
        val builder = if (Build.VERSION.SDK_INT >= 26) android.app.Notification.Builder(context, "crm_calls") else android.app.Notification.Builder(context)
        return builder.setContentTitle(title).setContentText(text).setSmallIcon(android.R.drawable.sym_action_call).setOngoing(true).build()
    }
}
