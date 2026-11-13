# Feature 2: Server-Side Mock-Exam Scoring — Implementation & Verification Report

**Branch:** `feature/server-side-mock-exam-scoring`  
**Base Commit:** `4d6c730` (`feat: add administrator system status page`)  
**Feature Commit:** `66280cb` (`feat: calculate mock-exam results server-side`)  
**Status:** Committed to branch and pushed to `origin/feature/server-side-mock-exam-scoring`.  
**Coverage Scope:** Automated in-process unit tests, controller-level integration tests, database transaction/locking tests, view HTML rendering tests, and live HTTP curl probes. Interactive browser UI workflows are documented as Not Run.  
**Reference:** ISS-001 (Server-authoritative mock examination scoring)

---

## 1. Executive Summary

Feature 2 transitions mock-examination scoring from a client-authoritative model (where browser JavaScript computed scores and the server persisted them without verification) to an authoritative, server-enforced architecture.

Under the updated implementation:
1. The browser submits only user selections, question indexes, timeout flags, and time taken.
2. The server acquires an exclusive row lock (`SELECT ... FOR UPDATE`) on the examination material in `study_materials`.
3. The server enforces strict material ownership (`user_id === $_SESSION['user_id']`).
4. The server validates the entire stored question set in `raw_results_json` and verifies that every question has valid options and an in-range `correct_index`.
5. The server strictly validates incoming types (rejecting booleans, floats, strings, arrays, duplicates, or out-of-range option indices).
6. Authoritative grading is computed by `MockExamScorer::validateAndScore()` using the practice pass threshold of 70% and the integer rounding convention `round((correct / total) * 100)`.
7. All database persistence operations—material completion update, per-question answer records, result summary row, and audit log event—are executed within a single ACID transaction (`beginTransaction`, `commit`, `rollBack`).
8. Repeated or duplicate submissions on completed exams, as well as inconsistent state (existing results with a null `completed_at`), are rejected with HTTP 409 Conflict without altering record counts.
9. Frontend error handling visibly informs the user without unhandled exceptions, provides a direct link to existing results upon HTTP 409, and prevents duplicate submissions caused by button re-clicks or timer expiration.
10. This implementation prevents browser-supplied score fields from determining stored grades. It does not establish the factual accuracy of AI-generated answer keys and does not turn the application into a secure certification-exam platform.

---

## 2. Exact Files Modified & Created

| File | Status | Description |
|---|---|---|
| `web/controllers/MockExamScorer.php` | **Created** | Isolated pure unit for validating stored questions, validating submitted answers, and computing authoritative scores. |
| `web/controllers/MockExamController.php` | **Modified** | Implemented transactional handling, row locking (`FOR UPDATE`), strict ownership, 409 conflict checks, authoritative persistence, and structured HTTP error responses. |
| `web/assets/js/mock_exam.js` | **Modified** | Removed client-side score computation, added submission-in-progress guard, sanitized submission payload, and added visible error/conflict handling. |
| `web/mock_exam_results.php` | **Modified** | Hardened results rendering against null values, option index 0, timed-out flags, and unsubmitted questions. |
| `tests/test_server_scoring.php` | **Created** | Comprehensive test suite containing 35 automated unit, integration, transactional, and view render test cases. |
| `docs/feature-2-server-side-scoring.md` | **Created** | This comprehensive implementation and verification report. |

---

## 3. Architecture & Scoring Flow

```
[Browser: mock_exam.js]
        │
        │ 1. User clicks "Finish" or timer reaches zero
        │ 2. Sets isSubmitting = true, disables buttons, cancels timer
        │ 3. Assembles minimal payload (material_id, answers[], time_taken_seconds)
        │
        ▼ POST /api/mock_exam_results.php
[Web Server: MockExamController.php]
        │
        ├─► [Session Check]: Is $_SESSION['user_id'] set? (No -> 401 Unauthorized)
        ├─► [Method Check]: Is method POST? (No -> 405 Method Not Allowed)
        ├─► [Input Validation]: Are material_id, answers, and time_taken valid types? (No -> 400 Bad Request)
        │
        ▼ DB::getConnection()->beginTransaction()
        │
        ├─► SELECT id, user_id, exam_id, content_type, raw_results_json, completed_at
        │   FROM study_materials WHERE id = ? FOR UPDATE;
        │
        ├─► [Record Exists?]: Material found? (No -> 404 Not Found, rollBack)
        ├─► [Strict Ownership]: user_id === $_SESSION['user_id']? (No -> 403 Forbidden, rollBack)
        ├─► [Material Type]: content_type === 'questions'? (No -> 400 Bad Request, rollBack)
        ├─► [Completion Guard]: completed_at IS NULL? (No -> 409 Conflict, rollBack)
        ├─► [Inconsistent State Guard]: mock_exam_results count == 0? (No -> 409 Conflict, rollBack)
        │
        ▼ [MockExamScorer::validateAndScore()]
        │
        ├─► Validate stored questions (options array >= 2, correct_index in range) -> 422 if invalid
        ├─► Validate submitted answers (types, bounds, no duplicates) -> 400 if invalid
        ├─► Match selections against raw_results_json correct_index
        ├─► Calculate score_percentage = round((correct / total) * 100)
        ├─► Calculate passed = (score_percentage >= 70)
        │
        ▼ [Atomic Transaction Persistence]
        │
        ├─► 1. UPDATE study_materials SET score_percentage, correct_count, incorrect_count,
        │      passed, time_taken_seconds, user_answers_json, completed_at = NOW() WHERE id = ?;
        ├─► 2. INSERT INTO mock_exam_answers (material_id, question_index, selected_option, correct_option, timed_out)
        ├─► 3. INSERT INTO mock_exam_results (user_id, exam_id, material_id, score_percentage, correct_count, ...)
        ├─► 4. Capture result_id = $db->lastInsertId()
        ├─► 5. Analytics::log($userId, $examId, 'mock_exam_completed')
        │
        ▼ DB::commit()
        │
        ▼ 200 OK: Return JSON { status: true, material_id, result_id, score_percentage, passed, ... }
[Browser: mock_exam.js]
        │
        └─► Redirects to /mock_exam_results.php?id={material_id}
```

---

## 4. Request & Response Formats

### Client Submission Request Payload
```http
POST /api/mock_exam_results.php HTTP/1.1
Content-Type: application/json

{
  "material_id": 14,
  "answers": [
    { "question_index": 0, "selected": 2, "timed_out": false },
    { "question_index": 1, "selected": null, "timed_out": false },
    { "question_index": 2, "selected": 0, "timed_out": true },
    { "question_index": 3, "selected": 1, "timed_out": false }
  ],
  "time_taken_seconds": 360
}
```
*Note: Any client-supplied scoring parameters (`score_percentage`, `correct_count`, `incorrect_count`, `passed`, or `correct` answer keys) are ignored by the server.*

### Successful Server Response (HTTP 200 OK)
```json
{
  "status": true,
  "message": "Mock exam completed successfully",
  "material_id": 14,
  "result_id": 8,
  "score_percentage": 50,
  "correct_count": 2,
  "incorrect_count": 2,
  "total_questions": 4,
  "passed": false
}
```

### Duplicate Attempt Response (HTTP 409 Conflict)
```json
{
  "status": false,
  "error": "Mock exam attempt has already been completed",
  "material_id": 14
}
```

### Type Validation Failure Response (HTTP 400 Bad Request)
```json
{
  "status": false,
  "error": "Selected option for question_index 0 must be an integer or null"
}
```

---

## 5. Verification Inventory & Execution Summary

### Mandatory Checklist Coverage
| Check Description | Test Reference | Execution Mode | Outcome |
|---|---|---|---|
| **Admin submitting another user's examination** | T26 | In-process controller integration test (`user_role='admin'`, non-owner `user_id`) | **PASS (403 Forbidden)** |
| **Non-question material submission** | T32 | In-process controller integration test (`content_type='summary'`) | **PASS (400 Bad Request)** |
| **Malformed JSON** | T33 | In-process controller integration test (`"this-is-not-valid-json"`) | **PASS (400 Bad Request)** |
| **Duplicate submission with unchanged record counts** | T34 | Integration test pre/post querying `mock_exam_answers` and `mock_exam_results` | **PASS (409 Conflict, counts unchanged)** |
| **Browser display of unanswered and timed-out questions** | T35 | Output-buffered PHP execution of `mock_exam_results.php` with timeout/null badges | **PASS (HTML badges verified)** |
| **Browser regression checks for existing workflows** | Manual / Curl | HTTP status probes (`/index.php`, `/login.php`, `/admin-login.php`, `/system-status.php`) | **Bounded / Not Run (Interactive UI workflow not automated)** |

---

## 6. Detailed Test Suite Execution

**Execution Command:**
```powershell
Get-Content tests/test_server_scoring.php -Raw | docker compose exec -T web php
```
**Tested Commit:** `66280cb`

### Sanitised Execution Output
```text
========================================================
FEATURE 2: SERVER-SIDE SCORING COMPREHENSIVE TEST SUITE
========================================================

--- Section 1: Pure Unit Tests (MockExamScorer) ---
[PASS] T01: All correct answers yields 100% and passed=true
[PASS] T02: All incorrect answers yields 0% and passed=false
[PASS] T03: Exact 70% boundary passes (7 out of 10 correct)
[PASS] T04: Score below 70% fails (e.g. 6 out of 10 is 60%)
[PASS] T05: Score rounding rule: 2 out of 3 is 67% (fails) and 13 out of 19 is 68% (fails)
[PASS] T06: Option index 0 accepted and scored correctly
[PASS] T07: Null / omitted answer counted incorrect without warning
[PASS] T08: Timed-out answer counted incorrect even if selected matches correct_index
[PASS] T09: Empty submitted answers array accepted as all-unanswered
[PASS] T10: Rejects boolean selection true with HTTP 400
[PASS] T11: Rejects boolean selection false with HTTP 400
[PASS] T12: Rejects float selection 1.5 with HTTP 400
[PASS] T13: Rejects string selection '0' with HTTP 400
[PASS] T14: Rejects duplicate question index with HTTP 400
[PASS] T15: Rejects out-of-range option index with HTTP 400
[PASS] T16: Rejects out-of-bounds question index with HTTP 400
[PASS] T17: Rejects non-boolean timed_out with HTTP 400
[PASS] T18: Corrupt stored questions (missing correct_index) rejected with HTTP 422
[PASS] T19: Corrupt stored questions (options < 2) rejected with HTTP 422

--- Section 2: Integration & Transaction Tests (MockExamController) ---
[PASS] T20: Unauthenticated request rejected with HTTP 401
[PASS] T21: Non-POST request rejected with HTTP 405
[PASS] T22: Missing or non-integer material_id rejected with HTTP 400
[PASS] T23: Missing answers array rejected with HTTP 400
[PASS] T24: Invalid time_taken_seconds rejected with HTTP 400
[PASS] T25: Non-existent material_id rejected with HTTP 404
[PASS] T26: Strict ownership: submitting another user's material rejected with HTTP 403
[PASS] T27: Client-tampered scores are ignored and server calculates authoritative grade
[PASS] T28: Duplicate submission on completed material rejected with HTTP 409
Inconsistent state detected: material_id 32 has mock_exam_results but completed_at is NULL.
[PASS] T29: Inconsistent state (results exist but completed_at is NULL) rejected with HTTP 409
[PASS] T30: Transaction rollback preserves data integrity on failure
[PASS] T31: Database row lock verification (SELECT ... FOR UPDATE)
[PASS] T32: Non-question material submission (content_type='summary') rejected with HTTP 400
[PASS] T33: Malformed JSON payload rejected with HTTP 400
[PASS] T34: Duplicate submission does not alter database record counts
[PASS] T35: HTML render output verification for unanswered and timed-out questions

========================================================
TEST RESULTS: Total: 35 | Passed: 35 | Failed: 0
========================================================
```

### Transport-Level HTTP Probes (Curl)
In addition to the in-process test suite, live HTTP requests were sent against the Apache web server:
- `curl.exe -i -X POST http://localhost:8080/api/mock_exam_results.php`  
  **Result:** `HTTP/1.1 401 Unauthorized`, `{"status":false,"error":"Unauthorized"}`
- `curl.exe -i -X GET http://localhost:8080/api/mock_exam_results.php`  
  **Result:** `HTTP/1.1 405 Method Not Allowed`, `{"status":false,"error":"Invalid request method"}`

---

## 7. Deep-Dive on Specific Test Implementations

### T30: Transaction Rollback & Failure-Injection Verification
- **Failure Injection Method:** In test T30, an invalid option index (`selected: 9999`) was passed in a submission payload for a newly created material.
- **Controller Execution:** The controller started a database transaction (`$db->beginTransaction()`), acquired the row lock (`SELECT ... FOR UPDATE`), loaded the material, and invoked `MockExamScorer::validateAndScore()`. Because `9999` exceeded the available option count (4), `MockExamScorer` flagged validation failure (`http_code: 400`).
- **Rollback Trigger:** Upon detecting `!$scoring['valid']`, the controller executed `$db->rollBack()`, set HTTP 400, and returned the validation error.
- **Post-Rollback Integrity Checks:** Test T30 directly verified that:
  1. `study_materials.completed_at` remained strictly `NULL`.
  2. `study_materials.score_percentage` remained strictly `NULL`.
  3. `SELECT COUNT(*) FROM mock_exam_answers WHERE material_id = ?` returned exactly `0`.
  4. `SELECT COUNT(*) FROM mock_exam_results WHERE material_id = ?` returned exactly `0`.

### T31: Database Row-Lock Verification (`SELECT ... FOR UPDATE`)
- **Execution Method:** Test T31 tested database-level locking independently from the web layer:
  1. Opened two distinct PDO connections (`$pdo1` and `$pdo2`) to MySQL.
  2. `$pdo1` started a transaction and executed `SELECT id FROM study_materials WHERE id = ? FOR UPDATE`.
  3. `$pdo2` configured `SET innodb_lock_wait_timeout = 1`, started a transaction, and attempted to execute the identical `SELECT id ... FOR UPDATE` on the locked row.
  4. `$pdo2` was blocked by MySQL's InnoDB row lock and threw a `PDOException` with MySQL error 1205 (`Lock wait timeout exceeded`).
  5. Both connections executed `rollBack()`.
- **Note on Concurrency Scope:** This test verified InnoDB row-level locking behavior. Complete overlapping concurrent HTTP requests through Apache were not tested because PHP's default file-based session handler locks the session file (`/var/lib/php/sessions/sess_*`), automatically serializing requests originating from the same session before they reach the database.

---

## 8. Remaining Limitations & Boundaries

1. **No CSRF Token Protection:** The `/api/mock_exam_results.php` endpoint relies solely on the session cookie (`PHPSESSID`) without an anti-CSRF token. While state modification is restricted to materials owned by the authenticated user and idempotent once completed, an attacker could theoretically induce an unauthorized submission via cross-site request forgery.
2. **Client-Side Answer Key Exposure:** In `mock_exam.php`, the questions array rendered into inline JavaScript (`var examQuestions = ...`) includes `correct_index` and `explanation`. A technically proficient user can inspect client memory or DOM variables during the quiz to view correct answers before submitting. Server-side scoring prevents post-submission score alteration, but does not prevent pre-submission inspection.
3. **Client-Reported Time Taken:** `time_taken_seconds` is currently submitted by the client (validated as a non-negative integer). Server-side elapsed wall-clock time recording and attempt session tracking are not part of Feature 2.

---

## 9. Reversion Instructions

To revert the changes made in Feature 2, use standard Git revert:
```bash
git checkout feature/server-side-mock-exam-scoring
git revert 66280cb
```

> [!WARNING]
> Reverting the Git commit reverses only the application codebase. Any test rows created during test execution (such as test materials or result records in MySQL) remain in the database unless cleaned manually or reset via database container re-initialisation.
