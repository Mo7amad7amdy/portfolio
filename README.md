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

### SEO & social sharing
- **Page head:** title, description, canonical link, Open Graph (`profile` type) and X/Twitter large-image card tags.
- **Structured data:** JSON-LD for `ProfilePage`, `Person` and `WebSite`. The `Person` entry includes your job, current employer, skills, languages, education, certifications and profile links.
- **Share card:** a 1200×630 image built with PHP GD from your name, title, tagline, first three highlights and portrait (`app/Support/SocialCard.php`). The file name changes whenever the content changes, so LinkedIn, Facebook and X load the new preview. Upload your own image in the dashboard to replace it.
- **Other files:** `/sitemap.xml`, `/site.webmanifest`, favicons in `public/images/icons`, and `robots.txt`, which blocks `/admin`.
- **Dashboard → Profile & Photo → SEO & social sharing:** custom search title and description (with live Google and share previews), an X username and a custom share image.

**After going live:**
1. Set `APP_URL` to your real domain. The share card's domain chip, canonical link and absolute image URLs all use it.
2. Add `Sitemap: https://your-domain/sitemap.xml` to `public/robots.txt`.
3. Submit the sitemap in Google Search Console.
4. Refresh the link preview in the LinkedIn Post Inspector (linkedin.com/post-inspector) and the Facebook Sharing Debugger (developers.facebook.com/tools/debug).

The share card needs PHP's GD extension with FreeType (enabled in Laragon by default). Without it, the site shares your profile photo instead.

### Running without `/public` in the URL
The project root has its own `.htaccess` and `index.php`, so these both work on Apache (Laragon, shared hosting):
- `http://localhost/portfolio` (sub-folder)
- a domain whose document root is the project folder

Only files inside `public/` are served; everything else (`.env`, `vendor/`, `storage/`, the database) goes to
Laravel or is denied. Set `APP_URL` in `.env` to the address you use (e.g. `APP_URL=http://localhost/portfolio`).
On Nginx, point the server root at `public/` instead; the `.htaccess` files are Apache-only.

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

### Living portrait (default hero)
The hero renders your photo with WebGL (`public/js/living-portrait.js`) from two files:
an enhanced transparent cut-out and a **Depth Anything V2** depth map.

- **3D head turn:** parallax driven by the depth map. The nose moves more than the cheeks and the shoulders counter-move, so it reads as real rotation rather than a flat tilt.
- **Eyes:** they blink on a natural random rhythm (sometimes a double blink) and glance toward the cursor.
- **Motion and light:** subtle breathing and head sway, plus a warm key light that follows the cursor using normals from the depth map.
- **Fallbacks:** without WebGL, or with the feature turned off, it falls back to the head/body layers, then to the tilting photo.

To regenerate from a new photo: `python tools/portrait/make_portrait.py photo.jpg` (see `tools/portrait/README.md`),
or upload the files in **Dashboard → Profile & Photo → Living portrait** and click both eyes on the preview.

### How the face-follow works (layers fallback)
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
