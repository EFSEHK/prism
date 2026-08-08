# Build the single production release APK.
# Output: mobile/dist/sap-efsc-{version}.apk  (version from app.json)
# Usage:  .\rebuild-apks.ps1
#     or: npm run build:apk

$ErrorActionPreference = 'Stop'
$mobile = Split-Path -Parent $PSScriptRoot
$android = Join-Path $mobile 'android'
$dist = Join-Path $mobile 'dist'
$apkSrc = Join-Path $android 'app\build\outputs\apk\release\app-release.apk'
$appJson = Get-Content (Join-Path $mobile 'app.json') -Raw | ConvertFrom-Json
$version = [string]$appJson.expo.version
if (-not $version) { throw 'app.json expo.version is missing' }

$apkName = "sap-efsc-$version.apk"
$prodUrl = 'https://sap-api.innovisiq.com/api'

$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:ANDROID_HOME = Join-Path $env:LOCALAPPDATA 'Android\Sdk'
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME

New-Item -ItemType Directory -Force -Path $dist | Out-Null

$envDev = Join-Path $mobile '.env'
$envDevBak = Join-Path $mobile '.env.dev.bak'

function Restore-EnvFiles {
    if ((Test-Path $envDevBak) -and -not (Test-Path $envDev)) {
        Move-Item $envDevBak $envDev -Force
    }
}

function Clear-JsBundleCache {
    # Avoid `gradlew clean` — new-arch CMake clean fails if codegen dirs are missing.
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

try {
    Write-Host "======== Building $apkName ========"
    Write-Host "EXPO_PUBLIC_API_URL=$prodUrl"
    Write-Host "version=$version"

    if (Test-Path $envDev) { Move-Item $envDev $envDevBak -Force }
    $env:EXPO_PUBLIC_API_URL = $prodUrl
    $env:EXPO_PUBLIC_API_LAN_IP = ''

    Clear-JsBundleCache

    Push-Location $android
    try {
        & .\gradlew.bat --stop | Out-Host
        & .\gradlew.bat assembleRelease --no-daemon
        if ($LASTEXITCODE -ne 0) {
            throw "gradlew failed (exit $LASTEXITCODE)"
        }
    }
    finally {
        Pop-Location
    }

    if (-not (Test-Path $apkSrc)) {
        throw "APK missing after build: $apkSrc"
    }

    Get-ChildItem $dist -Filter '*.apk' -ErrorAction SilentlyContinue |
        Remove-Item -Force

    $dest = Join-Path $dist $apkName
    Copy-Item $apkSrc $dest -Force
    $size = (Get-Item $dest).Length
    Write-Host "Copied -> $dest ($size bytes)"
}
finally {
    Restore-EnvFiles
}

Write-Host ""
Write-Host "Done. Single APK in $dist"
Get-ChildItem $dist -Filter '*.apk' | Select-Object Name, Length, LastWriteTime | Format-Table -AutoSize
