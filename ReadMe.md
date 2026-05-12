# PRISM

**Parent's Real-time Insight into School Matters** — a school platform for sharing attendance, marks, homework, timetables, fee vouchers, announcements, and leave workflows with parents, with staff roles and **approval-gated** notifications before parents receive pushes.

---

## Repository layout

| Path | Description |
|------|-------------|
| [`api/`](api/) | Laravel 12 API (LASK base): Sanctum auth, Spatie roles/permissions, PRISM domain + notification modules under `/api/prism/*`. |
| [`web/`](web/) | Vue 3 + Vite SPA for staff/admin: login, dashboard, notification approval queue. |
| [`mobile/`](mobile/) | Expo + React Native app: parent login and aggregate dashboard (expandable). |

More detail: [api/README.md](api/README.md), [web/README.md](web/README.md), [mobile/README.md](mobile/README.md).

---

## What is implemented (MVP)

- **Roles:** superadmin, admin, principal, vice_principal, section_head, class_incharge, teacher, parent, computer_operator, accountant (plus `developer` from LASK seed).
- **Modules:** attendance (daily batch + weekly/monthly reports), marks (assessments, mark sheets, entries, parent notify request), timetable slots, datesheet entries, homework, online class links, fee vouchers + submission status, feed (announcements/events/achievements), leave requests (parent submit, staff decide).
- **Notifications:** configurable `notification_features` + `notification_approval_policies`; `notification_dispatch_requests` must be **approved** before `ProcessApprovedNotificationDispatchJob` writes in-app notifications and runs the **FCM stub** (real FCM can replace `FcmNotificationSender`).
- **Performance-oriented API:** aggregate parent dashboard endpoint, pagination on list routes, bulk inserts where appropriate.

---

## Prerequisites

- **PHP** 8.2+ and **Composer** (Laragon includes these).
- **MySQL** (Laragon default is fine).
- **Node.js** 18+ for `web/` and `mobile/`.

---

## Quick start (Laragon / local)

### 1. Database and API (`api/`)

```bash
cd api
copy .env.example .env
```

Edit `.env`: set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `APP_URL` (e.g. `http://127.0.0.1:8000`). Ensure `QUEUE_CONNECTION=database` if you use the queue worker for notifications.

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Keep the API running at `http://127.0.0.1:8000` (or your Laragon vhost such as `http://prism.test` with document root `api/public`).

**Notification delivery:** after an approver approves a dispatch, run a worker so jobs execute:

```bash
php artisan queue:work
```

For quick smoke tests without a worker, you can temporarily set `QUEUE_CONNECTION=sync` in `.env` (not recommended for production).

### 2. Web admin (`web/`)

```bash
cd web
npm install
copy .env.example .env
npm run dev
```

Open **http://localhost:5173**. Vite proxies `/api` to `http://127.0.0.1:8000`, so the SPA calls the same-origin `/api/login` and `/api/prism/*` routes.

- Log in (e.g. `admin@lask.com` / `Admin.123`, or `incharge@school.test` / `Parent.123` for approvals).
- Use **Dashboard** to see pending approval count; open **Approvals** to approve or reject notification dispatches.

### 3. Mobile (`mobile/`)

```bash
cd mobile
npm install
npx expo start
```

- **Android emulator:** `App.js` defaults API base to `http://10.0.2.2:8000/api` (reaches host `127.0.0.1:8000`).
- **Physical device / iOS simulator:** set `EXPO_PUBLIC_API_URL` to your machine’s LAN URL, e.g. `http://192.168.1.10:8000/api`, or configure `expo.extra` in `mobile/app.json` (see [mobile/README.md](mobile/README.md)).

Log in as a **parent** (e.g. `parent@school.test` / `Parent.123`) to load the aggregate parent dashboard.

---

## Seeded test accounts

Passwords are defined in [`api/database/seeders/UsersTableSeeder.php`](api/database/seeders/UsersTableSeeder.php). Typical examples:

| Email | Password | Role (approx.) |
|-------|----------|----------------|
| `superadmin@lask.com` | `S.Admin.123` | superadmin |
| `admin@lask.com` | `Admin.123` | admin |
| `parent@school.test` | `Parent.123` | parent (linked to demo student) |
| `teacher@school.test` | `Parent.123` | teacher |
| `incharge@school.test` | `Parent.123` | class_incharge |
| `principal@school.test` | `Parent.123` | principal |

Demo class/section/student data is created by `SchoolPrismDataSeeder`.

---

## How to exercise main flows

1. **Teacher marks attendance**  
   `POST /api/prism/attendance/batches` with `school_class_id`, `section_id`, `date`, and `records[]` (include at least one `absent`). Use a Bearer token for `teacher@school.test`.  
   This creates a **pending** notification dispatch for absent alerts (per seeded policy, class incharge approves).

2. **Class incharge approves**  
   Log into **web** as `incharge@school.test`, open **Approvals**, approve the dispatch. With `queue:work` running, parents get in-app rows + FCM stub log.

3. **Marks → notify parents**  
   Teacher updates entries on a mark sheet, then `POST /api/prism/mark-sheets/{id}/notify-parents`. Principal (or policy-defined role) approves in the web UI.

4. **Parent view**  
   Web does not yet mirror full parent UI; use **mobile** or call `GET /api/prism/parent/dashboard?include=homework,timetable` with parent token.

5. **API-only testing**  
   Use Postman/Insomnia: `POST /api/login` → copy `access_token` → `Authorization: Bearer …` on `GET/POST` under `/api/prism/…`.

---

## CORS and production

- API CORS: [`api/config/cors.php`](api/config/cors.php) — set `CORS_ALLOWED_ORIGINS` or reuse `LARAVEL_CORS_ALLOWED_ORIGINS` from `.env.example` (comma-separated origins).
- For production builds of the Vue app, set `VITE_API_URL` in `web/.env` to your public API URL if the SPA is not served behind a reverse proxy on the same host.

---

## Shared hosting (cPanel) note

Limit concurrent `queue:work` processes, keep list endpoints paginated, and avoid opening many parallel DB-heavy tabs during load tests to reduce risk of hitting MySQL `max_user_connections`.

---

## Tests

From `api/`:

```bash
php artisan test
```

---

## License

Follow the license of the underlying LASK / Laravel stack and your organization’s policy for this fork.
