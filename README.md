# Anketo

A JotForm-style drag-and-drop form builder built with CodeIgniter 4, MySQL, and Bootstrap 5.

## Features

- Drag-and-drop form builder (Bootstrap 5 + SortableJS) — 9 field types: text, email, number, textarea, checkbox, radio, select, date, file upload.
- Public, shareable form links — anyone can fill in a published form without registering or logging in.
- Admin / user roles via [CodeIgniter Shield](https://github.com/codeigniter4/shield): users manage their own forms, admins can see and manage everything and promote/demote other users.
- Submission list, per-submission detail view, uploaded file downloads, and CSV export.
- Optional email notification to the form owner (or a custom address) whenever a form receives a submission — configured per form under **Form settings**. Requires SMTP (see [Email notifications](#email-notifications-smtp) below).

Not in this version (planned for later): conditional field logic, custom form themes, a full REST API.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (or MariaDB)

## Setup

```bash
composer install
cp env .env
```

Edit `.env`:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = anketo
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi
```

Create the database, then run migrations (this also creates Shield's own auth tables — no separate step needed):

```bash
php spark migrate --all
```

Generate an encryption key if `.env` doesn't already have one:

```bash
php spark key:generate
```

Start the dev server:

```bash
php spark serve
```

### Create the first admin

Registration (`/register`) always creates a plain `user`. To promote yourself to `admin`, register a normal account first, then run:

```bash
php spark shield:user addgroup -e your@email.com -g admin
```

## Email notifications (SMTP)

Each form has a **Notify me when someone submits this form** toggle under **Form settings** (with an optional override recipient — leave it blank to use the form owner's account email). When enabled, a submission triggers an HTML email summarising the answers with a link to the submission detail page.

Sending is best-effort and fully isolated from the public submission flow: if SMTP is down or misconfigured, the submitter still sees the thank-you page, the response is still saved, and the failure is logged to `writable/logs/`.

To enable delivery, configure SMTP in `.env` (the `env` template lists these under `EMAIL`):

```ini
email.protocol = smtp
email.SMTPHost = mail.yourdomain.com
email.SMTPUser = notifications@yourdomain.com
email.SMTPPass = 'your-mailbox-password'
email.SMTPPort = 465          # 465 -> SMTPCrypto ssl; 587 -> tls
email.SMTPCrypto = ssl
email.fromEmail = notifications@yourdomain.com
email.fromName = 'Anketo'
```

Notes:
- `fromEmail` should be an address on your sending domain so SPF/DKIM pass and mail isn't flagged as spam.
- On **cPanel shared hosting**, create a mailbox under **Email Accounts**, then use `SMTPHost = mail.yourdomain.com` with port `465`/`ssl` (or `587`/`tls`).
- Uploaded files are **not attached** — the email shows the filename and links to the submission, where the owner downloads through the access-controlled route.
- Notification links use `app.baseURL`, so make sure it's set correctly in `.env`.
- Leaving `email.protocol = mail` (the default) uses PHP `mail()`, which works on some hosts but is less reliable than SMTP.

## Project structure notes

- `system/` is **not** committed to this repo — the framework and Shield are installed via Composer into `vendor/`, same as any standard CodeIgniter 4 project. Run `composer install` before anything else will work.
- `writable/{cache,logs,session,uploads,debugbar}` are tracked via `.gitkeep` placeholders so they exist after a fresh `git clone` — CodeIgniter needs to write session and log files into these on every request, even before you've touched the app, and their absence causes a 500 on every route (see Troubleshooting below).
- Domain tables (`forms`, `form_fields`, `form_submissions`, `submission_data`) are separate from Shield's own auth tables (`users`, `auth_identities`, `auth_groups_users`, etc.) — see `app/Database/Migrations/`.
- Uploaded files are stored under `writable/uploads/forms/{form_id}/` (outside the public webroot) and served through an ownership-checked download route, not linked directly.

## Deployment (cPanel-style shared hosting, SSH + FTP)

**Core rule for every option below:** only `public/` may be web-accessible. `app/`, `vendor/`, `writable/`, and `.env` must sit **outside** `public_html` (or whatever your domain's document root is) so they can never be requested directly by a browser.

Note: the exact home-directory layout differs by host — classic cPanel uses `~/public_html` directly under your home directory, while some hosts (e.g. Hostinger hPanel) nest it per-domain as `~/domains/yourdomain.com/public_html`. The examples below use `~/anketo` for brevity; substitute your host's actual path (e.g. `~/domains/yourdomain.com/anketo`) throughout.

### 1. Get the code onto the server

Pick one:

- **SSH + git (recommended):**
  ```bash
  ssh youruser@yourserver.com
  cd ~                    # or e.g. ~/domains/yourdomain.com on hosts that nest per-domain
  git clone <your-repo-url> anketo
  ```
- **FTP:** zip the project on your machine (exclude `vendor/`, `writable/cache/*`, `writable/logs/*`, `writable/session/*`, `writable/debugbar/*`, `.env` — `.gitignore` already lists these), upload the zip to a folder **outside** `public_html` via FTP, then in File Manager (or `unzip` over SSH) extract it there.

Either way, end up with the full project as a **sibling** of `public_html`, not inside it — e.g. `~/anketo` next to `~/public_html`.

### 2. Install dependencies (SSH)

```bash
cd ~/anketo
composer install --no-dev --optimize-autoloader
```

This populates `~/anketo/vendor/` with the framework and Shield. `system/` is not part of this repo — everything comes from Composer.

### 3. Point your domain at `public/`

**Whatever path the project lives at, it must not sit inside `public_html` (or any other web-accessible folder) itself — only `public/` may be reachable by the web server.** `app/.htaccess` and `writable/.htaccess` block direct access to those two folders specifically, but `.env`, `.git/`, `vendor/`, `composer.json/.lock` have no such protection, so if the whole project sits under `public_html`, things like `https://yourdomain.com/anketo/.env` (DB password + encryption key) can be directly downloadable.

- **Preferred — custom document root, e.g. via a subdomain (works on Hostinger hPanel and most cPanel-style hosts):**
  1. Put the whole project *outside* `public_html`, e.g. `~/domains/yourdomain.com/anketo` (a sibling of `public_html`, not inside it).
  2. Create a subdomain (e.g. `anketo.yourdomain.com`) and set its **Directory** to `.../anketo/public` — the `public/` subfolder specifically, not the project root.
  3. No code changes needed: `public/index.php` already expects `app/` exactly one directory above it, which is preserved since you moved the whole project as one unit.
  4. Set `app.baseURL` in `.env` to the subdomain, e.g. `https://anketo.yourdomain.com/`.
- **If you must serve from `public_html` itself, or the subdomain's document root is a fixed subfolder you can't repoint** (some hPanel/cPanel setups create the subdomain directory as e.g. `public_html/anketo` and don't let you edit it to point elsewhere): copy the *contents* of `~/anketo/public/` (including the hidden `.htaccess`) into that web-accessible folder, then edit the copied `index.php` there and change:
  ```php
  require FCPATH . '../app/Config/Paths.php';
  ```
  to point at wherever the real project root ended up, relative to the copied `index.php`'s own folder (`FCPATH`). Two examples:
  - Serving from `public_html/` itself, with `anketo/` a sibling of `public_html/`:
    ```php
    require FCPATH . '../anketo/app/Config/Paths.php';
    ```
  - Subdomain forced to `public_html/anketo/` as its docroot, with the real project cloned as `anketo-app` *outside* `public_html` (e.g. `~/domains/yourdomain.com/anketo-app`, a sibling of `public_html/`) — note the extra `../` since the copied `index.php` now sits one folder deeper:
    ```php
    require FCPATH . '../../anketo-app/app/Config/Paths.php';
    ```
  Either way, use a different name for the real project root than the web-accessible copy (e.g. `anketo-app` vs. `public_html/anketo`) so the two don't get confused. You'll need to redo the copy (but not the edit) every time `public/assets` or `public/index.php` changes upstream — the subdomain/custom-docroot option avoids this entirely, so use it if you can.

**Sanity check after either option:** `curl -I https://yourdomain.com/anketo/.env` (or wherever the project root ended up under a web-accessible path) should return `404`, not `200`.

### 4. Create the database

In your host's control panel (cPanel: **MySQL Database Wizard**; Hostinger hPanel: **Databases → MySQL Databases**), create a database, create a user, and add the user to the database with **All Privileges**. Note the full db/user names your host gives you — they're usually prefixed with your account username (e.g. `cpuser_anketo` or `u843504816_anketo`).

### 5. Configure `.env` (SSH or File Manager)

```bash
cd ~/anketo
cp env .env
php spark key:generate
```

Edit `.env`:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://yourdomain.com/'   # or 'https://anketo.yourdomain.com/' if you used the subdomain approach in step 3
app.forceGlobalSecureRequests = true

database.default.hostname = localhost
database.default.database = cpuser_anketo
database.default.username = cpuser_anketo
database.default.password = your_secure_password
database.default.DBDriver = MySQLi
```

`CI_ENVIRONMENT = production` is what turns off the debug toolbar and detailed error pages — don't skip it.

### 6. Run migrations and create the first admin

```bash
php spark migrate --all
php spark shield:user addgroup -e your@email.com -g admin
```

(Register the account at `https://yourdomain.com/register` first if it doesn't exist yet, *then* run the command above to promote it.)

### 7. File permissions

```bash
chmod -R 755 ~/anketo
chmod -R 775 ~/anketo/writable
chmod 640 ~/anketo/.env
```

### 8. Verify

Visit `https://yourdomain.com/register`, create an account, promote it to admin (step 6), build a form, publish it, and open the public link in a private/incognito window to confirm anonymous submission works. Check `~/anketo/writable/logs/` if anything 500s.

### Updating later

```bash
cd ~/anketo
git pull                              # or re-upload changed files via FTP
composer install --no-dev --optimize-autoloader
php spark migrate --all
```

If you used the fallback document-root option in step 3, re-copy `public/`'s contents into `public_html/` after pulling changes that touch `public/assets` or `public/index.php`.

### Troubleshooting

- **Every route returns 500 right after a fresh deploy, even `/`:** check `~/anketo/writable/logs/*.log` for the real error (production mode hides it from the browser). The most common cause is `writable/cache`, `writable/logs`, `writable/session`, `writable/uploads`, or `writable/debugbar` not existing — CodeIgniter needs to write into these on every request. This repo now tracks them via `.gitkeep`, but if you're deploying from an older checkout or a zip/FTP upload that dropped empty folders, recreate them manually:
  ```bash
  mkdir -p ~/anketo/writable/{cache,logs,session,uploads,debugbar}
  chmod -R 775 ~/anketo/writable
  ```
- **`DatabaseException: Table '....settings' doesn't exist` (or any other missing-table error) in `writable/logs/`:** migrations never actually ran — most often because an earlier `php spark migrate --all` silently died on the missing-`writable/`-folder issue above before the DB error had a chance to surface. Fix the underlying cause, then re-run:
  ```bash
  php spark migrate --all
  ```
- **`php spark shield:user addgroup -e you@example.com -g admin` says "User doesn't exist":** the promote command doesn't create accounts, it only upgrades an existing one. Register at `https://yourdomain.com/register` first, then re-run the command.
- **Sanity-checking `.env` isn't exposed:** `curl -I https://yourdomain.com/.env` should be `404`. A `500` there (and on every other route) points at the missing-`writable/`-folder issue above, not at `.env` actually being served — check the logs before assuming a security problem.
