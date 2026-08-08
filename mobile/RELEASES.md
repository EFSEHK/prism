# EFSC-YA Mobile — Builds & Updates

## Signing key (save this securely)

| Item | Value |
|------|-------|
| Keystore file | `mobile/credentials/android/efsc-ya-release.keystore` |
| Alias | `efsc-ya` |
| Store password | `EFSC-YA-Store-2026!` |
| Key password | `EFSC-YA-Store-2026!` (PKCS12 — same as store password) |
| Validity | 10,000 days |
| DN | `CN=EFSC-YA, OU=School, O=EFSC-YA, L=Chakwal, ST=Punjab, C=PK` |

**Important:** Back up `efsc-ya-release.keystore` and passwords offline. All future APK updates must use this same keystore or users must uninstall before installing a new build.

---

## Build method used

**Local Gradle release build** (not EAS cloud):

- `npx expo prebuild --platform android`
- Release signing via `mobile/keystore.properties`
- `npm run build:apk` (copies the single production APK to `mobile/dist/`)

Output APK:

```
mobile/dist/sap-efsc-1.0.0.apk
```

Gradle still writes `android/app/build/outputs/apk/release/app-release.apk`; the script copies it as `sap-efsc-{version}.apk` from `app.json`.

---

## Download & share the APK (step by step)

### For developers

1. Build (or reuse) the APK at `mobile/dist/sap-efsc-1.0.0.apk`.
2. Sign in to the hidden portal: `https://sap-api.innovisiq.com/sys/portal-access`
   - Accounts: `developer@efsc-ya.com` or `superadmin@efsc-ya.com`
3. Open **Release settings** → upload APK (or confirm file is already in `api/storage/app/public/releases/`).
4. Set **version name** (`1.0.0`) and **version code** (`1`, must increase for each new APK).
5. Set **web app URL** (e.g. production Vue URL).
6. Save — the public welcome page at `https://sap-api.innovisiq.com/` shows **Download Android app**.

### For parents / staff (first install)

1. Open the school welcome page on the phone browser: `https://sap-api.innovisiq.com/` (or local dev URL).
2. Tap **Download Android app**.
3. If prompted, allow the browser to download APK files.
4. Open the downloaded file → tap **Install**.
5. If Android blocks install: Settings → Security → allow install from your browser (one-time).

### Share via USB / WhatsApp / school LAN

- Copy `sap-efsc-1.0.0.apk` to a USB drive, or
- Share the public link from the welcome page, or
- Upload through the developer portal (recommended — single source of truth).

---

## Ongoing updates

### A) JS-only changes (screens, logic, API) — OTA, no reinstall

Requires EAS Update configured (`eas init` + `updates.url` in `app.json`).

```bash
cd mobile
eas update --branch production --message "Describe change"
```

**User effect:** Next cold start downloads the new bundle silently. No install prompt.

### B) Native / version code changes — new APK

When you change `android.versionCode`, native modules, permissions, or Expo SDK:

1. Bump in `mobile/app.json`:
   - `version` (e.g. `1.0.1`)
   - `android.versionCode` (e.g. `2`)
2. Rebuild:
   ```powershell
   cd mobile
   npm run build:apk
   ```
3. Upload APK via developer portal; bump version code in the form.
4. Users with the app installed see **Update available** on launch → tap → Android install screen.

**User effect:** One tap through the system installer. Same package + same keystore = replaces app without manual uninstall.

---

## API endpoints

| Endpoint | Purpose |
|----------|---------|
| `GET /api/mobile/version` | Mobile app checks `version_code` + `apk_url` |
| `GET /` | Public welcome page with download links |
| `GET /sys/portal-access` | Hidden developer login (configurable via `DEV_PORTAL_PATH`) |

---

## Local build prerequisites (Windows)

- Android Studio (JBR Java 21): `C:\Program Files\Android\Android Studio\jbr`
- Android SDK: `%LOCALAPPDATA%\Android\Sdk`
- `org.gradle.java.home` set in `mobile/android/gradle.properties`
