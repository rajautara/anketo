# Anketo

A JotForm-style drag-and-drop form builder built with CodeIgniter 4, MySQL, and Bootstrap 5.

## Features

- Drag-and-drop form builder (Bootstrap 5 + SortableJS) — 9 field types: text, email, number, textarea, checkbox, radio, select, date, file upload.
- Public, shareable form links — anyone can fill in a published form without registering or logging in.
- Admin / user roles via [CodeIgniter Shield](https://github.com/codeigniter4/shield): users manage their own forms, admins can see and manage everything and promote/demote other users.
- Submission list, per-submission detail view, uploaded file downloads, and CSV export.

Not in this version (planned for later): conditional field logic, custom form themes, email notifications on submission, a full REST API.

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

## Project structure notes

- `system/` is **not** committed to this repo — the framework and Shield are installed via Composer into `vendor/`, same as any standard CodeIgniter 4 project. Run `composer install` before anything else will work.
- Domain tables (`forms`, `form_fields`, `form_submissions`, `submission_data`) are separate from Shield's own auth tables (`users`, `auth_identities`, `auth_groups_users`, etc.) — see `app/Database/Migrations/`.
- Uploaded files are stored under `writable/uploads/forms/{form_id}/` (outside the public webroot) and served through an ownership-checked download route, not linked directly.

## Deployment (cPanel shared hosting, SSH + FTP)

**Core rule for every option below:** only `public/` may be web-accessible. `app/`, `vendor/`, `writable/`, and `.env` must sit **outside** `public_html` (or whatever your domain's document root is) so they can never be requested directly by a browser.

### 1. Get the code onto the server

Pick one:

- **SSH + git (recommended):**
  ```bash
  ssh yourcpaneluser@yourserver.com
  cd ~
  git clone <your-repo-url> anketo
  ```
- **FTP:** zip the project on your machine (exclude `vendor/`, `writable/cache/*`, `writable/logs/*`, `writable/session/*`, `writable/debugbar/*`, `.env` — `.gitignore` already lists these), upload the zip to your home directory (**not** inside `public_html`) via FTP, then in cPanel File Manager (or `unzip` over SSH) extract it to `~/anketo`.

Either way, end up with the full project at `~/anketo` — a sibling of `public_html`, not inside it.

### 2. Install dependencies (SSH)

```bash
cd ~/anketo
composer install --no-dev --optimize-autoloader
```

This populates `~/anketo/vendor/` with the framework and Shield. `system/` is not part of this repo — everything comes from Composer.

### 3. Point your domain at `public/`

- **Preferred — set a custom document root:** in cPanel → **Domains**, edit the domain/subdomain you're deploying to and set its **Document Root** to `anketo/public` (relative to your home directory). Nothing else outside `public/` becomes web-accessible, and `public/index.php` works unmodified since `app/` is exactly one level above it, as it expects.
- **Fallback — if your host won't let you change the document root** (e.g. you're stuck serving from `public_html` itself): copy the *contents* of `~/anketo/public/` (including the hidden `.htaccess`) into `public_html/`, then edit `public_html/index.php` and change:
  ```php
  require FCPATH . '../app/Config/Paths.php';
  ```
  to point at the real location of `app/`, e.g. if `anketo/` sits next to `public_html/` in your home directory:
  ```php
  require FCPATH . '../anketo/app/Config/Paths.php';
  ```
  You'll need to redo this copy (but not the edit) every time `public/assets` or `public/index.php` changes upstream — the custom-document-root option avoids this entirely, so use it if you can.

### 4. Create the database

cPanel → **MySQL Database Wizard**: create a database, create a user, add the user to the database with **All Privileges**. Note the full db/user names cPanel gives you — they're usually prefixed with your cPanel username (e.g. `cpuser_anketo`).

### 5. Configure `.env` (SSH or File Manager)

```bash
cd ~/anketo
cp env .env
php spark key:generate
```

Edit `.env`:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://yourdomain.com/'
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
