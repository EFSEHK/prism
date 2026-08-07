# EFSC-YA — App Progress Report

Simple status of what is **done** across the three apps. Product name: **EFSC-YA**. Local folder may still be `prism/`.

| App | Stack | Path |
|-----|-------|------|
| **API** | Laravel 12 + Sanctum + Spatie roles | `api/` |
| **Web** | Vue 3 + Vite + Pinia | `web/` |
| **Mobile** | Expo 54 + React Native (standalone APK) | `mobile/` |

Legend: **Done** · **Partial** · **Missing** · **N/A** (not intended for that client)

---

## Feature matrix

| Feature | API | Web | Mobile |
|---------|-----|-----|--------|
| Login / logout (Sanctum token) | Done | Done | Done |
| Register | Done | Missing | Missing |
| Role-based access & permissions | Done | Done | Done (catalog + permissions) |
| View-as role / impersonate user (superadmin) | Done | Done | Done |
| Module catalog (live / coming soon / disabled) | Done | Done | Done |
| Staff dashboard | Done | Done | Done (feature grid) |
| Parent / student (learner) dashboard | Done | Done | Done |
| Users CRUD | Done (no delete) | Done (create/edit) | Partial (list only) |
| Roles & permissions admin | Done | Done | Partial (read-only; assign on web) |
| Apps / module toggles | Done | Done | N/A (uses catalog) |
| Academic structure (years, areas, classes, sections) | Done | Done | Done |
| Subjects & study-group subject assign | Partial (create/list; limited update/delete) | Done (create + assign) | Done (create + assign) |
| Student enroll / roster | Partial (list/create/search; no edit/delete) | Done (enroll) | Done (enroll) |
| Teachers as separate CRUD | Missing (users + roles / CSV) | Missing | Missing |
| Attendance mark → submit → verify | Done | Done | Done (staff) |
| Attendance reports (summary / monthly) | Done | Done | Done (parent monthly; staff summary) |
| Assessments & mark sheets | Done | Done | Partial (view / verify; full enter on web) |
| Homework post + approve/reject | Done (no file attach) | Done | Done |
| Timetable slots | Partial (list/create) | Done | Done (view) |
| Exam datesheet | Partial (list/create) | Done | Done (view) |
| Online class links + approve | Done | Done | Done |
| Online class reminders (scheduler) | Done | N/A | N/A |
| Fee vouchers (create, status, PDF) | Done | Done | Done (view + mark submitted) |
| Fee deposits | Partial (AIMS import only) | Via AIMS import | Missing |
| Broadcasts / announcements + approval | Done | Done | Done (view; staff create on web/approvals) |
| Approvals queue (broadcasts + notification dispatches) | Done | Done | Done |
| Leave requests (parent submit / staff decide) | Done | Done | Done |
| In-app notifications | Done | Done | Done |
| Push notifications (FCM) | Stub (logs only) | Missing | Missing |
| Device token register | Done | N/A | Not wired yet |
| AIMS CSV import | Done | Done | N/A |
| Mobile version / APK update endpoint | Done | Landing download link | Done (in-app check) |
| Developer release portal (APK upload) | Done | N/A | N/A |
| Dedicated Reports page | Partial (attendance endpoints) | Missing | Missing |
| Settings page | N/A | Missing (via Admin) | Missing |

---

## API (`api/`) — what’s done

School APIs live under **`/api/efsc/*`**. Auth and platform APIs under **`/api/*`**.

### Platform
- Sanctum login / logout / register; multi-device tokens
- Users, roles, permissions, navigations
- Module catalog (`platform=web|mobile`) and module enablement (`/efsc/apps`)
- View-as role/user middleware
- Public `GET /api/mobile/version` for APK update metadata
- Dev portal for uploading release APKs / version settings
- Health checks, Telescope / log viewer (local)

### School modules
- Staff + learner dashboards
- Academic years → areas → classes → sections; study groups & subjects
- Students: list, create (auto-provisions student/parent users), search
- Attendance batches with draft → submit → verify + monthly/weekly reports
- Assessments, mark sheets, entries, verify, notify parents
- Homework create + approve/reject
- Timetable slots + datesheet (create/list)
- Online classes + approval + scheduled reminders
- Fee vouchers (optional PDF) + status updates; deposits via AIMS import
- Broadcasts with audience scoping + approval
- Leave requests
- Notification dispatch approval pipeline + in-app inbox
- AIMS CSV import: students, attendance, fee vouchers/deposits, test/exam results

### Known API gaps
- FCM push is stubbed (in-app rows still created)
- No student/staff REST update-delete in places; homework attachments unused
- Staff/Department models exist for import/seed, not full REST CRUD

---

## Web (`web/`) — what’s done

Full staff/admin portal and parent/student learner UI.

### Public & auth
- Landing page (brand, web sign-in, Android APK download from API)
- Login (email / admission no. / CNIC)
- Role-aware nav from module catalog; Coming Soon gate when module status says so

### Admin portal
- Apps (module visibility per role)
- Users (create/edit + roles; impersonate)
- Permissions matrix (superadmin)
- Academic configuration (structure, subjects, assign, enroll)
- AIMS CSV import + logs

### Staff operations
- Dashboard with pending widgets
- Attendance, homework, marks, timetable/datesheet, online classes
- Fees, notifications/broadcasts, approvals, leave decisions

### Parent / student
- Child picker home → child dashboard
- Homework, marks, attendance (monthly), timetable, online links
- Fees (view + mark submitted), leave submit, notifications/announcements

### Known web gaps
- No standalone Reports or Settings pages
- Students create-only (no edit/delete UI)
- No separate Teachers entity UI
- iOS download marked “coming later”
- Some unused orphan views (`ParentFeedView`, `ParentAlertsView`)

---

## Mobile (`mobile/`) — what’s done

Standalone Android app (`com.school.efscya`). Custom tab + side menu (not Expo Go for production).

### Auth & shell
- Login / session restore / logout
- Side menu driven by `/efsc/modules?platform=mobile`
- Superadmin view-as
- Startup update checks (OTA if configured, then APK version check)

### Learner (parent / student)
- Parent child picker + dashboard grid
- Homework, marks, attendance report, timetable + datesheet
- Notifications + broadcasts, fees, online class links, leave

### Staff (when modules are live on mobile)
- Attendance mark / verify / summary
- Homework post + approve; marks view/verify
- Online / leave approve; approvals inbox
- Users list; permissions list (read-only)
- Academic configuration + enroll students

### Known mobile gaps
- Full marks entry UI left to web
- User create/edit and permission assignment left to web
- FCM / push not wired
- EAS OTA URL may still need wiring for JS-only silent updates
- Production testing expects **installed APK**, not Expo Go

---

## Roles covered

| Role | API | Web | Mobile |
|------|-----|-----|--------|
| superadmin / developer | Done | Done | Done (view-as + admin modules) |
| admin / principal / VP / section head / class incharge / teacher | Done | Done | Done (module-gated) |
| computer operator / accountant | Done | Done (permission/module gated) | Partial (same catalog rules) |
| parent | Done | Done | Done |
| student | Done | Done (learner shell) | Done |

---

## Bottom line

| Area | Verdict |
|------|---------|
| **Core school ops (attendance, marks, homework, timetable, fees, leave, broadcasts)** | Done end-to-end on **API + Web**; **Mobile** covers learner suite and key staff workflows |
| **Admin (users, permissions, academic setup, AIMS, apps)** | Done on **API + Web**; **Mobile** has lighter config / read-only admin |
| **Push notifications** | Not production-ready (FCM stub) |
| **Distribution** | Web SPA + Android APK via API welcome page / in-app update |
