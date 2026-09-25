package com.omnigocrm
import android.app.*
import android.content.Intent
import android.media.MediaRecorder
import android.os.*
import android.telephony.TelephonyCallback
import android.telephony.TelephonyManager
import java.io.File
class CallRecordingService:Service(){
 private var recorder:MediaRecorder?=null;private var file:File?=null;private var started=0L;private var finished=false
 private var type="lead";private var id=0L;private var phone=""
 private lateinit var tm:TelephonyManager
 private val callback=object:TelephonyCallback(),TelephonyCallback.CallStateListener{override fun onCallStateChanged(state:Int){when(state){TelephonyManager.CALL_STATE_OFFHOOK->if(started==0L){started=System.currentTimeMillis();startRecording()};TelephonyManager.CALL_STATE_IDLE->if(started>0&&!finished){finished=true;finishCall()}}}}
 override fun onCreate(){super.onCreate();tm=getSystemService(TELEPHONY_SERVICE) as TelephonyManager;if(Build.VERSION.SDK_INT>=31)try{tm.registerTelephonyCallback(mainExecutor,callback)}catch(_:Exception){};createChannel();startForeground(7001,notification())}
 override fun onStartCommand(i:Intent?,flags:Int,startId:Int):Int{type=i?.getStringExtra("subject_type")?:type;id=i?.getLongExtra("subject_id",id)?:id;phone=i?.getStringExtra("phone")?:phone;return START_NOT_STICKY}
 private fun startRecording(){try{val d=File(filesDir,"call-recordings");d.mkdirs();file=File(d,"call_"+System.currentTimeMillis()+".m4a");recorder=MediaRecorder(this).apply{setAudioSource(MediaRecorder.AudioSource.VOICE_COMMUNICATION);setOutputFormat(MediaRecorder.OutputFormat.MPEG_4);setAudioEncoder(MediaRecorder.AudioEncoder.AAC);setOutputFile(file!!.absolutePath);prepare();start()}}catch(_:Exception){recorder?.release();recorder=null;file=null}}
 private fun finishCall(){val duration=((System.currentTimeMillis()-started)/1000).toInt();try{recorder?.stop()}catch(_:Exception){file?.delete()};recorder?.release();recorder=null;Thread{try{ApiClient(this).run{}}catch(_:Exception){}finally{stopSelf()}}.start()}
 private fun createChannel(){if(Build.VERSION.SDK_INT>=26){(getSystemService(NOTIFICATION_SERVICE) as NotificationManager).createNotificationChannel(NotificationChannel("calls","CRM Calls",NotificationManager.IMPORTANCE_LOW))}}
 private fun notification():Notification{val b=if(Build.VERSION.SDK_INT>=26)Notification.Builder(this,"calls") else Notification.Builder(this);return b.setContentTitle("OmniGoCRM").setContentText("Call activity tracking is active").setSmallIcon(android.R.drawable.sym_action_call).build()}
 override fun onDestroy(){try{if(Build.VERSION.SDK_INT>=31)tm.unregisterTelephonyCallback(callback)}catch(_:Exception){};recorder?.release();super.onDestroy()}
 override fun onBind(i:Intent?):IBinder?=null
}