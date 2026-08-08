# EFSC-YA

School platform for attendance, marks, homework, notifications, leave workflows, and role-based access. Repository folder name remains `prism/` for local dev paths only; the product name is **EFSC-YA** everywhere else.

---

## Repository layout

| Path | Description |
|------|-------------|
| [`api/`](api/) | Laravel 12 API: Sanctum auth, Spatie roles/permissions, school modules under `/api/efsc/*`, public welcome page, developer release portal. |
| [`web/`](web/) | Vue 3 + Vite SPA for staff/admin and learners. |
| [`mobile/`](mobile/) | Expo + React Native app for parents/students (staff: use web). Standalone Android APK for off–Play Store distribution. |

---

## Academic hierarchy

`academic_years` (session) → `areas` → `school_classes` → `sections` → `students`

Study groups are independent of classes. Students belong to a study group and optionally a section. Subjects link to study groups via `study_group_subject` (many-to-many).

---

## Roles & test accounts

Password `Test.123` for all `@efsc-ya.com` accounts unless noted.

| Email | Password | Role |
|-------|----------|------|
| `superadmin@efsc-ya.com` | `S.Admin.123` | superadmin |
| `developer@efsc-ya.com` | `Developer.123` | developer |
| `admin@efsc-ya.com` | `Admin.123` | admin |
| `superadmin@lask.com` | `S.Admin.123` | superadmin |
| `admin@lask.com` | `Admin.123` | admin |
| `developer@lask.com` | `Developer.123` | developer |
| `principal@efsc-ya.com` | `Test.123` | principal |
| `viceprincipal@efsc-ya.com` | `Test.123` | vice_principal |
| `sectionhead@efsc-ya.com` | `Test.123` | section_head |
| `incharge@efsc-ya.com` | `Test.123` | class_incharge |
| `teacher@efsc-ya.com` | `Test.123` | teacher |
| `operator@efsc-ya.com` | `Test.123` | computer_operator |
| `accountant@efsc-ya.com` | `Test.123` | accountant |
| `parent@efsc-ya.com` | `Test.123` | parent |
| `student@efsc-ya.com` | `Test.123` | student |

`SchoolDataSeeder` is empty — add academic data via admin UI or a future demo seeder.

---

## Quick start

```bash
cd api
composer install
cp .env.example .env   # set DB credentials
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

```bash
cd web
npm install
npm run dev
```

Open **http://localhost:5173**. API routes use `/api/efsc/*`.

**Superadmin:** `/admin/permissions` — manage role permissions and per-user direct grants.

**Mobile (development):** `cd mobile && npm install && npx expo start` — parents/students use learner dashboard; staff see a message to use web. Expo Go is for dev only; production uses the standalone APK (see below).

---

## Production URLs

| Service | URL | Deploy target |
|---------|-----|----------------|
| **API (Laravel)** | `https://sap-api.innovisiq.com` | `api/public` on server |
| **Web (Vue)** | `https://sap.innovisiq.com` | `web/dist` static files |
| **Mobile** | APK hosted by API | `api/storage/app/public/releases/` |

**API `.env` on server:**

```env
APP_URL=https://sap-api.innovisiq.com
APP_ENV=production
APP_DEBUG=false
DEFAULT_WEB_APP_URL=https://sap.innovisiq.com
LARAVEL_CORS_ALLOWED_ORIGINS=https://sap.innovisiq.com
SANCTUM_STATEFUL_DOMAINS=sap.innovisiq.com
```

**Web production build:**

```bash
cd web
cp .env.production.example .env.production
npm run build
# deploy web/dist/ → sap.innovisiq.com
```

**Mobile production APK** (rebuild required after URL change):

```bash
cd mobile
cp .env.production.example .env.production
# or set EXPO_PUBLIC_API_URL=https://sap-api.innovisiq.com/api
npx expo prebuild --platform android
# then gradlew assembleRelease (see Mobile APK section)
```

Canonical URLs are also defined in [`api/config/urls.php`](api/config/urls.php).

---

## Public welcome page & developer portal

The API serves a public landing page and a hidden release-management portal (no Play Store required).

| URL | Purpose |
|------|---------|
| `/` (local: `http://EFSC-YA.test/`, prod: `https://sap-api.innovisiq.com/`) | Public welcome page — app description, link to web app, Android APK download |
| `/sys/portal-access` | Hidden login (developer / superadmin only) → release settings |
| `/sys/portal-access/releases` | Upload APK, set web URL, version name/code, release notes |
| `GET /api/mobile/version` | JSON for in-app update check (`version`, `version_code`, `apk_url`) |

**Developer portal login** (not linked from the welcome page):

- `developer@efsc-ya.com` / `Developer.123`
- `superadmin@efsc-ya.com` / `S.Admin.123`

**Optional `.env` keys** (see `api/.env.example`):

```env
# Local
DEFAULT_WEB_APP_URL=http://localhost:5173
# Production
DEFAULT_WEB_APP_URL=https://sap.innovisiq.com
DEV_PORTAL_PATH=sys/portal-access
```

APK files are stored at `api/storage/app/public/releases/` and served at `/storage/releases/...` after `php artisan storage:link`.

---

## Mobile app — development

```bash
cd mobile
npm install
npx expo start          # Expo Go / QR for device testing
npm run web             # Browser at http://localhost:8081
```

Copy `mobile/.env.example` to `mobile/.env` for **local** dev:

- `EXPO_PUBLIC_API_URL=http://prism.test/api` (or `http://EFSC-YA.test/api`)
- `EXPO_PUBLIC_API_LAN_IP=` your PC Wi‑Fi IP (`ipconfig`) — required on a **physical phone**

For **production APK**, use `mobile/.env.production.example` → `EXPO_PUBLIC_API_URL=https://sap-api.innovisiq.com/api` (no LAN IP).

The app bridges `prism.test` / `EFSC-YA.test` to your LAN IP with a `Host` header on physical devices. Production uses `sap-api.innovisiq.com` directly.

---

## Mobile app — Android APK (off–Play Store)

Production distribution uses a **standalone signed APK**, not Expo Go.

### Signing key (save securely)

| Item | Value |
|------|-------|
| Keystore file | `mobile/credentials/android/efsc-ya-release.keystore` |
| Alias | `efsc-ya` |
| Store password | `EFSC-YA-Store-2026!` |
| Key password | `EFSC-YA-Store-2026!` (PKCS12 — same as store password) |
| Validity | 10,000 days |

**Important:** Back up the keystore and passwords offline. Every future APK must use this same keystore, or users must uninstall before installing a new build.

### Build APK locally (Windows)

**Prerequisites:**

- Android Studio JBR: `C:\Program Files\Android\Android Studio\jbr`
- Android SDK: `%LOCALAPPDATA%\Android\Sdk`
- `org.gradle.java.home` is set in `mobile/android/gradle.properties`

**Build:**

```powershell
cd mobile
npm install
npx expo prebuild --platform android
npm run build:apk
```

**Output:**

| Path | Description |
|------|-------------|
| `mobile/android/app/build/outputs/apk/release/app-release.apk` | Gradle output |
| `mobile/dist/sap-efsc-1.0.0.apk` | Single production APK for sharing |
| `api/storage/app/public/releases/` | Upload via developer portal (served on welcome page) |

To regenerate the keystore (first time only): `mobile/scripts/setup-android-signing.ps1`

### Share the APK

**Developers**

1. Build or copy the APK to `mobile/dist/`.
2. Sign in at `https://sap-api.innovisiq.com/sys/portal-access` (or local `http://EFSC-YA.test/sys/portal-access`).
3. Open **Release settings** → upload APK (or place file in `api/storage/app/public/releases/`).
4. Set **version name** (e.g. `1.0.0`) and **version code** (integer; must increase for each new APK).
5. Set **web app URL** (production Vue URL).
6. Save — the welcome page shows **Download Android app**.

**Parents / staff (first install)**

1. Open `https://sap-api.innovisiq.com/` on the phone browser (or local welcome URL).
2. Tap **Download Android app**.
3. Allow download → open file → **Install**.
4. If blocked: Settings → allow installs from the browser (one-time).

**Other channels:** USB, WhatsApp, or school LAN — share `mobile/dist/sap-efsc-*.apk` or the welcome-page link.

---

## Mobile updates (without Play Store)

Two mechanisms work together:

| Method | When to use | User experience |
|--------|-------------|-----------------|
| **OTA (Expo Updates)** | JS-only changes: screens, logic, UI | Update on next app open — **no reinstall** |
| **In-app APK updater** | Native changes, new permissions, Expo SDK bump, `versionCode` increase | **“Update available”** prompt → tap → Android install screen |

**On each app launch:** OTA check first (silent), then APK version check via `GET /api/mobile/version`.

### A) JS-only updates (OTA)

Requires one-time EAS setup: `eas init` in `mobile/`, then set `updates.url` in `app.json`.

```bash
cd mobile
eas update --branch production --message "Describe change"
```

Users get the update on next cold start. No APK reinstall.

### B) Native / version-code updates (new APK)

1. Bump in `mobile/app.json`: `version` (e.g. `1.0.1`) and `android.versionCode` (e.g. `2`).
2. Rebuild APK (see build steps above).
3. Upload via developer portal; increase version code in the form.
4. Installed users see an update prompt on launch.

Same package name + same signing key = Android replaces the app without manual uninstall.

**Rule of thumb:** Use OTA for day-to-day fixes; ship a new APK only when native code or `versionCode` changes.

---

## Module notes

- **Attendance:** teacher/incharge submit → section head verifies → visible to parents/students.
- **Marks:** operator creates assessments; teachers enter; section head verifies.
- **Homework / online classes:** section head approves before visibility.
- **Timetable / fee vouchers:** Coming soon in UI.
- **AIMS import:** Admin web page at `/admin/aims-import` imports CSV exports from AIMS (students, attendance, fees, results). See [`docs/aims-prism-csv-contract.md`](docs/aims-prism-csv-contract.md).
- **Notifications:** user broadcasts (`/notifications` menu); system inbox separate.

---

## Tests

```bash
cd api
php artisan test
```

