plugins { id("com.android.application"); id("org.jetbrains.kotlin.android") }
android {
    namespace="com.omnigocrm"
    compileSdk=36
    defaultConfig { applicationId="com.omnigocrm"; minSdk=26; targetSdk=36; versionCode=1; versionName="1.0.0" }
    buildFeatures { buildConfig=true }
    defaultConfig {
        buildConfigField("String","API_BASE_URL","\"https://crm.example.com/api/\"")
    }
}
dependencies { implementation("org.jetbrains.kotlin:kotlin-stdlib:2.2.20") }
