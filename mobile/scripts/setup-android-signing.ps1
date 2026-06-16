# Generates release keystore and keystore.properties for local APK builds.
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$credDir = Join-Path $root "credentials\android"
$keystore = Join-Path $credDir "efsc-ya-release.keystore"
$props = Join-Path $root "keystore.properties"

$storePass = "EFSC-YA-Store-2026!"
$keyPass = "EFSC-YA-Key-2026!"
$alias = "efsc-ya"
$dname = "CN=EFSC-YA, OU=School, O=EFSC-YA, L=Chakwal, ST=Punjab, C=PK"

New-Item -ItemType Directory -Force -Path $credDir | Out-Null

if (-not (Test-Path $keystore)) {
    keytool -genkeypair -v `
        -storetype PKCS12 `
        -keystore $keystore `
        -alias $alias `
        -keyalg RSA `
        -keysize 2048 `
        -validity 10000 `
        -storepass $storePass `
        -keypass $keyPass `
        -dname $dname
}

@"
storeFile=../../credentials/android/efsc-ya-release.keystore
storePassword=$storePass
keyAlias=$alias
keyPassword=$storePass
"@ | Set-Content -Path $props -Encoding UTF8

Write-Host "Keystore: $keystore"
Write-Host "Properties: $props"
Write-Host "Alias: $alias"
Write-Host "Store password: $storePass"
Write-Host "Key password: $keyPass"
