# EFSC-YA — Startup Guide (Web & Mobile)

How to run the **web** and **mobile** apps locally, how to open the mobile app in a **browser (web view)**, and how to **test on a real phone** using the standalone APK (not Expo Go).

Prerequisites: **Laragon** (or PHP + MySQL), **Node.js**, and the **API** running so `/api` responds.

---

## 0. Start the API first

Both web and mobile talk to the Laravel API.

```bash
cd api
composer install
cp .env.example .env
# Set DB credentials in .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
```

**Laragon (recommended):** open `http://prism.test` — API base is `http://prism.test/api`.

**Or Artisan serve:**

```bash
php artisan serve
# API base: http://127.0.0.1:8000/api
```

Seeded demo users (see root `ReadMe.md`). Example parent: `parent@efsc-ya.com` / `Test.123`.

---

## 1. Launch the Web app

```bash
cd web
npm install
cp .env.example .env
npm run dev
```

Open **http://localhost:5173**.

- With empty `VITE_API_URL`, Vite proxies `/api` to Laragon (`http://prism.test`) or whatever you set in `VITE_API_PROXY_TARGET`.
- Login at `/login`, then staff land on the dashboard / portal; parents use the learner home.

**Production build (optional):**

```bash
cd web
cp .env.production.example .env.production
npm run build
# Deploy web/dist/
```

---

## 2. Launch the Mobile app in web view (browser)

Use this for fast UI / login / dashboard checks on your PC. No phone and no APK required.

```bash
cd mobile
npm install
cp .env.example .env
```

In `.env` for browser use:

```env
EXPO_PUBLIC_API_URL=http://prism.test/api
```

You do **not** need `EXPO_PUBLIC_API_LAN_IP` for the browser — the PC can resolve `prism.test`.

Then:

```bash
npm run web
```

Expo opens the app in the browser (typically **http://localhost:8081**, or the port printed in the terminal).

**What works well in web view:** login, menus, dashboards, most API-backed screens.

**What does not fully apply in web view:** APK download/install, Android install permissions, real-device camera/push behavior. For those, use the APK steps below.

> We do **not** use Expo Go for testing or distribution. Development UI can run in the browser (`npm run web`); phones use the **installed APK**.

---

## 3. Test on a real Android phone (APK — first install)

Production / device testing uses a **standalone Android APK** (`com.school.efscya`), not Expo Go.

### 3.1 Build the APK (developers)

One-time / when native code or version changes:

```powershell
cd mobile
cp .env.production.example .env.production
# Or set EXPO_PUBLIC_API_URL to your API, e.g. https://sap-api.innovisiq.com/api
# For local device testing against Laragon, use your PC Wi‑Fi IP (see §3.3)

npx expo prebuild --platform android
# Configure release signing (see mobile/RELEASES.md and scripts/)
npm run build:apk
```

Output:

```
mobile/android/app/build/outputs/apk/release/app-release.apk
```

Optional copy for sharing:

```
mobile/dist/EFSC-YA-1.0.0.apk
```

Upload the APK through the **developer release portal** so the public welcome page and `GET /api/mobile/version` point at it:

- Portal: `https://sap-api.innovisiq.com/sys/portal-access` (or your local API equivalent)
- Set **version name**, **version code** (must increase each release), and save

### 3.2 First-time install on the phone

1. On the phone browser, open the school welcome page  
   - Production: `https://sap-api.innovisiq.com/`  
   - Or your local/public download link after portal upload
2. Tap **Download Android app**.
3. Allow the browser to download APK files if asked.
4. Open the downloaded file → tap **Install**.
5. If Android blocks it: Settings → allow install from that browser / unknown sources (one-time).
6. Open **EFSC-YA** and log in.

You can also copy the APK via USB / WhatsApp / school LAN instead of the download link — same install flow.

### 3.3 Point a local APK at Laragon (optional)

Phones cannot resolve `prism.test` unless hosts are edited. For a **local** API:

```env
EXPO_PUBLIC_API_URL=http://prism.test/api
EXPO_PUBLIC_API_LAN_IP=YOUR_PC_WIFI_IP
```

Get the IP with `ipconfig` (same Wi‑Fi as the phone). Rebuild the APK after changing env. The app calls the LAN IP and sends `Host: prism.test` so Laragon’s vhost matches.

Android emulator can omit `LAN_IP` (uses `10.0.2.2` automatically).

---

## 4. In-app updates (after the first install)

Once the APK is installed, users do **not** need Expo Go and usually do **not** need to hunt for a new download link manually.

### How it works

On app launch (Android):

1. App calls **`GET /api/mobile/version`**.
2. If the server’s **`version_code`** is higher than the installed build, the app shows **Update available**.
3. User taps **Update** → APK downloads → system install screen opens.
4. Same package name + same signing keystore → Android **replaces** the old app (no uninstall required).

If download fails, the app can fall back to opening the **`apk_url`** in the browser.

### Developer steps for a new APK update

1. Bump in `mobile/app.json`:
   - `version` (e.g. `1.0.1`)
   - `android.versionCode` (e.g. `2`) — **must** increase
2. Rebuild release APK (same keystore as the first install — see `mobile/RELEASES.md`).
3. Upload via developer portal; set the new version name / version code / APK file.
4. Ask testers to open the app (cold start). They should see the in-app update prompt.

### Optional: JS-only OTA (no reinstall)

For screen/logic-only changes, `expo-updates` can apply a JS bundle silently if EAS Update is configured (`updates.url` in `app.json`). That is separate from the APK in-app installer. Native changes, permissions, or SDK bumps still need a **new APK** and version code bump.

---

## Quick reference

| Goal | Command / action |
|------|------------------|
| Run API | Laragon `prism.test` or `php artisan serve` in `api/` |
| Run Web | `cd web && npm run dev` → http://localhost:5173 |
| Mobile in browser | `cd mobile && npm run web` |
| First phone install | Download APK from welcome page → Install |
| Later phone updates | Launch app → **Update available** → install |
| Rebuild APK | `prebuild` + `npm run build:apk` → upload via portal |

---

## Related docs

| File | Contents |
|------|----------|
| `app-progress.md` | Feature status across API / Web / Mobile |
| `ReadMe.md` | Repo layout, roles, production URLs |
| `web/README.md` | Web env / proxy notes |
| `mobile/README.md` | Mobile env / LAN IP notes |
| `mobile/RELEASES.md` | Signing, Gradle build, portal upload details |
