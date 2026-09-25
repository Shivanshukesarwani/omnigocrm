# Call Recording

The Android app starts a foreground tracking service before launching the native phone dialer. When the device reports an active call, the service attempts to record through Android MediaRecorder.

The recording is stored in the app private directory, then uploaded through the authenticated CRM API into Laravel private storage.

The web recording route is restricted to Super Admin and Admin. The access is also written to the audit log.

Important platform limitation: Android cellular call recording depends on device, OEM, carrier and OS behavior. Some phones expose only one side of a call or block recording entirely. OmniGoCRM therefore treats recording as best-effort and still logs the call when recording is unavailable.

Production deployment must test the exact phone models used by sales staff and comply with applicable consent, privacy and recording laws.
