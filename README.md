# Mohammed Hamdy — Portfolio (Laravel)

Personal portfolio with a hero where **your face turns to follow the mouse**, plus a
dashboard to manage everything on the page.

## Setup (Laragon)

1. Open **Laragon → Terminal** (so `php` and `composer` are on PATH).
2. `cd C:\laragon\www\portfolio`
3. Run `install.bat`
4. Open **http://portfolio.test** (reload Laragon if the host is new) or run `php artisan serve`.

`install.bat` pulls the Laravel skeleton with Composer and merges it in **without overwriting**
the portfolio files, then creates `.env`, the SQLite database, and seeds your CV data.

**Dashboard:** `/admin` — `mohammad-hamdy@hotmail.com` / `password`
→ change it immediately in **Dashboard → Account**.
You can also set `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` before running the seeder.

### Using MySQL instead of SQLite
Create a `portfolio` database in Laragon, then in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portfolio
DB_USERNAME=root
DB_PASSWORD=
```
and run `php artisan migrate --seed`.

## What's inside

| Area | Files |
|---|---|
| Public page | `resources/views/portfolio.blade.php`, `public/css/site.css`, `public/js/site.js` |
| Face-follow hero | `public/js/site.js` (pointer → eased 3D head rotation), `public/images/hero-head.webp`, `hero-body.webp` |
| Dashboard | `app/Http/Controllers/Admin/*`, `resources/views/admin/*`, `public/css/admin.css` |
| Data | `database/migrations/2026_09_16_000100_create_portfolio_tables.php`, `database/seeders/PortfolioSeeder.php` |
| Tests | `tests/Feature/PortfolioTest.php`, `tests/Feature/AdminTest.php` → `php artisan test` |

No Node/Vite build step — CSS and JS are plain files in `public/`.

### How the face-follow works
Your photo was cut out and split into two transparent layers of the same size:
the **head** and the **body** (face area filled in behind the head). The head layer pivots around
the neck with `rotateY/rotateX` toward the cursor, the body follows slightly, and the halo /
floating cards move in parallax. Motion is eased per frame, pauses when the hero is off-screen,
glances around on its own when idle (so it works on phones too), and is disabled for
`prefers-reduced-motion`.

If you upload a new photo, either upload a new head/body pair in **Profile & Photo**
(any background remover works — cut the head at the neck, keep both images the same size),
or tick *Remove layers* and the hero will tilt the plain photo instead.

### Dashboard sections
Profile & photo & CV · Hero highlights (the stat strip) · Experience · Projects · Skills
(level ≥ 90 is highlighted as a core skill) · Certifications · Education · Languages ·
Contact-form messages (unread badge, honeypot + rate-limit spam protection) · Account/password.

Adding a new section = migration + model + a ~30-line controller extending `CrudController`
(declare fields, rules, columns) + one `Route::resource` line. Views are shared.

Uploaded files go to `public/uploads/` (no `storage:link` needed on Windows).
