# EFSC-YA — Project update (July 2026)

School platform for attendance, marks, homework, notifications, leave, and role-based access.  
Repo folder is still `prism/`; product name is **EFSC-YA**.

**Where you left off:** branch `fix/ui-ux` (tracking `origin/fix/ui-ux`), with uncommitted work that mainly switches seed/login emails from `@efsc-ya.test` → `@efsc-ya.com` and polishes login UX (web + mobile password show/hide).

**Production (already deployed):**

| Service | URL |
|---------|-----|
| API | https://sap-api.innovisiq.com |
| Web | https://sap.innovisiq.com |
| Mobile APK | Download from API welcome page |

---

## Snapshot: what’s done vs not

| Area | Status |
|------|--------|
| Auth (Sanctum) + Spatie roles/permissions | Done |
| Academic structure (years → areas → classes → sections → students) | Done (API + web admin) |
| Attendance (mark → verify → parent view) | Done (web + mobile staff mark) |
| Marks / assessments | Done |
| Homework + online classes (with approval) | Done |
| Leave requests | Done |
| Notifications / broadcasts (with approval) | Done |
| Parent/student learner dashboards | Done (web + mobile) |
| Superadmin “View as” role/user | Done |
| Mobile standalone APK + in-app update check | Done |
| Timetable / fees | **API exists; web routes still “Coming soon”** (views exist but not wired) |
| Demo school data seeder | **Empty** — you must create academic data via admin UI |
| Push notifications (FCM) | **Stub only** (logs, does not send) |
| OTA (Expo Updates) | Code present; needs EAS `updates.url` setup |

---

## 1. API (`api/`)

### What it is

Laravel **12** API with Sanctum tokens, Spatie permissions, activity log, Telescope, health checks.

School modules live under **`/api/efsc/*`**.

### Main pieces

| Path | Role |
|------|------|
| `routes/api.php` | REST API |
| `routes/web.php` | Welcome page, developer portal, dashboard |
| `app/Http/Controllers/Api/Efsc/` | Attendance, marks, homework, leave, fees, etc. |
| `database/seeders/` | Users, roles, notification catalog, release settings |
| `config/urls.php` | Production API/web URLs |

### Auth / login

- Login: `POST /api/login` with `{ email, password }`
- Identifier can be full email **or** local part only (admission no. / CNIC) → appended with `@efsc-ya.com`
- Uncommitted: `LoginIdentifier.php` + seeders updated for `@efsc-ya.com`

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

| URL | Purpose |
|-----|---------|
| http://prism.test/ | Welcome page (APK download, link to web) |
| http://prism.test/sys/portal-access | Developer release portal |
| http://prism.test/api/login | API login (POST) |
| http://prism.test/api/mobile/version | Mobile update JSON |

If you use `php artisan serve`, point web/mobile env at `http://127.0.0.1:8000` instead of `prism.test`.

### Seeded test accounts

After `migrate:fresh --seed` (with current uncommitted seeders):

| Email | Password | Role |
|-------|----------|------|
| `superadmin@efsc-ya.com` | `S.Admin.123` | superadmin |
| `admin@efsc-ya.com` | `Admin.123` | admin |
| `developer@efsc-ya.com` | `Developer.123` | developer |
| `principal@efsc-ya.com` | `Test.123` | principal |
| `viceprincipal@efsc-ya.com` | `Test.123` | vice_principal |
| `sectionhead@efsc-ya.com` | `Test.123` | section_head |
| `incharge@efsc-ya.com` | `Test.123` | class_incharge |
| `teacher@efsc-ya.com` | `Test.123` | teacher |
| `operator@efsc-ya.com` | `Test.123` | computer_operator |
| `accountant@efsc-ya.com` | `Test.123` | accountant |
| `parent@efsc-ya.com` | `Test.123` | parent |
| `student@efsc-ya.com` | `Test.123` | student |

You can also log in with just the local part (e.g. `incharge` / `Test.123`).

**Important:** `SchoolDataSeeder` is empty. Seeded users have **no** classes/students/attendance until you create them in **Admin → Academic** (or enroll via UI).

---

## 2. Web (`web/`)

### What it is

Vue **3.5** + Vite **6** + Pinia + Vue Router SPA. No UI kit — custom CSS. Staff/admin primary surface; parents/students get learner views.

### Main pieces

| Path | Role |
|------|------|
| `src/router/index.js` | Routes + guards |
| `src/stores/auth.js` | Login / token |
| `src/views/` | Feature screens |
| `src/views/admin/` | Users, Permissions, Academic config |
| `src/api/client.js` | Axios → `/api` |

### Features by route

| Route | Status |
|-------|--------|
| `/login`, `/` dashboard | Done |
| `/admin/users`, `/admin/permissions`, `/admin/academic` | Done |
| `/attendance`, `/marks`, `/homework`, `/online-classes` | Done |
| `/notifications`, `/leave`, `/approvals` | Done |
| `/timetable`, `/fees` | **Coming soon** (UI files exist but not routed) |

### How to start / resume (Web)

```powershell
cd D:\laragon\www\prism\web
npm install
# .env already exists — VITE_API_PROXY_TARGET should be http://prism.test
npm run dev
```

Open **http://localhost:5173**.

Dev mode: browser calls `/api/*` on `:5173`; Vite proxies to Laragon (`prism.test`).  
Production build uses `VITE_API_URL=https://sap-api.innovisiq.com/api`.

### Uncommitted web work

- `LoginView.vue` — cleaner login card, show/hide password, no prefilled credentials
- `UsersAdminView.vue` — email placeholder → `@efsc-ya.com`

---

## 3. Mobile (`mobile/`)

### What it is

Expo SDK **54** / React Native app (`com.school.efscya`, v1.0.0).  
**Primary audience:** parents & students.  
**Staff:** only attendance marking is in-app; other staff are told to use the web app.

### Main pieces

| Path | Role |
|------|------|
| `App.js` | Login + shell + tabs |
| `screens/ParentScreens.js` | Learner features |
| `screens/StaffAttendanceScreen.js` | Staff attendance |
| `apiClient.js` | Axios + Laragon LAN Host bridge |
| `services/apkUpdate.js` | In-app APK update |
| `RELEASES.md` | Signing + build + portal upload |

### Features

| Feature | Status |
|---------|--------|
| Parent home, child pick, dashboard | Done |
| Homework, marks, attendance, timetable, fees list, online class, leave, notifications | Done |
| Staff attendance mark/view/summary | Done |
| Superadmin View-as | Done |
| Password show/hide | Uncommitted (`EyeIcon.js` + `react-native-svg`) |
| Push (FCM) | Not wired |
| OTA JS updates | Needs EAS project URL |

### How to start / resume (Mobile)

**Fastest for local testing (browser):**

```powershell
cd D:\laragon\www\prism\mobile
npm install
# .env already exists — EXPO_PUBLIC_API_URL=http://prism.test/api
npx expo start
# press w → http://localhost:8081
```

**Physical phone (Expo Go):**

1. Same Wi‑Fi as PC.
2. In `mobile/.env`, set `EXPO_PUBLIC_API_LAN_IP` to your PC Wi‑Fi IP (`ipconfig`).
3. `npx expo start` → scan QR with Expo Go.
4. App rewrites `prism.test` → LAN IP and sends `Host: prism.test` so Laragon still matches.

**Production APK** (when you need a real installable): see `mobile/RELEASES.md` or root `ReadMe.md` (`expo prebuild` + `gradlew assembleRelease`). Upload via developer portal.

---

## 4. Recommended resume order

1. **Start Laragon** (Apache + MySQL).
2. **API:** confirm DB works; if unsure, `migrate:fresh --seed`.
3. **Web:** `npm run dev` → login as `superadmin@efsc-ya.com`.
4. **Create minimal school data** (required before attendance/marks/parent flows work):
   - `/admin/academic` → academic year → area → class → section → study group/subjects
   - Enroll at least one student; link a parent user if testing parent app
5. **Smoke-test staff flows** on web (attendance → verify → marks → homework).
6. **Mobile:** `npx expo start` → `w` or Expo Go as `parent@efsc-ya.com`.

---

## 5. Manual test guide

Use this checklist after seed + a bit of academic data.

### A. API smoke

| # | Check | How |
|---|--------|-----|
| 1 | Welcome page loads | Open http://prism.test/ |
| 2 | Login works | `POST http://prism.test/api/login` with `{"email":"superadmin@efsc-ya.com","password":"S.Admin.123"}` (Postman/Thunder Client) |
| 3 | Short identifier | Login with `"email":"incharge"` / `Test.123` |
| 4 | Mobile version | `GET http://prism.test/api/mobile/version` → JSON |
| 5 | Dev portal | http://prism.test/sys/portal-access → `developer@efsc-ya.com` / `Developer.123` |

### B. Web — admin & staff

Login at http://localhost:5173/login.

| # | Role | What to test |
|---|------|----------------|
| 1 | `superadmin` | `/admin/permissions` — role defaults + per-user grants |
| 2 | `superadmin` | Header **View as** — switch role / user; confirm nav changes |
| 3 | `admin` / `operator` | `/admin/users` — create user, assign roles |
| 4 | `operator` / admin | `/admin/academic` — year → area → class → section → enroll student |
| 5 | `teacher` / `incharge` | `/attendance` — mark batch → submit |
| 6 | `sectionhead` | `/attendance` — verify batch; `/approvals` if anything pending |
| 7 | `operator` / teacher | `/marks` — assessment / mark sheet → enter → verify / notify |
| 8 | Staff | `/homework`, `/online-classes` — create; section head approve if required |
| 9 | Staff | `/notifications` — broadcast; approve if gated |
| 10 | Staff / parent | `/leave` — request + decide |
| 11 | Anyone | Confirm `/timetable` and `/fees` show Coming soon |

### C. Web — parent / student

| # | Account | What to test |
|---|---------|----------------|
| 1 | `parent@…` | Home → select child → dashboard, attendance, marks, homework |
| 2 | `student@…` | Same learner views for self |

*(Only works after students are enrolled and linked in academic config.)*

### D. Mobile

| # | Check | How |
|---|--------|-----|
| 1 | Web Metro | `npx expo start` → browser → login `parent@efsc-ya.com` |
| 2 | Learner tabs | Dashboard, homework, marks, attendance, leave, notifications |
| 3 | Staff attendance | Login as `incharge` / `teacher` → Attendance screen |
| 4 | Other staff | Login as `principal` → should see “use web” style message |
| 5 | Phone (optional) | Set LAN IP in `.env`, Expo Go, same flows |

### E. End-to-end happy path (best single walkthrough)

1. Superadmin: create academic year + class + section + one student; ensure parent is linked.
2. Incharge/teacher: mark attendance for today → submit.
3. Section head: verify attendance.
4. Parent (web or mobile): open Attendance → see verified day.
5. Operator/teacher: create mark sheet → enter marks → notify.
6. Parent: open Marks → see entries.
7. Teacher: post homework → section head approve → parent sees it.

---

## 6. Git / working tree notes

- **Current branch:** `fix/ui-ux`
- **Other local branches:** `master`, `staging`, `expo-build`, `building-project`, `navigation`, `project-structure`
- **Uncommitted theme:** `@efsc-ya.com` domain alignment + login UX (web/mobile). Commit when you’re ready.
- Prefer root **`ReadMe.md`** over `api/README.md` (API readme still mentions old LASK / `/api/prism` names).

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

1. Commit the pending `@efsc-ya.com` + login UX changes on `fix/ui-ux`.
2. Add a small **demo SchoolDataSeeder** so manual testing doesn’t require hand-building the hierarchy every time.
3. Wire `/timetable` and `/fees` to the existing Vue views (or finish those UIs).
4. Replace FCM stub when push is needed.
5. Finish EAS OTA if you want JS-only mobile updates without new APKs.
