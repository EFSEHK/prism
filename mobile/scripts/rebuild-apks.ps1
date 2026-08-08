# Build release APKs for production and/or local Laragon.
# Usage: .\rebuild-apks.ps1 [-Target production|local|both]
param(
    [ValidateSet('production', 'local', 'both')]
    [string]$Target = 'both'
)

$ErrorActionPreference = 'Stop'
$mobile = Split-Path -Parent $PSScriptRoot
$android = Join-Path $mobile 'android'
$dist = Join-Path $mobile 'dist'
$apkSrc = Join-Path $android 'app\build\outputs\apk\release\app-release.apk'

$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:ANDROID_HOME = Join-Path $env:LOCALAPPDATA 'Android\Sdk'
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME

New-Item -ItemType Directory -Force -Path $dist | Out-Null

$envDev = Join-Path $mobile '.env'
$envProd = Join-Path $mobile '.env.production'
$envDevBak = Join-Path $mobile '.env.dev.bak'
$envProdBak = Join-Path $mobile '.env.production.bak'

function Restore-EnvFiles {
    if ((Test-Path $envDevBak) -and -not (Test-Path $envDev)) {
        Move-Item $envDevBak $envDev -Force
    }
    if ((Test-Path $envProdBak) -and -not (Test-Path $envProd)) {
        Move-Item $envProdBak $envProd -Force
    }
}

function Clear-JsBundleCache {
    # Avoid `gradlew clean` — new-arch CMake clean fails if codegen dirs are missing.
    # Still drop JS/Hermes outputs so EXPO_PUBLIC_* is re-inlined for each variant.
    $paths = @(
        (Join-Path $android 'app\build\generated\assets'),
        (Join-Path $android 'app\build\intermediates\sourcemaps'),
        (Join-Path $android 'app\build\intermediates\sourcemap-js'),
        (Join-Path $android 'app\src\main\assets\index.android.bundle'),
        (Join-Path $android 'app\build\outputs\apk\release')
    )
    foreach ($p in $paths) {
        if (Test-Path $p) { Remove-Item -Recurse -Force $p }
    }
    Get-ChildItem $env:TEMP -Filter 'metro-*' -ErrorAction SilentlyContinue |
        Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    Get-ChildItem $env:TEMP -Filter 'haste-map-*' -ErrorAction SilentlyContinue |
        Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    $metroCache = Join-Path $mobile 'node_modules\.cache'
    if (Test-Path $metroCache) { Remove-Item -Recurse -Force $metroCache }
}

function Build-Apk([string]$label, [string]$url, [string]$lanIp, [string]$destName) {
    Write-Host ""
    Write-Host "======== Building $label ========"
    Write-Host "EXPO_PUBLIC_API_URL=$url"
    Write-Host "EXPO_PUBLIC_API_LAN_IP=$lanIp"

    $env:EXPO_PUBLIC_API_URL = $url
    $env:EXPO_PUBLIC_API_LAN_IP = $lanIp

    Clear-JsBundleCache

    Push-Location $android
    try {
        & .\gradlew.bat --stop | Out-Host
        & .\gradlew.bat assembleRelease --no-daemon
        if ($LASTEXITCODE -ne 0) {
            throw "gradlew failed for $label (exit $LASTEXITCODE)"
        }
    }
    finally {
        Pop-Location
    }

    if (-not (Test-Path $apkSrc)) {
        throw "APK missing after $label build: $apkSrc"
    }

    $dest = Join-Path $dist $destName
    Copy-Item $apkSrc $dest -Force
    $size = (Get-Item $dest).Length
    Write-Host "Copied $label APK -> $dest ($size bytes)"
}

try {
    if ($Target -in @('production', 'both')) {
        if (Test-Path $envDev) { Move-Item $envDev $envDevBak -Force }
        Build-Apk 'production' 'https://sap-api.innovisiq.com/api' '' 'EFSC-YA-production.apk'
        Copy-Item (Join-Path $dist 'EFSC-YA-production.apk') (Join-Path $dist 'EFSC-YA-1.0.0.apk') -Force
        if (Test-Path $envDevBak) { Move-Item $envDevBak $envDev -Force }
    }

    if ($Target -in @('local', 'both')) {
        if (Test-Path $envProd) { Move-Item $envProd $envProdBak -Force }
        Build-Apk 'local' 'http://prism.test/api' '192.168.18.15' 'EFSC-YA-local.apk'
        if (Test-Path $envProdBak) { Move-Item $envProdBak $envProd -Force }
    }
}
finally {
    Restore-EnvFiles
}

Write-Host ""
Write-Host "Done. APKs in $dist"
Get-ChildItem $dist -Filter '*.apk' | Select-Object Name, Length, LastWriteTime | Format-Table -AutoSize
