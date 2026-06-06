# EFSC-YA

School platform for attendance, marks, homework, notifications, leave workflows, and role-based access. Repository folder name remains `prism/` for local dev paths only; the product name is **EFSC-YA** everywhere else.

---

## Repository layout

| Path | Description |
|------|-------------|
| [`api/`](api/) | Laravel 12 API: Sanctum auth, Spatie roles/permissions, school modules under `/api/efsc/*`. |
| [`web/`](web/) | Vue 3 + Vite SPA for staff/admin and learners. |
| [`mobile/`](mobile/) | Expo + React Native app for parents/students (staff: use web). |

---

## Academic hierarchy

`academic_years` (session) → `areas` → `school_classes` → `sections` → `study_groups` → `students`

Subjects link to study groups via `study_group_subject` (many-to-many).

---

## Roles & test accounts

Password `Test.123` for all `@efsc-ya.test` accounts. LASK accounts unchanged.

| Email | Password | Role |
|-------|----------|------|
| `superadmin@lask.com` | `S.Admin.123` | superadmin |
| `admin@lask.com` | `Admin.123` | admin |
| `developer@lask.com` | `Developer.123` | developer |
| `principal@efsc-ya.test` | `Test.123` | principal |
| `viceprincipal@efsc-ya.test` | `Test.123` | vice_principal |
| `sectionhead@efsc-ya.test` | `Test.123` | section_head |
| `incharge@efsc-ya.test` | `Test.123` | class_incharge |
| `teacher@efsc-ya.test` | `Test.123` | teacher |
| `operator@efsc-ya.test` | `Test.123` | computer_operator |
| `accountant@efsc-ya.test` | `Test.123` | accountant |
| `parent@efsc-ya.test` | `Test.123` | parent |
| `student@efsc-ya.test` | `Test.123` | student |

`SchoolDataSeeder` is empty — add academic data via admin UI or a future demo seeder.

---

## Quick start

```bash
cd api
composer install
php artisan migrate:fresh --seed
php artisan serve
```

```bash
cd web
npm install
npm run dev
```

Open **http://localhost:5173**. API routes use `/api/efsc/*`.

**Superadmin:** `/admin/permissions` — manage role permissions and per-user direct grants.

**Mobile:** `cd mobile && npx expo start` — parents/students use learner dashboard; staff see a message to use web.

---

## Module notes

- **Attendance:** teacher/incharge submit → section head verifies → visible to parents/students.
- **Marks:** operator creates assessments; teachers enter; section head verifies.
- **Homework / online classes:** section head approves before visibility.
- **Timetable / fee vouchers:** Coming soon in UI.
- **Notifications:** user broadcasts (`/notifications` menu); system inbox separate.

---

## Tests

```bash
cd api
php artisan test
```
