package com.omnigocrm
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.media.MediaRecorder
import android.os.Build
import android.os.IBinder
import android.telephony.TelephonyCallback
import android.telephony.TelephonyManager
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class CallRecordingService:Service(){
 private var recorder:MediaRecorder?=null
 private var file:File?=null
 private var started=0L
 private var done=false
 private var subjectType="lead"
 private var subjectId=0L
 private var phone=""
 private lateinit var tm:TelephonyManager

 private val callback=object:TelephonyCallback(),TelephonyCallback.CallStateListener{
  override fun onCallStateChanged(state:Int){when(state){
   TelephonyManager.CALL_STATE_OFFHOOK->if(started==0L){started=System.currentTimeMillis();startRecording()}
   TelephonyManager.CALL_STATE_IDLE->if(started>0L&&!done){done=true;finishCall()}
  }}
 }
 override fun onCreate(){super.onCreate();channel();startForeground(7001,notification());tm=getSystemService(TELEPHONY_SERVICE) as TelephonyManager;try{if(Build.VERSION.SDK_INT>=31)tm.registerTelephonyCallback(mainExecutor,callback)}catch(_:Exception){}}
 override fun onStartCommand(i:Intent?,flags:Int,startId:Int):Int{subjectType=i?.getStringExtra("subject_type")?:subjectType;subjectId=i?.getLongExtra("subject_id",subjectId)?:subjectId;phone=i?.getStringExtra("phone")?:phone;return START_NOT_STICKY}
 private fun startRecording(){try{val d=File(filesDir,"call-recordings");d.mkdirs();file=File(d,"call_"+SimpleDateFormat("yyyyMMdd_HHmmss",Locale.US).format(Date())+".m4a");recorder=MediaRecorder(this).apply{setAudioSource(MediaRecorder.AudioSource.VOICE_COMMUNICATION);setOutputFormat(MediaRecorder.OutputFormat.MPEG_4);setAudioEncoder(MediaRecorder.AudioEncoder.AAC);setOutputFile(file!!.absolutePath);prepare();start()}}catch(_:Exception){recorder?.release();recorder=null;file=null}}
 private fun finishCall(){val duration=((System.currentTimeMillis()-started)/1000).toInt();try{recorder?.stop()}catch(_:Exception){file?.delete()};recorder?.release();recorder=null;Thread{try{ApiClient(this).logCall(subjectType,subjectId,phone,duration,file)}catch(_:Exception){}finally{stopSelf()}}.start()}
 override fun onDestroy(){try{if(Build.VERSION.SDK_INT>=31)tm.unregisterTelephonyCallback(callback)}catch(_:Exception){};recorder?.release();super.onDestroy()}
 override fun onBind(i:Intent?):IBinder?=null
 private fun channel(){if(Build.VERSION.SDK_INT>=26){(getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager).createNotificationChannel(NotificationChannel("crm","CRM Calls",NotificationManager.IMPORTANCE_LOW))}}
 private fun notification():Notification{val b=if(Build.VERSION.SDK_INT>=26)Notification.Builder(this,"crm") else Notification.Builder(this);return b.setContentTitle("OmniGoCRM").setContentText("Call activity tracking is active").setSmallIcon(android.R.drawable.sym_action_call).setOngoing(true).build()}
}