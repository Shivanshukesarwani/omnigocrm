plugins { id("com.android.application"); id("org.jetbrains.kotlin.android") }
android {
    namespace="com.omnigocrm"
    compileSdk=36
    defaultConfig { applicationId="com.omnigocrm"; minSdk=26; targetSdk=36; versionCode=1; versionName="1.0.0" }
}
dependencies { implementation("org.jetbrains.kotlin:kotlin-stdlib:2.2.20") }
