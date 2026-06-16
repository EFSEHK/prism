# Patches android/app/build.gradle to use keystore.properties for release signing.
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$gradle = Join-Path $root "android\app\build.gradle"
$props = Join-Path $root "keystore.properties"

if (-not (Test-Path $gradle)) {
    throw "Run expo prebuild first. Missing $gradle"
}
if (-not (Test-Path $props)) {
    throw "Run setup-android-signing.ps1 first."
}

$content = Get-Content $gradle -Raw

if ($content -match "efsc-ya-release-signing") {
    Write-Host "Signing config already applied."
    exit 0
}

$signingBlock = @'

def keystorePropertiesFile = rootProject.file("../keystore.properties")
def keystoreProperties = new Properties()
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

'@

$releaseSigning = @'

        release { // efsc-ya-release-signing
            if (keystorePropertiesFile.exists()) {
                storeFile file(keystoreProperties['storeFile'])
                storePassword keystoreProperties['storePassword']
                keyAlias keystoreProperties['keyAlias']
                keyPassword keystoreProperties['keyPassword']
            }
        }

'@

$content = $content -replace "android \{", "android {$signingBlock"
$content = $content -replace "signingConfigs \{", "signingConfigs {$releaseSigning"

if ($content -notmatch "signingConfig signingConfigs.release") {
    $content = $content -replace "buildTypes \{\s*release \{", "buildTypes {`n        release {`n            signingConfig signingConfigs.release"
}

Set-Content -Path $gradle -Value $content -Encoding UTF8
Write-Host "Applied release signing to $gradle"
