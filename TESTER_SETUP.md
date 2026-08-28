# Tester Setup Guide — Lync (PUP-TBIDO)

This gets the app running on your own machine after pulling the repo. Follow it in order — most "it doesn't work" issues come from skipping a step.

## 1. Prerequisites

Make sure you have these installed before starting:

- PHP 8.3 or higher
- Composer
- Node.js + npm
- MySQL, running locally

## 2. Install dependencies

From the project root:

```bash
composer install
npm install
```

## 3. Set up your `.env` file

`.env` is never in git — everyone needs their own local copy.

```bash
cp .env.example .env
php artisan key:generate
```

Now open `.env` and edit two blocks:

**Database** — uncomment and fill in these lines with your local MySQL credentials:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lync
DB_USERNAME=root
DB_PASSWORD=your_local_mysql_password
```

(Create the `lync` database in MySQL first if it doesn't exist yet.)

**Mail (Mailtrap)** — the `MAIL_*` block is already templated in `.env.example`, but `MAIL_USERNAME` and `MAIL_PASSWORD` are left blank on purpose. Fill them in with the team's shared Mailtrap sandbox credentials:

```
MAIL_USERNAME=aaf719a9ae3712
MAIL_PASSWORD=921cb8dad40268
```

Without this, registration/verification emails will silently do nothing (no error, but nothing sent either).

## 4. Set up the database

```bash
php artisan migrate
php artisan db:seed
```

This creates all the tables and seeds ready-to-use accounts and sample data. Login accounts you get from this:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@pup.edu.ph` | `password` |
| Founder (Startup) | `founder@test.com` | `password` |

Plus sample startups, coordinators, mentors, and roadblocks across every status/tab, so most screens have data to look at immediately.

## 5. Link storage

Uploaded files (signatures, photos, roadblock attachments, exported PDFs) live under `storage/app/public` and need this symlink to be reachable by the browser:

```bash
php artisan storage:link
```

Easy to forget, and the symptom is usually "images/downloads are broken" everywhere.

## 6. Build frontend assets

```bash
npm run build
```

(Use `npm run dev` instead if you want Vite to auto-rebuild while you're actively working on the frontend.)

## 7. Run the app

```bash
php artisan serve
```

Then open the URL it prints (usually `http://127.0.0.1:8000`).

If you'd rather run the dev server, Vite, and the log viewer together in one terminal, there's a shortcut:

```bash
composer run dev
```

## 8. Testing email verification

Once your `.env` has the shared Mailtrap credentials (step 3), register a new Founder account through the app as normal. The verification email won't hit a real inbox — instead, ask Argee for access to the shared Mailtrap sandbox, and check it there. Everyone using the same credentials sees the same inbox.

## Common gotchas

- Forgot to `cp .env.example .env` at all → app won't boot, or uses stale settings.
- Copied `.env.example` but never edited the DB/mail values → migrations fail, or emails silently don't send.
- Skipped `php artisan storage:link` → uploaded/exported files 404 or show as broken images.
- Skipped `php artisan key:generate` → "no application encryption key has been specified" error.
- MySQL not actually running, or `lync` database doesn't exist yet → migration errors on step 4.
