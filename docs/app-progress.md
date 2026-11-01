# EFSC-YA — Features

School platform for staff, parents, and students.

| App | Stack | Folder |
|-----|-------|--------|
| **API** | Laravel 12 + Sanctum + roles | `api/` |
| **Web** | Vue 3 + Vite + Pinia | `web/` |
| **Mobile** | Expo / React Native (Android APK) | `mobile/` |

**Status:** Done · Partial · Missing · N/A

---

## At a glance

| Area | Status |
|------|--------|
| Auth, roles, dashboards | Done (API + Web + Mobile) |
| School ops (attendance, marks, homework, timetable, fees, leave, broadcasts) | Done end-to-end; Mobile lighter on some staff flows |
| Admin (users, permissions, academic setup, Apps, AIMS) | Done on API + Web; Mobile lighter |
| Push notifications (FCM) | Not ready (stub only) |

---

## Features

### Auth & access
| Feature | API | Web | Mobile |
|---------|-----|-----|--------|
| Login / logout | Done | Done | Done |
| Register | Done | Missing | Missing |
| Change password | Done | Done | Done |
| Roles & permissions | Done | Done | Done (read / catalog) |
| View-as role / impersonate (superadmin) | Done | Done | Done |
| Module catalog (live / coming soon / disabled) | Done | Done | Done |

### Dashboards
| Feature | API | Web | Mobile |
|---------|-----|-----|--------|
| Staff dashboard | Done | Done | Done |
| Parent home (child picker) | Done | Done | Done |
| Student / child dashboard | Done | Done | Done |

### Admin
| Feature | API | Web | Mobile |
|---------|-----|-----|--------|
| Users (create / edit; no delete) | Done | Done | Partial (list only) |
| Permissions matrix | Done | Done | Partial (read-only) |
| Apps / module toggles | Done | Done | N/A |
| Academic structure (years, areas, classes, sections) | Done | Done | Done |
| Subjects & study groups | Partial | Done | Done |
| Student enroll / roster | Partial | Done | Done |
| Teachers as separate CRUD | Missing | Missing | Missing |
| AIMS CSV import | Done | Done | N/A |

### School operations
| Feature | API | Web | Mobile |
|---------|-----|-----|--------|
| Attendance (mark → submit → verify) | Done | Done | Done (staff) |
| Attendance reports | Done | Done | Done |
| Assessments & mark sheets | Done | Done | Partial (view / verify) |
| Homework (post + approve / reject) | Done | Done | Done |
| Timetable | Partial | Done | Done (view) |
| Exam datesheet | Partial | Done | Done (view) |
| Online class links + approve | Done | Done | Done |
| Online class reminders (scheduler) | Done | N/A | N/A |
| Fee vouchers (view / status / PDF) | Done | Done | Done |
| Fee deposits | Partial (AIMS only) | Via AIMS | Missing |
| Broadcasts + approval | Done | Done | Done |
| Approvals queue | Done | Done | Done |
| Leave (submit / decide) | Done | Done | Done |
| In-app notifications | Done | Done | Done |
| Push notifications (FCM) | Stub | Missing | Missing |

### Distribution
| Feature | API | Web | Mobile |
|---------|-----|-----|--------|
| APK version / update check | Done | Landing download | Done |
| Developer release portal | Done | N/A | N/A |
| Dedicated Reports page | Partial | Missing | Missing |
| Settings page | N/A | Missing | Missing |

---

## Roles

| Role | Covered |
|------|---------|
| superadmin / developer | All apps |
| admin / principal / VP / section head / class incharge / teacher | All apps (module-gated) |
| computer operator / accountant | Web + API; Mobile via same catalog |
| parent / student | All apps |

---

## Still open

- Push (FCM) not production-ready
- Full marks entry on Mobile → use Web
- User create/edit & permission assign on Mobile → use Web
- No Teachers entity; no standalone Reports / Settings pages
- Some API create/list only (subjects, students, timetable, datesheet)
