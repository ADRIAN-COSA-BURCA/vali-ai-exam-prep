# Feature 2: Server-Side Mock-Exam Scoring — Implementation & Verification Report

**Branch:** `feature/server-side-mock-exam-scoring`  
**Base Commit:** `4d6c730` (`feat: add administrator system status page`)  
**Status:** Implementation Complete & Fully Verified (Pending User Approval to Commit)  
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
8. Repeated or duplicate submissions on completed exams, as well as inconsistent state (existing results with a null `completed_at`), are rejected with HTTP 409 Conflict.
9. Frontend error handling visibly informs the user without throwing unhandled exceptions, provides a direct link to existing results upon HTTP 409, and prevents duplicate submissions caused by button re-clicks or timer expiration.

---

## 2. Exact Files Modified & Created

| File | Status | Description |
|---|---|---|
| `web/controllers/MockExamScorer.php` | **Created** | Isolated pure unit for validating stored questions, validating submitted answers, and computing scores. |
| `web/controllers/MockExamController.php` | **Modified** | Implemented transactional handling, row locking (`FOR UPDATE`), strict ownership, 409 conflict checks, authoritative persistence, and structured HTTP error responses. |
| `web/assets/js/mock_exam.js` | **Modified** | Removed client-side score computation, added submission-in-progress guard, sanitized submission payload, and added visible error/conflict handling. |
| `web/mock_exam_results.php` | **Modified** | Made results rendering robust against null values, option index 0, timed-out flags, and unsubmitted questions. |
| `tests/test_server_scoring.php` | **Created** | Comprehensive test suite containing 31 automated unit, integration, transactional, and concurrency test cases. |
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

## 5. Automated Verification Results

All 31 automated test cases were executed inside the container environment using `tests/test_server_scoring.php`:

| ID | Test Scenario | Category | Result |
|---|---|---|---|
| **T01** | All correct answers yields 100% and passed=true | Unit / Scorer | **PASS** |
| **T02** | All incorrect answers yields 0% and passed=false | Unit / Scorer | **PASS** |
| **T03** | Exact 70% boundary passes (7 of 10 correct) | Unit / Scorer | **PASS** |
| **T04** | Score below 70% fails (6 of 10 correct is 60%) | Unit / Scorer | **PASS** |
| **T05** | Integer rounding rule: 2/3 is 67% (fail) and 13/19 is 68% (fail) | Unit / Scorer | **PASS** |
| **T06** | Option index 0 accepted and scored correctly | Unit / Scorer | **PASS** |
| **T07** | Null / omitted answer counted incorrect without warning | Unit / Scorer | **PASS** |
| **T08** | Timed-out answer counted incorrect even if selected matches correct | Unit / Scorer | **PASS** |
| **T09** | Empty submitted answers array accepted as all-unanswered | Unit / Scorer | **PASS** |
| **T10** | Rejects boolean selection `true` with HTTP 400 | Unit / Scorer | **PASS** |
| **T11** | Rejects boolean selection `false` with HTTP 400 | Unit / Scorer | **PASS** |
| **T12** | Rejects float selection `1.5` with HTTP 400 | Unit / Scorer | **PASS** |
| **T13** | Rejects string selection `"0"` with HTTP 400 | Unit / Scorer | **PASS** |
| **T14** | Rejects duplicate question index with HTTP 400 | Unit / Scorer | **PASS** |
| **T15** | Rejects out-of-range option index with HTTP 400 | Unit / Scorer | **PASS** |
| **T16** | Rejects out-of-bounds question index with HTTP 400 | Unit / Scorer | **PASS** |
| **T17** | Rejects non-boolean `timed_out` with HTTP 400 | Unit / Scorer | **PASS** |
| **T18** | Corrupt stored questions (missing `correct_index`) rejected with HTTP 422 | Unit / Scorer | **PASS** |
| **T19** | Corrupt stored questions (options < 2) rejected with HTTP 422 | Unit / Scorer | **PASS** |
| **T20** | Unauthenticated request rejected with HTTP 401 | Integration / Auth | **PASS** |
| **T21** | Non-POST request rejected with HTTP 405 | Integration / Protocol | **PASS** |
| **T22** | Missing or non-integer `material_id` rejected with HTTP 400 | Integration / Input | **PASS** |
| **T23** | Missing `answers` array rejected with HTTP 400 | Integration / Input | **PASS** |
| **T24** | Invalid `time_taken_seconds` rejected with HTTP 400 | Integration / Input | **PASS** |
| **T25** | Non-existent `material_id` rejected with HTTP 404 | Integration / Persistence | **PASS** |
| **T26** | Strict ownership: submitting another user's material rejected with HTTP 403 | Integration / Security | **PASS** |
| **T27** | Client-tampered scores ignored, authoritative score calculated & persisted | Integration / Integrity | **PASS** |
| **T28** | Duplicate submission on completed material rejected with HTTP 409 | Integration / Idempotency | **PASS** |
| **T29** | Inconsistent state (`mock_exam_results` exists, `completed_at` is NULL) rejected with HTTP 409 | Integration / Data Guard | **PASS** |
| **T30** | Transaction rollback preserves data integrity on failure | Integration / ACID | **PASS** |
| **T31** | Database row lock verification (`SELECT ... FOR UPDATE` blocks concurrent transactions) | Concurrency / DB Lock | **PASS** |

**Summary: 31 Total | 31 Passed | 0 Failed | 0 Not run**

---

## 6. Concurrency & Session-Locking Analysis

Two distinct layers of concurrency protection are active:
1. **PHP Session Serialisation:** PHP uses default file-based session handling (`session_start()`). When a user submits an exam request, PHP acquires an exclusive file lock on `/var/lib/php/sessions/sess_<session_id>`. Any rapid, concurrent requests originating from the *same authenticated browser session* are automatically queued and serialised at the web application layer before entering database logic.
2. **Database Row-Level Pessimistic Locking:** To guard against separate sessions, concurrent API clients, or non-blocking session handlers attempting to score the same material simultaneously, `MockExamController` opens an explicit transaction and executes `SELECT ... FROM study_materials WHERE id = ? FOR UPDATE`. In Test T31, two distinct database connections (`$pdo1` and `$pdo2`) were opened; `$pdo1` acquired the row lock, causing `$pdo2` to block until its timeout threshold was reached (`MySQL Error 1205: Lock wait timeout exceeded`). Once Connection 1 commits and sets `completed_at = NOW()`, any subsequent transaction immediately encounters the duplicate check and aborts with HTTP 409.

---

## 7. Remaining Limitations & Security Boundaries

While Feature 2 achieves complete server-side scoring authority and prevents grade manipulation, several architectural boundaries remain:
1. **No CSRF Token Protection:** The `/api/mock_exam_results.php` endpoint currently authenticates solely via the active `PHPSESSID` cookie without an anti-CSRF token. While state change is restricted to existing materials owned by the authenticated user and idempotent once completed, an attacker could theoretically induce an unauthorized submission via cross-site request forgery.
2. **Client-Side Answer Key Exposure:** In the existing examination UI (`mock_exam.php`), the full question bank—including `correct_index` and `explanation`—is serialized into inline JavaScript (`var examQuestions = ...`). A technically inclined student can inspect DOM variables during the exam to view correct answers before submitting. Server-side scoring prevents post-submission score alteration, but does not prevent pre-submission inspection.
3. **Client-Reported Time Taken:** `time_taken_seconds` is currently reported by the client (constrained to a non-negative integer). It does not represent server-verified wall-clock elapsed time. Implementing server-side exam session tracking, start timestamps, and elapsed-time verification is reserved for Feature 3.

---

## 8. Rollback Instructions

If rollback is required, restore the baseline commit `4d6c730`:
```bash
git checkout feature/server-side-mock-exam-scoring
git restore web/assets/js/mock_exam.js web/controllers/MockExamController.php web/mock_exam_results.php
rm -f web/controllers/MockExamScorer.php tests/test_server_scoring.php docs/feature-2-server-side-scoring.md
```
Or reset the branch:
```bash
git reset --hard 4d6c730
git clean -fd
```
*(No database schema migrations were applied; hence, no database rollback is required).*
