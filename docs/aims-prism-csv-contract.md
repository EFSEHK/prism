# AIMS → PRISM CSV Format Contract

One-directional sync: AIMS exports CSV files from `/export-for-sap`; PRISM imports them at `/admin/aims-import`.

- **Encoding:** UTF-8 with BOM (AIMS export); PRISM accepts UTF-8 CSV
- **Delimiter:** comma (RFC 4180 via `fputcsv`)
- **Student match:** `student_uid` → PRISM `admission_no`; fallback `student_cnic` → PRISM `cnic`
- **Attendance status map:** AIMS `1` Present → `present`; `2` Late → `present`; `3` Absent → `absent`; `4` Leave → `leave`

---

## 1. Students — `students_prism_{timestamp}.csv`

**AIMS route:** `GET /export/students-prism`

| Column | Required | Type | Source (AIMS) | PRISM target |
|--------|----------|------|---------------|--------------|
| `admission_no` | yes | string | `students.uid` | `students.admission_no` |
| `cnic` | no | string | `students.cnic` (digits) | `students.cnic` |
| `full_name` | yes | string | `student_information.name` | `first_name` + `last_name` |
| `class_label` | yes | string | `{class} {section} {study_group}` uppercased | `AcademicStructureImportService` |
| `roll_no` | no | string | `student_academic_groups.roll_no` | `students.roll_no` |
| `status` | yes | string | literal `ADMITTED` | only `ADMITTED` rows imported |

**Class label** must match: `^(\d+(?:ST|ND|RD|TH))\s+(\S+)\s+(BOYS|GIRLS)$` (e.g. `6TH GREEN BOYS`).

---

## 2. Daily attendance — `daily_attendance_prism_{timestamp}.csv`

**AIMS route:** `GET /export/daily-attendance` (archive tiers included)

| Column | Required | Type | Notes |
|--------|----------|------|-------|
| `student_uid` | yes | string | Match → `admission_no` |
| `student_r_uid` | no | string | informational |
| `student_cnic` | no | string | Fallback match → `cnic` |
| `student_id` | no | int | informational (AIMS PK) |
| `attendance_date` | yes | date | `Y-m-d` |
| `attendance_status` | yes | int | `1`–`4` per AIMS |
| `status` | yes | int | Row active; skip if `0` |

PRISM groups by `(section_id, attendance_date)` → `attendance_batches` with status `submitted`.

---

## 3. Fee vouchers — `fee_vouchers_prism_{timestamp}.csv`

**AIMS route:** `GET /export/fee-vouchers-prism?voucher_month=m-Y`

| Column | Required | Type | Notes |
|--------|----------|------|-------|
| `student_uid` | yes | string | |
| `student_r_uid` | no | string | |
| `student_cnic` | no | string | |
| `student_id` | no | int | |
| `voucher` | yes | int | AIMS voucher number → `external_voucher` |
| `voucher_type` | no | string | `monthly` / `installment` |
| `voucher_no` | no | string | |
| `voucher_month` | yes | date | `Y-m-d` (1st of month) |
| `due_date` | no | date | |
| `voucher_status` | yes | int | `1` unpaid / `2` paid |
| `total_due` | yes | decimal | Sum of `fee_type=0` accounts |
| `total_paid` | yes | decimal | Sum of `fee_type=1` accounts |

Upsert key: `(student_id, external_voucher)`.

---

## 4. Fee deposits — `fee_deposits_prism_{timestamp}.csv`

**AIMS route:** `GET /export/fee-deposits-prism?deposit_month=m-Y`

| Column | Required | Type | Notes |
|--------|----------|------|-------|
| `student_uid` | yes | string | |
| `student_r_uid` | no | string | |
| `student_cnic` | no | string | |
| `student_id` | no | int | |
| `voucher` | yes | int | Links to voucher |
| `amount` | yes | decimal | Deposit amount |
| `fee_date` | yes | date | Payment date |
| `voucher_month` | no | date | From linked voucher |
| `voucher_status` | no | int | From linked voucher |

Unique key: `(student_id, external_voucher, fee_date, amount)`.

---

## 5. Test results — `student_tests_prism_{timestamp}.csv`

**AIMS route:** `GET /export/student-tests` (same columns; PRISM filename prefix optional)

| Column | PRISM mapping |
|--------|---------------|
| `student_uid` / `student_cnic` | Resolve student |
| `subject` | `subjects.name` (firstOrCreate) |
| `test_number` | `assessments.number` where `type=test` |
| `test_date` | `assessments.held_on` |
| `total_marks` | `mark_entries.max_marks` |
| `obtained_marks` | `mark_entries.marks_obtained` |
| `status` | Skip if `inactive` |

Assessment key: `(type=test, number, held_on)`. Mark sheet key: `(assessment_id, study_group_id, subject_id)`.

---

## 6. Exam results — `student_exams_prism_{timestamp}.csv`

**AIMS route:** `GET /export/student-exams`

| Column | PRISM mapping |
|--------|---------------|
| `exam_name` | `assessments.name` where `type=exam` |
| `exam_date` | `assessments.held_on` |
| (other columns) | Same as test results |

Assessment key: `(type=exam, name, held_on)`.

---

## Duplicate handling

| Type | Policy |
|------|--------|
| Students | `updateOrCreate` by `admission_no` |
| Attendance | Skip `verified` batches; replace `draft`/`submitted` |
| Fee vouchers | Upsert by `(student_id, external_voucher)` |
| Fee deposits | Skip if unique key exists |
| Results | Upsert `mark_entries` by `(mark_sheet_id, student_id)` |
