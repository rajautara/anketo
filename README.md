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

## Deployment

Standard CodeIgniter 4 deployment: only `public/` should be web-accessible; `app/`, `vendor/`, and `.env` should sit outside the public webroot. Run `composer install --no-dev` and `php spark migrate --all` as part of your deploy step (locally or in CI), then upload the resulting `vendor/` folder along with the rest of the project — no Composer or SSH access is required on the server itself.
