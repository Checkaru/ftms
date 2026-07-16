# Field Training Management System — Build Brief

> Give this to Claude Code as the opening prompt, and keep it in the repo as
> `PROJECT.md` so it survives between sessions.

---

## Context

Palestinian universities require students to complete a **field training**
placement (التدريب الميداني) at a host organisation — a fixed number of hours,
logged over a semester, confirmed by a supervisor at the host, then reviewed
and graded by an academic supervisor at the university.

Today this runs on paper logbooks and WhatsApp. Hours get disputed, sign-off
sheets get lost, and coordinators reconcile it all by hand at the end of term.

This is being built for the TAQAT IT Incubator training programme — TAQAT has
this exact problem themselves, so they are the first real user. Build it for
production use, not as a demo.

**Author:** Mohammed Emad Elrefy · github.com/Checkaru

## Stack

- **Laravel 13** (requires PHP 8.3+), MySQL 8
- **Blade** views, Tailwind, Vite
- Auth via a starter kit — whichever `laravel new` offers. Do not hand-roll login.
- Native **PHP backed enums**, cast on the models
- Pest or PHPUnit for the few tests that matter (see Testing below)
- No Livewire, no Inertia, no API layer, no queues. Server-rendered Blade only.

---

## The one design decision that matters

**Students self-report their hours. Field supervisors confirm them.**

Everything else here is CRUD. This is the part that carries the trust, and
every decision should protect it:

- A student logs a day: date, check-in, check-out, what they did.
- The entry is `pending` and counts for **nothing** until the field supervisor
  at the host organisation approves it.
- Only `approved` hours accrue toward the requirement. Never sum `pending`
  minutes into a student's progress total — not even greyed out. If a number
  appears next to "hours completed", it is approved hours.
- Supervisors approve or reject with a reason. Rejected entries stay visible
  to the student so they can correct and resubmit.
- Once approved, a student cannot edit the entry. Only the supervisor can
  revert it.

If this model is right, a coordinator can sign off a grade without chasing
anyone. If it's loose, this is a worse paper logbook.

---

## Actors and permissions

| Role enum | Arabic | Can do |
|---|---|---|
| `student` | طالب | Log own attendance, edit own `pending` entries, view own progress, view own evaluation once released |
| `field_supervisor` | المشرف الميداني | Approve/reject attendance for students placed at *their organisation only*, submit field evaluation |
| `academic_supervisor` | المشرف الأكاديمي | View assigned students' logs and hours, submit academic evaluation, set final grade |
| `coordinator` | منسق التدريب | Manage organisations, periods, placements, users; view everything; export reports |

Roles are a `UserRole` backed enum cast on `User`. Route-level middleware
gates the section; **Policies gate the row**. Both, always.

---

## Data model

Migrations, in this order. Use `foreignId()->constrained()` and be explicit
about delete behaviour.

```
users              (extend the starter kit's migration)
                   + role            UserRole enum, not null
                   + phone           nullable
                   + student_number  nullable, unique
                   + organization_id nullable, FK→organizations, nullOnDelete
                   + is_active       bool default true

organizations      name, sector, address, contact_name, contact_phone,
                   is_active bool default true

training_periods   name,                 -- "Summer 2026"
                   starts_on date, ends_on date,
                   required_hours int,   -- e.g. 180
                   is_open bool          -- only one open at a time

placements         student_id            FK→users, cascadeOnDelete
                   organization_id       FK→organizations, restrictOnDelete
                   period_id             FK→training_periods, cascadeOnDelete
                   field_supervisor_id   FK→users, nullOnDelete, nullable
                   academic_supervisor_id FK→users, nullOnDelete, nullable
                   status                PlacementStatus enum
                                         (active|completed|withdrawn)
                   unique(student_id, period_id)

attendance_logs    placement_id  FK→placements, cascadeOnDelete
                   work_date     date
                   check_in      time
                   check_out     time
                   minutes       int         -- computed on save, stored
                   tasks         string(1000)
                   status        LogStatus enum (pending|approved|rejected)
                   reviewed_by   FK→users, nullOnDelete, nullable
                   reviewed_at   timestamp nullable
                   reject_reason string(300) nullable
                   unique(placement_id, work_date)
                   index(placement_id, status)
                   index(status, work_date)

evaluations        placement_id  FK→placements, cascadeOnDelete
                   evaluator_id  FK→users, cascadeOnDelete
                   kind          EvaluationKind enum (field|academic)
                   scores        json          -- {"attendance":9,"skills":8}
                   total         decimal(5,2)
                   comments      text nullable
                   submitted_at  timestamp nullable
                   unique(placement_id, kind)
```

**Modelling rules — follow these:**

- Store `minutes` as an integer, computed once in the model (a `saving` hook
  or an action class). Never store decimal hours — `7.5` invites float drift
  across a 180-hour total. Expose hours via an accessor.
- `unique(placement_id, work_date)` **at the database level**. Duplicate-day
  entries are the obvious way to inflate hours; a validation rule alone loses
  the race under a double-submit.
- Everything hangs off `placement_id`, never `student_id`. A student can train
  again in a later period and their histories must not merge.
- `utf8mb4` / `utf8mb4_unicode_ci` (Laravel's default — don't change it).
  Arabic names and task descriptions.
- Cast `scores` to `array`, so the rubric can change per period without a
  migration. Keep the rubric definition in a config file.
- Set `$fillable` deliberately on every model — see the security section, this
  is the real vulnerability in this app.

**The query that matters** — put it on the `Placement` model:

```php
public function approvedMinutes(): int
{
    return $this->attendanceLogs()
        ->where('status', LogStatus::Approved)
        ->sum('minutes');
}
```

Nothing anywhere sums `pending`.

---

## Security — what Laravel does NOT do for you

Laravel gives you CSRF, `bcrypt` hashing, Blade escaping and prepared
statements for free. Don't re-implement them. The risks below are what's left,
and they're more dangerous precisely because the framework's reputation
invites you to stop thinking.

**1. Mass assignment is the live wound here.**

A student POSTs `status=approved` alongside their attendance form. If
`status` is in `$fillable`, Eloquent writes it, and the entire trust model is
gone — silently, with no error.

```php
// AttendanceLog
protected $fillable = ['work_date', 'check_in', 'check_out', 'tasks'];
// NOT: status, minutes, reviewed_by, reviewed_at, reject_reason, placement_id
```

`status` and `reviewed_by` are only ever set inside the approve/reject action,
never from request data. Same rule on `User`: `role` is not fillable.

**2. Authorise the row, not the page.**

The bug this system will actually have is a field supervisor approving hours
for a student who isn't theirs. Middleware checking `role === field_supervisor`
does not stop that.

```php
// AttendanceLogPolicy
public function review(User $user, AttendanceLog $log): bool
{
    return $user->role === UserRole::FieldSupervisor
        && $log->placement->organization_id === $user->organization_id;
}
```

Call `$this->authorize('review', $log)` in the controller. Scope every index
query to the actor (`whereHas('placement', fn($q) => $q->where(...))`) —
never fetch broadly and filter in Blade.

**3. Validate in Form Requests**, not controllers. `work_date` must fall
inside the placement's period and not be in the future. `check_out` must be
after `check_in`.

**4. Eager-load the supervisor queue.** It renders student name + org per row;
without `with(['placement.student'])` it's an N+1 that gets slower every week
of term.

---

## MVP scope

Ship exactly this:

1. Auth + the four roles + middleware + policies
2. Coordinator: CRUD organisations, periods, placements; create users
3. Student: log a day, edit/delete own pending entries, progress page
   (approved hours / required, remaining, recent entries + status)
4. Field supervisor: pending queue for their org, approve/reject with reason,
   bulk-approve a week
5. Academic supervisor: assigned students, hour totals, full logs, evaluation
6. Reports: per-student hour report, printable, CSV export for coordinator
7. Arabic RTL interface

## Explicitly NOT in the MVP

Do not build these. If one seems necessary, stop and ask first.

- Mobile app · API · Sanctum
- GPS / geofenced check-in
- Notifications, mail, queues
- File uploads, document signing
- Livewire / Inertia / SPA anything
- Multi-university tenancy
- A charts library — a CSS progress bar is enough
- Repository pattern, service layer, DTOs. Controllers call Eloquent. This app
  is too small to earn abstraction.

---

## Interface

- **Arabic-first, RTL.** `<html lang="ar" dir="rtl">`. Tailwind logical
  properties only — `ms-4` / `me-4`, never `ml-4` / `mr-4`.
- Plain, dense, fast. Internal tool on cheap phones and lab desktops. No hero
  sections, no animation.
- The student's home page answers one question above the fold: **how many
  hours do I have left?**
- The field supervisor's home page is the pending queue. Nothing else.

---

## Testing

Don't chase coverage. Write feature tests for exactly these, because they're
the ones that cost real money if wrong:

- A student cannot set `status` via mass assignment
- A field supervisor cannot approve a log from another organisation (403)
- Pending minutes never appear in a progress total
- Duplicate `work_date` on one placement is rejected
- An approved log is not editable by the student

---

## Build order

Do **one step at a time**. Stop after each and wait for review.

1. `laravel new`, starter kit, DB config, enums, migrations, models +
   relationships, factories, and a seeder (one open period, 2 orgs, a
   coordinator, 2 field supervisors, an academic supervisor, 3 students,
   ~20 attendance logs in mixed states)
2. Roles: middleware + policies + route groups
3. Coordinator CRUD: organisations, periods, placements
4. Student: log form + progress page
5. Field supervisor: approval queue
6. Academic supervisor: student view + evaluation form
7. Reports + CSV export
8. RTL polish + print stylesheet

Start with step 1. Show me the migrations and enums before writing any
controllers.
