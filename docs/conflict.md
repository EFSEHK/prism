# Audit: SuperAdmin Dashboard Module Visibility (Mobile vs Web)

**Status:** Resolved (implementation 2026-07-12). Prior read-only findings retained below for history; see **Resolution** at the top.

---

## Resolution (post-implementation)

Both Prism web and Prism mobile now consume **`GET /api/efsc/modules`** (`App\Services\ModuleCatalogService`). Hardcoded mobile `FEATURE_READY` was removed.

### SuperAdmin tile-by-tile parity (after fix)

| Module | Enabled on Prism web? | Enabled on Prism mobile? | Source of truth |
|--------|----------------------|---------------------------|-----------------|
| Dashboard | Yes | Yes | Catalog |
| Users | Yes | Yes (Coming soon screen) | Catalog |
| Configuration | Yes | Yes (Coming soon screen) | Catalog |
| Permissions | Yes | Yes (Coming soon screen) | Catalog |
| Approvals | Yes | Yes (Coming soon screen) | Catalog |
| Attendance | Yes | Yes (**real screen**) | Catalog |
| Marks | Yes | Yes (Coming soon screen) | Catalog |
| Homework | Yes | Yes (Coming soon screen) | Catalog |
| Online | Yes | Yes (Coming soon screen) | Catalog |
| Timetable | Yes | Visible, **greyed** (Coming soon) | Catalog `coming_soon` |
| Fees | Yes | Visible, **greyed** (Coming soon) | Catalog `coming_soon` |
| Notifications | Yes | Yes (Coming soon screen) | Catalog |
| Leave | Yes | Yes (Coming soon screen) | Catalog |

**Backlog (not a permission mismatch):** mobile screens still missing for all staff modules except Attendance — tracked in `mobile/features.js` → `PENDING_MOBILE_SCREENS`.

**CMS hub** (`cms-web` Navboard / AMS / LMS / …) remains a separate product and was not changed.

---

## Executive summary (original audit)

There are **two different products** involved in the reported symptom. Conflating them explains the AMS/LMS vs Attendance mismatch.

| Product | Path | SuperAdmin “module board” |
|---------|------|---------------------------|
| **Campus Management System (CMS hub)** | `D:\laragon\www\cms-web\` (not `cms\`) | Icon grid: AMS, LMS, CPD, HR, Finance, Feedback, Complaint, Inventory, Alumni |
| **EFSC-YA (Prism)** | `D:\laragon\www\prism\` (`web/`, `mobile/`, `api/`) | School modules: Attendance, Marks, Homework, Approvals, Users, etc. |

- **AMS / LMS / HR / Finance / Alumni / Feedback / Complaint / CPD** exist only on the **CMS** Navboard. They do **not** exist in Prism web or Prism mobile.
- Prior to the fix, Prism mobile showing **only Attendance enabled** was intentional local gating via a hardcoded `FEATURE_READY` map — not a failed sync with CMS.

**Within Prism (like-for-like):** SuperAdmin on **web** got a full role-gated nav. SuperAdmin on **mobile** saw the same *names* as icon tiles, but **only Attendance was `ready: true`**. That discrepancy is what the module catalog API fixes.

---

## Original investigation notes

See git history of this file prior to the Resolution section for the full Phase-1 audit (file paths, root-cause checklist, and CMS vs Prism scope clarification).
