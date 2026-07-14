# EFSC-YA — Project update (July 2026)

School platform for attendance, marks, homework, notifications, leave, and role-based access.  
Repo folder is still `prism/`; product name is **EFSC-YA**.

**Where you left off:** branch `fix/ui-ux` (tracking `origin/fix/ui-ux`), clean working tree. Latest: mobile academic **Configuration** parity + web redirect to login on auth failure (`1340812`, 2026-07-14).

**Production (already deployed):**

| Service    | URL                            |
| ---------- | ------------------------------ |
| API        | https://sap-api.innovisiq.com  |
| Web        | https://sap.innovisiq.com      |
| Mobile APK | Download from API welcome page |

---

## Snapshot: what’s done vs not

### Platform / infra — Done

| Area                                                                     | Status                                      |
| ------------------------------------------------------------------------ | ------------------------------------------- |
| Auth (Sanctum) + Spatie roles/permissions                                | Done                                        |
| Login: full email **or** local part → `@efsc-ya.com` (`LoginIdentifier`) | Done                                        |
| Academic structure (years → areas → classes → sections → students)       | Done (API + web + **mobile Configuration**) |
| Module catalog `GET /api/efsc/modules` (web + mobile nav)                | Done                                        |
| Apps admin `/admin/apps` (`live` / `coming_soon` / `disabled`)           | Done (web-only; superadmin + developer)     |
| Superadmin View-as role/user                                             | Done (web + mobile)                         |
| Mobile standalone APK + in-app APK update check                          | Done                                        |
| Developer release portal                                                 | Done                                        |
| Seeded test users `@efsc-ya.com`                                         | Done                                        |

### School modules — code vs catalog default

**Important:** Feature UIs exist, but **by default** non-admin school modules are **`coming_soon`** in `ModuleCatalogService`. Until Apps admin (or `module_settings`) sets them to **`live`**, web shows “Coming soon” and mobile greys tiles / blocks entry.

| Module                                                             | API  | Web UI (when `live`)                                                     | Mobile (when `live`)                                                                              | Default catalog |
| ------------------------------------------------------------------ | ---- | ------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------- | --------------- |
| Dashboard / Users / Permissions / Apps / Approvals / Configuration | Done | Done                                                                     | Config + Approvals real; Users/Permissions **list-only**                                          | **`live`**      |
| Attendance                                                         | Done | Done (mark → submit → verify)                                            | Staff: full mark/view/summary; Learner: monthly report                                            | `coming_soon`   |
| Marks / assessments                                                | Done | Partial — list/enter/notify; **no create assessment / no Verify button** | Read list + detail (no staff enter UI)                                                            | `coming_soon`   |
| Homework                                                           | Done | Done — Post / Diary / Pending approve-reject                             | Done — permission-gated post + pending + learner diary                                            | `coming_soon`   |
| Online classes                                                     | Done | Partial — create; **no approve UI**                                      | Learner/staff list + open URL                                                                     | `coming_soon`   |
| Notifications / broadcasts                                         | Done | Done (create + approve on Notifications / Approvals)                     | Inbox + feed; staff Approvals for pending                                                         | `coming_soon`   |
| Leave                                                              | Done | Done (request + decide)                                                  | Done                                                                                              | `coming_soon`   |
| Timetable / datesheet                                              | Done | Wired (`TimetableView` + parent view via catalog)                        | Screens **exist** (`TimetableScreen`) but **App.js does not open them** (falls back to dashboard) | `coming_soon`   |
| Fees                                                               | Done | Wired (`FeeView` + parent view via catalog)                              | Screens **exist** (`FeesScreen`) but **App.js does not open them**                                | `coming_soon`   |

### Explicitly not done

| Area                                | Status                                                                                                         |
| ----------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Demo school data seeder             | **Empty** — create academic data via Configuration (web or mobile)                                             |
| Push notifications (FCM)            | **Stub only** (`FcmNotificationSender` logs; does not send)                                                    |
| OTA (Expo Updates)                  | Code present; needs EAS `updates.url` / project setup                                                          |
| Staff mobile beyond current screens | No staff enter-marks UI; Users/Permissions are browse-only; online-class **approve** still missing |
| CMS hub (AMS/LMS/…)                 | Separate product — not in this repo                                                                            |

---

## 1. API (`api/`)

### What it is

Laravel **12** API with Sanctum tokens, Spatie permissions, activity log, Telescope, health checks.

School modules live under **`/api/efsc/*`**.

### Main pieces

| Path                                    | Role                                                         |
| --------------------------------------- | ------------------------------------------------------------ |
| `routes/api.php`                        | REST API                                                     |
| `routes/web.php`                        | Welcome page, developer portal, dashboard                    |
| `app/Http/Controllers/Api/Efsc/`        | Attendance, marks, homework, leave, fees, modules/apps, etc. |
| `app/Services/ModuleCatalogService.php` | Nav enablement + default `coming_soon` for school modules    |
| `database/seeders/`                     | Users, roles, notification catalog, release settings         |
| `config/urls.php` / `config/efsc.php`   | Production URLs + login email domain                         |

### Auth / login

- Login: `POST /api/login` with `{ email, password }`
- Identifier can be full email **or** local part only (admission no. / CNIC) → appended with `@efsc-ya.com`
- `App\Support\LoginIdentifier` is committed and in use

### How to start / resume (API)

Laragon is already set up: hosts include `prism.test` and `efsc.test`. Root `.htaccess` maps `/api/*` → Laravel.

```powershell
# 1. Start Laragon (Apache + MySQL)

# 2. Ensure dependencies & DB
cd D:\laragon\www\prism\api
composer install
# .env already exists on this machine — confirm DB_* and APP_URL

php artisan migrate:fresh --seed   # wipe + seed (dev only)
php artisan storage:link           # once, for APK downloads

# Optional instead of Laragon vhost:
php artisan serve                  # http://127.0.0.1:8000
```

**Useful local URLs**

| URL                                  | Purpose                                  |
| ------------------------------------ | ---------------------------------------- |
| http://prism.test/                   | Welcome page (APK download, link to web) |
| http://prism.test/sys/portal-access  | Developer release portal                 |
| http://prism.test/api/login          | API login (POST)                         |
| http://prism.test/api/mobile/version | Mobile update JSON                       |

If you use `php artisan serve`, point web/mobile env at `http://127.0.0.1:8000` instead of `prism.test`.

### Seeded test accounts

After `migrate:fresh --seed`:

| Email                       | Password        | Role              |
| --------------------------- | --------------- | ----------------- |
| `superadmin@efsc-ya.com`    | `S.Admin.123`   | superadmin        |
| `admin@efsc-ya.com`         | `Admin.123`     | admin             |
| `developer@efsc-ya.com`     | `Developer.123` | developer         |
| `principal@efsc-ya.com`     | `Test.123`      | principal         |
| `viceprincipal@efsc-ya.com` | `Test.123`      | vice_principal    |
| `sectionhead@efsc-ya.com`   | `Test.123`      | section_head      |
| `incharge@efsc-ya.com`      | `Test.123`      | class_incharge    |
| `teacher@efsc-ya.com`       | `Test.123`      | teacher           |
| `operator@efsc-ya.com`      | `Test.123`      | computer_operator |
| `accountant@efsc-ya.com`    | `Test.123`      | accountant        |
| `parent@efsc-ya.com`        | `Test.123`      | parent            |
| `student@efsc-ya.com`       | `Test.123`      | student           |

You can also log in with just the local part (e.g. `incharge` / `Test.123`).

**Important:** `SchoolDataSeeder` is empty. Seeded users have **no** classes/students/attendance until you create them in **Configuration** (web `/admin/academic` or mobile Configuration). To exercise attendance/marks/etc., flip those modules to **`live`** in **Apps** (`/admin/apps`).

---

## 2. Web (`web/`)

### What it is

Vue **3.5** + Vite **6** + Pinia + Vue Router SPA. No UI kit — custom CSS. Staff/admin primary surface; parents/students get learner views.

Nav is driven by `GET /efsc/modules` (`stores/modules.js` + `catalogView` wrapper). Non-`live` modules render `ComingSoonView`.

### Main pieces

| Path                                | Role                                        |
| ----------------------------------- | ------------------------------------------- |
| `src/router/index.js`               | Routes + guards (all school modules routed) |
| `src/stores/auth.js` / `modules.js` | Login / token / catalog                     |
| `src/views/`                        | Feature screens                             |
| `src/views/admin/`                  | Users, Permissions, Academic, **Apps**      |
| `src/api/client.js`                 | Axios → `/api`                              |

### Features by route

| Route                                                                               | Status                                                          |
| ----------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| `/login`, `/` landing, `/home` dashboard                                            | Done                                                            |
| `/admin/users`, `/admin/permissions`, `/admin/academic`, `/admin/apps`              | Done                                                            |
| `/approvals`                                                                        | Done (broadcasts + system dispatches)                           |
| `/attendance`, `/marks`, `/homework`, `/online-classes`, `/notifications`, `/leave` | **UI present**; gated by catalog status (default `coming_soon`) |
| `/timetable`, `/fees`                                                               | **Views wired** (no longer “unrouted”); same catalog gate       |
| Parent learner routes (same paths via `roleView`)                                   | Done when catalog `live`                                        |

### UI gaps (API exists, web incomplete)

- Create assessments (API `POST /efsc/assessments`) — no dedicated UI
- Verify mark sheets — no Verify button
- Approve online classes — no dedicated approve controls (Approvals page covers broadcasts/dispatches only)
- Homework attachments — deferred (`attachment_path` unused)

### How to start / resume (Web)

```powershell
cd D:\laragon\www\prism\web
npm install
# .env — VITE_API_PROXY_TARGET should be http://prism.test
npm run dev
```

Open **http://localhost:5173**.

Dev mode: browser calls `/api/*` on `:5173`; Vite proxies to Laragon (`prism.test`).  
Production build uses `VITE_API_URL=https://sap-api.innovisiq.com/api`.

---

## 3. Mobile (`mobile/`)

### What it is

Expo SDK **54** / React Native app (`com.school.efscya`).  
**Learners:** parent/student feature grid.  
**Staff:** module tiles from the same catalog; several real screens now (not “use web only”).

### Main pieces

| Path                                     | Role                                                  |
| ---------------------------------------- | ----------------------------------------------------- |
| `App.js`                                 | Login + shell + tab/feature routing                   |
| `features.js`                            | Catalog → tiles (`live` / `coming_soon` / `disabled`) |
| `screens/ParentScreens.js`               | Learner + shared module screens                       |
| `screens/StaffAttendanceScreen.js`       | Staff attendance                                      |
| `screens/StaffModuleScreens.js`          | Approvals, Users, Permissions                         |
| `screens/ConfigurationScreen.js`         | Academic structure / enroll (parity with web)         |
| `services/apkUpdate.js` / `otaUpdate.js` | APK update + OTA check                                |

### Features

| Feature                                                            | Status                                           |
| ------------------------------------------------------------------ | ------------------------------------------------ |
| Login, password show/hide, session                                 | Done                                             |
| Module catalog tiles (same source as web)                          | Done                                             |
| Parent home, child pick, learner dashboard                         | Done                                             |
| Learner: homework, marks, attendance, notifications, online, leave | Done (when catalog `live`)                       |
| Learner: timetable, fees                                           | Screens exist; **not hooked in `App.js` switch** |
| Staff attendance mark/view/summary                                 | Done                                             |
| Staff Configuration (academic + enroll)                            | Done                                             |
| Staff Approvals (broadcasts / dispatches)                          | Done                                             |
| Staff Homework (post / pending approve-reject / diary)             | Done                                             |
| Staff Users / Permissions                                          | List-only (manage on web)                        |
| Staff marks enter / online approve                                 | Not built                                        |
| Superadmin View-as                                                 | Done                                             |
| Push (FCM)                                                         | Not wired                                        |
| OTA JS updates                                                     | Needs EAS project URL                            |

### How to start / resume (Mobile)

**Fastest for local testing (browser):**

```powershell
cd D:\laragon\www\prism\mobile
npm install
# .env — EXPO_PUBLIC_API_URL=http://prism.test/api
npx expo start
# press w → http://localhost:8081
```

**Physical phone (Expo Go):**

1. Same Wi‑Fi as PC.
2. In `mobile/.env`, set `EXPO_PUBLIC_API_LAN_IP` to your PC Wi‑Fi IP (`ipconfig`).
3. `npx expo start` → scan QR with Expo Go.
4. App rewrites `prism.test` → LAN IP and sends `Host: prism.test` so Laragon still matches.

**Production APK:** see `mobile/RELEASES.md` or root `ReadMe.md`. Upload via developer portal.

---

## 4. Recommended resume order

1. **Start Laragon** (Apache + MySQL).
2. **API:** confirm DB works; if unsure, `migrate:fresh --seed`.
3. **Web:** `npm run dev` → login as `superadmin@efsc-ya.com`.
4. **Apps** (`/admin/apps`): set modules you want to test to **Accessible (`live`)** — e.g. attendance, marks, homework.
5. **Create minimal school data** (required before attendance/marks/parent flows work):
   - Configuration → academic year → area → class → section → study group/subjects
   - Enroll at least one student; link a parent user if testing parent app  
     (Web `/admin/academic` or mobile Configuration.)
6. **Smoke-test staff flows** on web (attendance → verify → marks → homework).
7. **Mobile:** `npx expo start` → `w` or Expo Go as `parent@efsc-ya.com` / staff roles.

---

## 5. Manual test guide

Use this checklist after seed + Apps flips + a bit of academic data.

### A. API smoke

| #   | Check              | How                                                                                                   |
| --- | ------------------ | ----------------------------------------------------------------------------------------------------- |
| 1   | Welcome page loads | Open http://prism.test/                                                                               |
| 2   | Login works        | `POST http://prism.test/api/login` with `{"email":"superadmin@efsc-ya.com","password":"S.Admin.123"}` |
| 3   | Short identifier   | Login with `"email":"incharge"` / `Test.123`                                                          |
| 4   | Module catalog     | `GET /api/efsc/modules?platform=web` with Bearer token                                                |
| 5   | Mobile version     | `GET http://prism.test/api/mobile/version` → JSON                                                     |
| 6   | Dev portal         | http://prism.test/sys/portal-access → `developer@efsc-ya.com` / `Developer.123`                       |

### B. Web — admin & staff

Login at http://localhost:5173/login.

| #   | Role                   | What to test                                                                 |
| --- | ---------------------- | ---------------------------------------------------------------------------- |
| 1   | `superadmin`           | `/admin/apps` — flip attendance/marks/etc. to Accessible                     |
| 2   | `superadmin`           | `/admin/permissions` — role defaults + per-user grants                       |
| 3   | `superadmin`           | Header **View as** — switch role / user; confirm nav changes                 |
| 4   | `admin` / `operator`   | `/admin/users` — create user, assign roles                                   |
| 5   | `operator` / admin     | `/admin/academic` — year → area → class → section → enroll student           |
| 6   | `teacher` / `incharge` | `/attendance` — mark batch → submit                                          |
| 7   | `sectionhead`          | `/attendance` — verify batch; `/approvals` for broadcasts/dispatches         |
| 8   | `operator` / teacher   | `/marks` — enter marks → notify (assessment create/verify still API-only)    |
| 9   | Staff                  | `/homework` — post; section head Pending tab approve/reject; `/online-classes` — create (approve still API-only) |
| 10  | Staff                  | `/notifications` — broadcast; approve if gated                               |
| 11  | Staff / parent         | `/leave` — request + decide                                                  |
| 12  | Operator               | `/timetable`, `/fees` — after Apps set `live`, staff UIs should load         |

### C. Web — parent / student

| #   | Account     | What to test                                                                          |
| --- | ----------- | ------------------------------------------------------------------------------------- |
| 1   | `parent@…`  | Home → select child → dashboard, attendance, marks, homework (modules must be `live`) |
| 2   | `student@…` | Same learner views for self                                                           |

_(Only works after students are enrolled and linked in Configuration.)_

### D. Mobile

| #   | Check               | How                                                                        |
| --- | ------------------- | -------------------------------------------------------------------------- |
| 1   | Web Metro           | `npx expo start` → browser → login `parent@efsc-ya.com`                    |
| 2   | Learner tabs        | Dashboard, homework, marks, attendance, leave, notifications (when `live`) |
| 3   | Staff attendance    | Login as `incharge` / `teacher` → Attendance (when `live`)                 |
| 4   | Staff configuration | Login as `operator` / `admin` → Configuration                              |
| 5   | Staff approvals     | Section head / principal → Approvals                                       |
| 6   | Phone (optional)    | Set LAN IP in `.env`, Expo Go, same flows                                  |

### E. End-to-end happy path (best single walkthrough)

1. Superadmin: `/admin/apps` → set attendance, marks, homework to **live**.
2. Superadmin/operator: create academic year + class + section + one student; ensure parent is linked.
3. Incharge/teacher: mark attendance for today → submit.
4. Section head: verify attendance.
5. Parent (web or mobile): open Attendance → see verified day.
6. Operator/teacher: enter marks on existing sheet → notify (create assessment via API if needed).
7. Parent: open Marks → see entries.
8. Teacher: post homework → section head approve on Homework Pending tab → parent sees it (+ notification dispatch).

---

## 6. Git / working tree notes

- **Current branch:** `fix/ui-ux` (in sync with `origin/fix/ui-ux`)
- **Other local branches:** `master`, `staging`, `fixes`, `ftr/apps`, `fix/app-conflict`, `expo-build`, `building-project`, `navigation`, `project-structure`
- Recent themes on this line: module catalog + Apps admin, default school modules `coming_soon`, mobile Configuration, attendance permission/UI alignment, auth hardening
- Prefer root **`ReadMe.md`** over `api/README.md` (API readme still mentions old LASK / `/api/prism` names)
- Fuller feature inventory: `docs/document.md` (some “unwired timetable/fees” notes there are stale — web routes are wired; catalog gates them)
- Mobile vs web catalog audit history: `docs/conflict.md` (resolved)

---

## 7. Quick command cheat sheet

```powershell
# API (Laragon running)
cd D:\laragon\www\prism\api
php artisan migrate:fresh --seed
php artisan test

# Web
cd D:\laragon\www\prism\web
npm run dev          # http://localhost:5173

# Mobile
cd D:\laragon\www\prism\mobile
npx expo start       # then w for browser, or QR for phone
```

---

## 8. Suggested next work (when you’re ready)

1. Flip needed modules to **`live`** in Apps for the environments you care about (or seed `module_settings`).
2. Add a small **demo SchoolDataSeeder** so testing doesn’t require hand-building the hierarchy every time.
3. Wire mobile `TimetableScreen` / `FeesScreen` into `App.js` when those modules are `live`.
4. Close remaining web UI gaps: create assessment, verify marks, approve online classes.
5. Deepen staff mobile screens (enter marks, richer Users/Permissions) or keep directing heavy admin to web.
6. Replace FCM stub when push is needed.
7. Finish EAS OTA if you want JS-only mobile updates without new APKs.
