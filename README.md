# Field Training Management System — نظام التدريب الميداني

A web application for managing university **field training** placements (التدريب الميداني): students log their training hours, field supervisors at the host organisation confirm them, academic supervisors grade the placement, and the training coordinator manages everything and exports reports.

Built for the **TAQAT IT Incubator** training programme. Arabic-first, RTL, server-rendered.

**Author:** Mohammed Emad Elrefy · [github.com/Checkaru](https://github.com/Checkaru)

## The core rule

> **Students self-report their hours. Field supervisors confirm them. Only approved hours count.**

A logged day is `pending` and counts for nothing until the field supervisor at the student's host organisation approves it. Rejected entries stay visible with a reason so the student can correct and resubmit. Once approved, an entry is locked to the student — only the supervisor can revert it. Every progress number in the app is **approved** hours; pending minutes are never summed into anything.

## Roles

| Role | Arabic | Can do |
|---|---|---|
| `student` | طالب | Log own attendance, edit own pending/rejected entries, track progress |
| `field_supervisor` | المشرف الميداني | Approve/reject logs **for their organisation only**, bulk-approve, submit field evaluation |
| `academic_supervisor` | المشرف الأكاديمي | View assigned students' logs and hours, submit the academic evaluation (the grade) |
| `coordinator` | منسق التدريب | Manage organisations, periods, placements, users; view reports; export CSV |

There is no public registration — the coordinator creates all accounts.

## Stack

- **Laravel 13** (PHP 8.3+), **MySQL 8**
- Blade + **Tailwind CSS** + Vite (no Livewire, no Inertia, no API)
- Auth via **Laravel Breeze** (Blade stack)
- PHPUnit feature tests

## Requirements

- PHP **8.3+** with `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `zip`, `intl` (and `pdo_sqlite` to run the test suite)
- Composer 2
- Node.js 20+ / npm
- MySQL 8 (MariaDB 10.6+ also works)

## Setup

```bash
git clone https://github.com/Checkaru/ftms.git
cd ftms

# 1. PHP dependencies
composer install

# 2. Environment
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# then edit .env → set DB_HOST / DB_PORT / DB_USERNAME / DB_PASSWORD
# note: if the DB password contains #, wrap it in quotes: DB_PASSWORD="pa#ss"

# 3. Database (create it first: CREATE DATABASE taqat_ftms
#    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;)
php artisan migrate --seed

# 4. Front-end assets
npm install
npm run build

# 5. Run
php artisan serve
```

Open <http://127.0.0.1:8000>. For live-reload while developing views, run `npm run dev` in a second terminal.

### Demo accounts (from the seeder)

All passwords are `password`:

| Role | Email |
|---|---|
| Coordinator | `coordinator@taqat.ps` |
| Field supervisor (org A) | `field.a@taqat.ps` |
| Field supervisor (org B) | `field.b@taqat.ps` |
| Academic supervisor | `academic@taqat.ps` |
| Students | `student1@taqat.ps` · `student2@taqat.ps` · `student3@taqat.ps` |

The seeder creates one open period (180 required hours), two organisations, three placements, and ~20 attendance logs in mixed states. Reset demo data anytime with `php artisan migrate:fresh --seed`.

## Public demo (Cloudflare tunnel)

To let someone outside your machine try the app without any hosting, expose the local server at a temporary public URL:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\demo-tunnel.ps1
```

It needs [`cloudflared`](https://github.com/cloudflare/cloudflared/releases) on your PATH (single binary, no account required). The script removes any stale `public/hot` file (a leftover `npm run dev` watcher would otherwise make remote visitors' pages load assets from *your* localhost and render unstyled), starts `php artisan serve` if it isn't running, and prints an `https://*.trycloudflare.com` URL.

Notes:

- The URL is random and changes on every start; the site is only up while the script runs and your machine is awake.
- Run `npm run build` first if you changed any views or CSS — the tunnel serves built assets, not the dev watcher.
- Demo accounts all use the password `password`, so share the URL privately.

## Tests

```bash
php artisan test
```

The suite runs on in-memory SQLite (see `phpunit.xml`) — it never touches your MySQL data. The tests that matter most guard the trust model:

- a student cannot set `status`/`minutes`/`reviewed_by` via mass assignment
- a field supervisor cannot approve a log from another organisation (403)
- pending minutes never appear in a progress total
- duplicate `work_date` per placement is rejected (validation **and** DB unique index)
- an approved log is not editable by the student

## Where things live

| Concern | Location |
|---|---|
| Role/status enums (with Arabic labels) | `app/Enums/` |
| Row-level authorisation | `app/Policies/` (the critical one: `AttendanceLogPolicy::review`) |
| Section gating | `app/Http/Middleware/EnsureRole.php` (`role:` middleware alias) |
| Validation | `app/Http/Requests/` (per section) |
| Evaluation rubrics (editable without migration) | `config/training.php` |
| Approved-hours logic | `Placement::approvedMinutes()` — the only place hours are summed |
| Reports + CSV export | `app/Http/Controllers/Coordinator/ReportController.php` |
| Arabic translations | `lang/ar.json`, `lang/ar/` |
| Print stylesheet (A4 reports) | `resources/css/app.css` (`@media print`) |

## Troubleshooting

- **`Vite manifest not found`** — run `npm run build` (pages call `@vite`, tests included).
- **`Cannot find module './rolldown-binding.win32-x64-msvc.node'`** (Windows) — npm sometimes skips optional native deps; fix with `npm install @rolldown/binding-win32-x64-msvc`.
- **`Access denied for user`** with a correct password — if the password contains `#`, quote it in `.env`.
- **Arabic garbled in Excel** — open the exported CSV directly (it includes a UTF-8 BOM); don't re-encode it.
