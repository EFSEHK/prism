# EFSC-YA — Feature Documentation

Complete inventory of features, modules, and options across **Web** (Vue SPA), **Mobile** (Expo / React Native), and the **API** (Laravel), including which **user roles** can access each capability.

Product name: **EFSC-YA**  
Repository folder: `prism/`

---

## Table of contents

1. [Platform overview](#1-platform-overview)
2. [User roles](#2-user-roles)
3. [Permissions catalog](#3-permissions-catalog)
4. [Authentication & session](#4-authentication--session)
5. [Superadmin View-as / impersonation](#5-superadmin-view-as--impersonation)
6. [Administration — Users](#6-administration--users)
7. [Administration — Permissions](#7-administration--permissions)
8. [Academic structure & enrollment](#8-academic-structure--enrollment)
9. [Staff dashboard](#9-staff-dashboard)
10. [Attendance](#10-attendance)
11. [Marks & assessments](#11-marks--assessments)
12. [Homework](#12-homework)
13. [Online classes](#13-online-classes)
14. [Timetable & datesheet](#14-timetable--datesheet)
15. [Fees](#15-fees)
16. [Leave](#16-leave)
17. [Notifications & broadcasts](#17-notifications--broadcasts)
18. [Approvals](#18-approvals)
19. [Learner portal (Parent / Student)](#19-learner-portal-parent--student)
20. [Developer release portal & mobile distribution](#20-developer-release-portal--mobile-distribution)
21. [Platform / ops (API host)](#21-platform--ops-api-host)
22. [Web navigation matrix](#22-web-navigation-matrix)
23. [Mobile feature matrix](#23-mobile-feature-matrix)
24. [API module endpoints summary](#24-api-module-endpoints-summary)
25. [Known UI gaps](#25-known-ui-gaps)

---

## 1. Platform overview

| App | Path | Stack | Primary audience |
|-----|------|-------|------------------|
| **API** | `api/` | Laravel 12, Sanctum, Spatie roles/permissions | Backend for all clients; welcome page; developer portal |
| **Web** | `web/` | Vue 3 + Vite + Pinia | Staff / admin primary surface; also parent & student learner views |
| **Mobile** | `mobile/` | Expo SDK 54 / React Native | Parents & students; staff attendance marking only |

**Academic hierarchy**

```
academic_years → areas → school_classes → sections → students
```

Study groups are independent of sections. Students belong to a study group and optionally a section. Subjects link to study groups via many-to-many (`study_group_subject`).

---

## 2. User roles

| Role slug | Label | Typical use |
|-----------|--------|-------------|
| `superadmin` | Super Admin | Full system, permissions UI, View-as, release portal |
| `developer` | Developer | All API permissions + release portal; limited web nav |
| `admin` | Admin | Users, academic, leave, verify attendance, broadcasts |
| `principal` | Principal | Oversight: verify attendance, reports, approvals, broadcasts |
| `vice_principal` | Vice Principal | Same seeded permissions as principal |
| `section_head` | Section Head | Roster, verify attendance/marks, approve homework/online/broadcasts/leave |
| `class_incharge` | Class Incharge | Mark attendance, roster, post homework, broadcasts |
| `teacher` | Teacher | Mark attendance, enter marks, homework, online classes |
| `computer_operator` | Computer Operator | Users (create), academic, assessments, timetable, fees, attendance mark |
| `accountant` | Accountant | Fee vouchers / fee accounting |
| `parent` | Parent | Learner portal for linked children |
| `student` | Student | Learner portal for self |

Protected roles (`superadmin`, `developer`) cannot be assigned by non-superadmins.

---

## 3. Permissions catalog

There are **29** Spatie permissions. Defaults are seeded in `RolesAndPermissionsSeeder`. Superadmin can also grant **direct per-user** permissions.

| Permission | SA | DV | Admin | Principal / VP | Section head | Class incharge | Teacher | Operator | Accountant | Parent | Student |
|------------|:--:|:--:|:-----:|:--------------:|:------------:|:--------------:|:-------:|:--------:|:----------:|:------:|:-------:|
| `view_dashboard` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | |
| `manage_users` | ✓ | ✓ | ✓ | | | | | ✓ | | | |
| `manage_roles` | ✓ | ✓ | ✓ | | | | | | | | |
| `manage_permissions` | ✓ | ✓ | | | | | | | | | |
| `manage_academic_structure` | ✓ | ✓ | ✓ | | | | | ✓ | | | |
| `manage_student_roster` | ✓ | ✓ | ✓ | | ✓ | ✓ | | ✓ | | | |
| `mark_attendance` | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | |
| `verify_attendance` | ✓ | ✓ | ✓ | ✓ | ✓ | | | | | | |
| `view_attendance_reports` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ | | | |
| `view_own_attendance` | ✓ | ✓ | | | | | | | | ✓ | ✓ |
| `manage_assessments` | ✓ | ✓ | | | | | | ✓ | | | |
| `enter_marks` | ✓ | ✓ | | | | | ✓ | | | | |
| `verify_marks` | ✓ | ✓ | | | ✓ | | | | | | |
| `view_marks_reports` | ✓ | ✓ | ✓ | ✓ | ✓ | | | | | | |
| `view_own_marks` | ✓ | ✓ | | | | | | | | ✓ | ✓ |
| `post_homework` | ✓ | ✓ | | | | ✓ | ✓ | | | | |
| `approve_homework` | ✓ | ✓ | | | ✓ | | | | | | |
| `view_own_homework` | ✓ | ✓ | | | | | | | | ✓ | ✓ |
| `manage_timetable` | ✓ | ✓ | | | | | | ✓ | | | |
| `manage_online_classes` | ✓ | ✓ | | | | | ✓ | | | | |
| `approve_online_classes` | ✓ | ✓ | | | ✓ | | | | | | |
| `view_own_online_classes` | ✓ | ✓ | | | | | | | | ✓ | ✓ |
| `manage_fee_vouchers` | ✓ | ✓ | | | | | | ✓ | ✓ | | |
| `view_fee_accounting` | ✓ | ✓ | | | | | | | ✓ | | |
| `publish_user_broadcasts` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | | |
| `manage_leave_requests` | ✓ | ✓ | ✓ | | ✓ | | | | | | |
| `view_parent_dashboard` | ✓ | ✓ | | | | | | | | ✓ | |
| `view_student_dashboard` | ✓ | ✓ | | | | | | | | | ✓ |
| `approve_notification_dispatches` | ✓ | ✓ | | ✓ | ✓ | ✓ | | | | | |

**Note:** Web navigation often gates by **role lists** (see [Web navigation matrix](#22-web-navigation-matrix)). The API enforces Spatie `can()` / `hasRole()` on controllers. UI visibility and API permission can differ slightly (especially for `developer`).

---

## 4. Authentication & session

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| Login | ✓ `/login` | ✓ | `POST /api/login` | All roles |
| Login identifier: full email | ✓ | ✓ | ✓ | All |
| Login identifier: local part only (admission / CNIC) → `@efsc-ya.com` | ✓ | ✓ | ✓ | All |
| Password show / hide | ✓ | ✓ | — | All |
| Logout | ✓ | ✓ | `POST /api/logout` | Authenticated |
| Register | — | — | `POST /api/register` (exists; not primary UX) | Public |
| Sanctum token + inactivity check | via API | via API | ✓ | Authenticated |
| Public landing / marketing | ✓ `/` | — | Redirects to web app URL | Public |
| Link to Android APK download | ✓ (landing) | — | Welcome / redirect flow | Public |

---

## 5. Superadmin View-as / impersonation

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| View as **role** (header / picker) | ✓ | ✓ | `GET /api/view-as/roles` + middleware | `superadmin` only |
| View as **user** (impersonate) | ✓ (from Users admin) | ✓ | Apply View-as user middleware (`X-View-As-User`) | `superadmin` only |
| Exit / back to Super Admin | ✓ | ✓ (clear view-as) | Headers cleared | `superadmin` |
| Cannot target `superadmin` or `developer` | ✓ | ✓ | ✓ | Enforced |
| Admin routes blocked while impersonating | ✓ (`/admin/*`) | — | — | Impersonation session |

Effective role and permissions switch for subsequent API requests while View-as is active.

---

## 6. Administration — Users

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| Users admin page | ✓ `/admin/users` | — | CRUD `/api/users` | Nav: `superadmin`, `admin`, `computer_operator` |
| List / search / filter by role | ✓ | — | ✓ | Same |
| Create user | ✓ | — | ✓ | `superadmin`, `admin`, `computer_operator` |
| Edit user | ✓ | — | ✓ | `superadmin`, `admin` only (UI) |
| Assign roles (multi-role) | ✓ | — | Sync roles endpoint | Non-SA cannot assign `superadmin` / `developer` |
| Fields: name, email, password, roles | ✓ | — | ✓ | As above |

---

## 7. Administration — Permissions

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| Permissions admin | ✓ `/admin/permissions` | — | Roles/permissions APIs | `superadmin` only (route guard) |
| Tab: By role (sync role defaults) | ✓ | — | ✓ | `superadmin` |
| Tab: By user (direct permission grants) | ✓ | — | ✓ | `superadmin` |
| Roles CRUD | — | — | ✓ `apiResource('roles')` | Authenticated API (UI is SA) |
| Permissions CRUD | — | — | ✓ `apiResource('permissions')` | Authenticated API (UI is SA) |

---

## 8. Academic structure & enrollment

**Apps:** Web Configuration UI + API. **Not available on Mobile.**

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| Configuration page | ✓ `/admin/academic` | — | `/api/efsc/academic/*` | See below |
| Tab: **Structure** (Year → Area → Class → Section) | ✓ | — | ✓ | `manage_academic_structure` / roles: SA, AD, DV, CO |
| Create / edit / delete academic year (dates, current flag) | ✓ | — | ✓ | Academic managers |
| Create / edit / delete area + assign **section head** | ✓ | — | ✓ | Academic managers |
| Create / edit / delete class | ✓ | — | ✓ | Academic managers |
| Create / edit / delete section | ✓ | — | ✓ | Academic managers |
| Tab: **Subjects** (name + code catalog) | ✓ | — | ✓ | Academic managers |
| Tab: **Assign subjects** (study groups + subject checkboxes) | ✓ | — | ✓ | Academic managers |
| Create study group | ✓ | — | ✓ | Academic managers |
| Tab: **Enroll students** | ✓ | — | ✓ | `manage_student_roster` / roles: CO, SH, CI (+ SA/AD via structure) |
| Enroll filters: class / section / study group / search | ✓ | — | ✓ | Roster managers |
| Enroll fields: name, admission no., class, section, roll, study group, CNIC, father name/CNIC, guardian (or father-is-guardian) | ✓ | — | ✓ | Roster managers |
| Auto-provision student / parent user accounts on enroll | — | — | ✓ (service) | Via enroll API |
| Section-heads list for pickers | — | — | ✓ | Staff with academic access |

**Who sees Configuration in web nav:** roles with `canConfigure` — academic structure **or** student roster (SA, AD, DV, CO, SH, CI).

---

## 9. Staff dashboard

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| Staff home / dashboard | ✓ `/home` (staff) | — | `GET /efsc/dashboard` | Non-learners with `view_dashboard` |
| Pending counts (approvals, attendance verify, homework, leave, broadcasts) | Partial UI | — | ✓ | Staff |
| “Use the web app” message | — | ✓ | — | Staff **without** attendance access |

---

## 10. Attendance

**Workflow:** Mark (draft) → Submit → Verifier **Verify** → visible to parents/students (verified days).

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Staff attendance module | ✓ `/attendance` | ✓ Staff Attendance screen | `/efsc/attendance/*` | See tabs below |
| Tab: **Mark Attendance** | ✓ | ✓ | Create/update batch, submit | `mark_attendance` (CI, TE, CO + SA/DV) |
| Class / section / date pickers | ✓ | ✓ | ✓ | Markers |
| Statuses: **present** / **absent** / **leave** | ✓ | ✓ | ✓ | Markers |
| Save draft | ✓ | ✓ | ✓ | Markers |
| Submit batch | ✓ | ✓ | ✓ | Markers |
| Locked when submitted / verified | ✓ | ✓ | ✓ | Markers |
| Tab: **Pending approval** (verify) | ✓ | — | Verify endpoint | `verify_attendance` (Admin, Principal, VP, SH + SA/DV) |
| Expand batch → Approve / verify | ✓ | — | ✓ | Verifiers |
| Tab: **Attendance status** | ✓ | ✓ (View tab) | List batches | Markers (and related) |
| Tab: **Attendance Summary** | ✓ | ✓ (Summary) | Summary endpoint | `view_attendance_reports` or `verify_attendance` |
| Summary: area or class cumulative | ✓ | ✓ | ✓ | Reports roles |
| Summary: section per-student totals | ✓ | ✓ | ✓ | Reports roles |
| Monthly report | Learner UIs | Learner UIs | `reports/monthly` | Reports or `view_own_attendance` |
| Weekly report | — | — | `reports/weekly` | Same |
| Learner attendance (month picker, day statuses) | ✓ Parent view | ✓ | ✓ | `parent`, `student` |

**Mobile staff:** Mark / View / Summary only — **no verify tab** on mobile.

**Web nav visibility:** `canStaff` — SA, AD, Principal, VP, SH, CI, Teacher, CO.

---

## 11. Marks & assessments

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Create assessments | — (no dedicated UI) | — | `POST /efsc/assessments` | `manage_assessments` (Operator + SA/DV) |
| List assessments | Partial via marks flow | — | ✓ | manage / enter / view reports |
| Mark sheets list / create | ✓ (staff Marks) | — | ✓ | `enter_marks` (Teacher + SA/DV) |
| Enter marks / max / grade | ✓ `/marks` | — | Upsert entries | `enter_marks` |
| Study group filter | ✓ | — | ✓ | Staff markers |
| Save entries | ✓ | — | ✓ | Staff markers |
| **Notify parents** (queues approval) | ✓ | — | `notify-parents` | Staff with access |
| Verify mark sheet | — (no Verify button in UI) | — | `POST .../verify` | `verify_marks` (SH + SA/DV) |
| Learner marks list + detail | ✓ | ✓ | ✓ | `parent`, `student` |
| Notification types: published / subject failed / assessment summary | — | Inbox | Catalog + dispatch | Via approval pipeline |

**Web nav:** `canStaff` (same staff set as Attendance).

---

## 12. Homework

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Post homework | ✓ `/homework` | — | `POST /efsc/homework` | `post_homework` (CI, TE + SA/DV) |
| Fields: study group, title, body, due date, subject | ✓ | — | ✓ | Posters |
| Status `pending_approval` until approved | ✓ | — | ✓ | System |
| Approve homework | — (no dedicated UI; dashboard may count) | — | `POST .../approve` | `approve_homework` (SH + SA/DV) |
| Learner homework diary (approved only) | ✓ | ✓ | ✓ | `parent`, `student` |
| Notification: new homework | — | Inbox | Catalog | Via pipeline |

**Web nav:** `canStaff`.

---

## 13. Online classes

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Create online class link | ✓ `/online-classes` | — | `POST /efsc/online-classes` | `manage_online_classes` (Teacher + SA/DV) |
| Fields: class/section, label, URL, start time, minutes-before, schedule reminder | ✓ | — | ✓ | Managers |
| Approve online class | — (API only) | — | Approve endpoint | `approve_online_classes` (SH + SA/DV) |
| Learner list + open URL | ✓ | ✓ (Linking) | ✓ | `parent`, `student` |
| Notifications: reminder / approved | — | Inbox | Catalog | Via pipeline |

**Web nav:** `canStaff`.

---

## 14. Timetable & datesheet

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Staff manage slots / datesheet | Coming soon (`/timetable`); `TimetableView.vue` exists but not routed | — | Create slots & datesheet | `manage_timetable` (Operator + SA/DV) |
| Learner weekly timetable | Coming soon | ✓ | List slots | `parent`, `student` |
| Learner exam datesheet | Coming soon | ✓ | List datesheet | `parent`, `student` |
| ParentTimetableView.vue | File exists, not routed | — | — | — |
| Notification: datesheet published | — | Inbox | Catalog | Via pipeline |

**Web nav (staff):** `canTimetable` — SA, AD, CO, Teacher (still shows Coming soon page).

---

## 15. Fees

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Create / manage fee vouchers | Coming soon (`/fees`); `FeeView.vue` exists but not routed | — | Fee voucher APIs | `manage_fee_vouchers` (CO, Accountant + SA/DV) |
| Update voucher status | Coming soon | — | ✓ | `manage_fee_vouchers` / `view_fee_accounting` |
| Statuses: pending → submitted → verified | — | Displayed | ✓ | Fee roles |
| Learner fee list + status | Coming soon | ✓ | ✓ | `parent` (children); student via API |
| ParentFeesView.vue | File exists, not routed | — | — | — |
| Notifications: voucher available / status changed | — | Inbox | Catalog | Via pipeline |

**Web nav (staff):** `canFees` — SA, AD, Accountant, CO (Coming soon page).

---

## 16. Leave

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Submit leave request | ✓ Parent leave view | ✓ | Create leave | **`parent` only** (API enforces `hasRole('parent')`) |
| Fields: child, start/end dates, reason | ✓ | ✓ | ✓ | Parent |
| Decide leave (Approve / Reject) | ✓ Staff `/leave` | — | Decide endpoints | `manage_leave_requests` (Admin, SH + SA/DV) |
| Filter: all / pending / approved / rejected | ✓ | — | ✓ | Leave managers |
| List own requests | ✓ | ✓ | ✓ | Parent |
| Student Leave menu item | Visible in learner nav | Visible | Submit **forbidden** for student | UI only for student |
| Notification: leave decision to parent | — | Inbox | Catalog | Via pipeline |

**Web nav (staff Leave):** `canLeave` — SA, AD, SH (+ parents via learner nav). Principal/Teacher do **not** get Leave in staff nav by default.

---

## 17. Notifications & broadcasts

| Feature / option | Web | Mobile | API | Roles / permission |
|------------------|-----|--------|-----|-------------------|
| Create user broadcast | ✓ `/notifications` | — | User broadcast APIs | `publish_user_broadcasts` |
| Audience: **general** | ✓ | — | ✓ | Publishers |
| Audience: **scoped** (area / class / section / study group) | ✓ | — | ✓ | Publishers |
| Audience: **individual** student | ✓ | — | ✓ | Publishers |
| Title + body | ✓ | — | ✓ | Publishers |
| Approval rules (general vs scoped) | ✓ | — | Approval service | General often needs approval unless Admin/SA/DV; scoped may auto-approve for Admin/SH/SA/DV |
| Approve / reject pending broadcasts | ✓ Notifications + Approvals | — | ✓ | Approvers (Admin/SA/DV; in-scope SH for scoped) |
| System notification dispatches | ✓ `/approvals` | — | Dispatch APIs | `approve_notification_dispatches` (Principal, VP, SH, CI + SA/DV) |
| In-app inbox / mark as read | ✓ Parent notifications | ✓ Feed / Notifications | User notification APIs | Authenticated learners (and staff as applicable) |
| Device tokens (FCM) | — | Stub | Register/delete tokens | Authenticated — **push not wired** |

### Seeded notification feature catalog

| Feature key module | Name |
|--------------------|------|
| attendance | Absent alert to parents |
| marks | Marks published to parents |
| marks | Subject failed alert to parents |
| marks | Assessment summary to parents |
| timetable | Datesheet published |
| homework | New homework posted |
| online_class | Online class reminder |
| online_class | Online class approved |
| fee | Fee voucher available |
| fee | Fee voucher status changed |
| events | Event / announcement broadcast |
| leave | Leave decision to parent |

**Web nav:** `canBroadcasts` — SA, AD, Principal, VP, SH, CI, Teacher, CO.

---

## 18. Approvals

| Feature / option | Web | Mobile | API | Roles |
|------------------|-----|--------|-----|-------|
| Approvals page | ✓ `/approvals` | — | Related approve endpoints | Staff only (`staffOnly` guard) |
| Pending user broadcasts | ✓ | — | ✓ | Approvers per broadcast rules |
| Pending system notification dispatches (payload JSON) | ✓ | — | ✓ | `approve_notification_dispatches` |
| Approve / reject actions | ✓ | — | ✓ | Same |

**Web nav:** `canApprove` — SA, AD, Principal, VP, SH, CI.

---

## 19. Learner portal (Parent / Student)

Available on **Web** and **Mobile** for roles `parent` and `student`.

| Screen / option | Web | Mobile | Roles | Notes |
|-----------------|-----|--------|-------|-------|
| Home / child pick | ✓ `/home` | ✓ Home | Parent (multi-child); Student (self) | Children avatars; general announcements; unread count |
| Child / self dashboard | ✓ `/dashboard` | ✓ Dashboard | After child select (parent) or self | Today’s timetable snippet; recent homework; unread |
| Homework | ✓ | ✓ | Parent, Student | Approved posts only |
| Marks | ✓ | ✓ | Parent, Student | List + detail |
| Attendance | ✓ | ✓ | Parent, Student | Monthly verified days |
| Timetable | Coming soon | ✓ | Parent, Student | Weekly slots + datesheet on mobile |
| Notifications / alerts | ✓ | ✓ | Parent, Student | Mark read |
| Fees | Coming soon | ✓ | Parent, Student | Voucher list + status on mobile |
| Online class | ✓ | ✓ | Parent, Student | Open meeting URL |
| Leave | ✓ | ✓ | Parent (submit); Student (UI only) | API blocks student submit |
| Switch child | ✓ | ✓ | Parent | Clears selection / returns to home |
| Learner API hub | — | — | `GET /efsc/learner/dashboard?include=...` | Both apps |

Parents must select a child before child-scoped web routes (`requiresChild`).

---

## 20. Developer release portal & mobile distribution

| Feature / option | Web SPA | Mobile | API / Blade | Roles |
|------------------|---------|--------|-------------|-------|
| Hidden portal login | — | — | `/sys/portal-access` | `developer`, `superadmin` |
| Release settings | — | — | `/sys/portal-access/releases` | Same |
| Set web app URL | — | — | ✓ | Same |
| Android version name / code | — | — | ✓ | Same |
| Optional iOS version / build | — | — | ✓ | Same |
| Release notes | — | — | ✓ | Same |
| Upload APK / IPA | — | — | ✓ | Same |
| Public mobile version JSON | — | Consumes | `GET /api/mobile/version` | Anyone |
| In-app APK update prompt | — | ✓ | Via version API | All mobile users |
| OTA Expo Updates | — | Code present | Needs EAS `updates.url` | — |
| Welcome / download APK | Landing links | Install APK | API welcome / storage | Public |

---

## 21. Platform / ops (API host)

| Feature / option | Web SPA | Mobile | API host | Roles |
|------------------|---------|--------|----------|-------|
| Laravel health dashboard | — | — | `/dashboard`, `/dashboard/health*` | Authenticated session |
| Navigation CRUD (legacy) | Not used by Vue top-nav | — | `/api/navigations` | Authenticated API |
| Telescope | — | — | Telescope + `/telescope-login` helper | Dev / ops |
| Log viewer | — | — | `/logs` (local/dev) | Local |
| Activity log package | — | — | Spatie activity log | Ops |
| Health checks (DB connections, table sizes, etc.) | — | — | Health routes | Ops / secret token where configured |

---

## 22. Web navigation matrix

What each role **sees** in the top nav (role-list gating in `App.vue` / `useRoles.js`). Learners use the learner template instead.

### Staff template

| Nav item | SA | DV | AD | PR | VP | SH | CI | TE | CO | AC |
|----------|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Users | ✓ | | ✓ | | | | | | ✓ | |
| Configuration | ✓ | ✓* | ✓ | | | ✓* | ✓* | | ✓ | |
| Permissions | ✓ | | | | | | | | | |
| Approvals | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | | | |
| Attendance | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Marks | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Homework | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Timetable | ✓ | | ✓ | | | | | ✓ | ✓ | |
| Online | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Fees | ✓ | | ✓ | | | | | | ✓ | ✓ |
| Notifications | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Leave | ✓ | | ✓ | | | ✓ | | | | |

\* Configuration via `canConfigure` (academic structure and/or roster permissions/roles).  
**Developer:** has all API permissions but is **not** in most `canStaff` / `canBroadcasts` lists — typically sees Dashboard + Configuration unless given additional roles or navigating by URL.

### Learner template (`parent` / `student`)

Home, Dashboard, Homework, Marks, Attendance, Timetable, Notifications, Fees, Online, Leave (+ **Switch child** for parent).

---

## 23. Mobile feature matrix

| Feature | Parent / Student | Staff with `mark_attendance` or `computer_operator` | Other staff | Superadmin |
|---------|------------------|-----------------------------------------------------|-------------|------------|
| Login / logout | ✓ | ✓ | ✓ | ✓ |
| In-app APK update check | ✓ | ✓ | ✓ | ✓ |
| View-as role picker | — | — | — | ✓ |
| View-as user (impersonate) | — | — | — | ✓ |
| Home + child pick | ✓ | If View-as learner | — | Via View-as |
| Dashboard, Homework, Marks, Attendance, Timetable, Notifications, Fees, Online Class, Leave, Switch child | ✓ | If View-as learner | — | Via View-as |
| Staff Attendance (Mark / View / Summary) | — | ✓ | — | Via View-as |
| Other staff modules | — | — | Message: use web app | Via View-as / web |

---

## 24. API module endpoints summary

All school modules live under `/api/efsc/*` (authenticated Sanctum group unless noted).

| Module | Endpoints (high level) |
|--------|------------------------|
| Auth | `POST /login`, `POST /register`, `POST /logout`, `GET /user` |
| View-as | `GET /view-as/roles` |
| Users / roles / permissions | CRUD users; roles; permissions; assign/remove |
| Dashboard | `GET efsc/dashboard`, `GET efsc/learner/dashboard` |
| Academic | years, areas, section-heads, classes, sections, study-groups, subjects |
| Students | list, enroll/create |
| Attendance | batches CRUD/submit/verify, summary, monthly/weekly reports |
| Assessments / marks | assessments, mark-sheets, entries, verify, notify-parents |
| Timetable | slots, datesheet |
| Homework | list, create, approve |
| Online classes | list, create, approve |
| Fees | vouchers create/list/status |
| Leave | request, list, decide |
| Broadcasts / notifications | user broadcasts, approvals, inbox, device tokens |
| Mobile version | `GET /api/mobile/version` (public) |

---

## 25. Known UI gaps

| Capability | API | Web UI | Mobile UI |
|------------|-----|--------|-----------|
| Create assessments | ✓ | Missing dedicated UI | — |
| Verify mark sheets | ✓ | Missing Verify button | — |
| Approve homework | ✓ | No dedicated approve UI | — |
| Approve online classes | ✓ | No dedicated approve UI | — |
| Timetable manage / learner (web) | ✓ | Coming soon (views exist unwired) | Learner read ✓ |
| Fee manage / learner (web) | ✓ | Coming soon (views exist unwired) | Learner list ✓ |
| Push notifications (FCM) | Token stub | — | Not wired |
| Demo school data seeder | Empty | Manual via Configuration | — |
| OTA Expo Updates | — | — | Needs EAS URL setup |

---

## Quick workflow reference

```
Attendance:     mark → submit → verify → parent/student view
Marks:          assessment → mark sheet → enter → verify → notify (approval)
Homework:       create → section_head approve → learner visible
Online class:   create → section_head approve → learner visible
Broadcasts:     create → approval if required → publish
Leave:          parent request → admin/section_head decide → notify
Fees/timetable: API ready; staff web mostly Coming soon; mobile learner read works
```

---

*Generated from the EFSC-YA codebase (API seeders, web router/nav, mobile screens, and controllers). Access combines Spatie permissions and app-specific role gates; when in doubt, the API permission check is authoritative for data operations.*
