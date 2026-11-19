# CampusFind

CampusFind is a mobile-first university lost & found app. Students can report lost or found items, browse listings, and file ownership claims for found items. Lost & Found staff can verify claims from the same app using a staff account.

- **Frontend:** React 19 + Vite + TypeScript + Tailwind CSS v4
- **Backend:** Laravel 12 + Sanctum token authentication + MySQL

## Project structure

```
CampusFind/
├── backend/    Laravel API (routes in backend/routes/api.php)
└── frontend/   React single-page app (routes in frontend/src/main.tsx)
```

## Prerequisites

- PHP 8.2+ with Composer
- MySQL (e.g. XAMPP) running locally
- Node.js 20+ with npm

## Setup

### 1. Backend

```powershell
cd backend
composer install
copy .env.example .env        # if .env does not exist
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

The API runs at `http://localhost:8000`. MySQL runs locally (XAMPP) on `127.0.0.1:3306` with database `campusfind`, user `campusfind`, password `secret`. Create that database/user once before running the migrations.

### 2. Frontend

```powershell
cd frontend
npm install
npm run dev
```

The app runs at `http://localhost:5175`. It reads the API base URL from `frontend/.env`:

```
VITE_API_URL=http://localhost:8000/api
```

Start the API before the frontend so login and data requests succeed.

## Deployment

The frontend goes to Vercel and the Laravel API goes to Laravel Cloud. This is a monorepo, so each platform needs to know which directory to build.

### 1. Laravel Cloud (backend + database)

1. Go to https://cloud.laravel.com and choose **New application**.
2. Connect GitHub and select `KatrineMatedios/CampusFind`.
3. When Cloud detects the monorepo, select the **`backend`** directory as the root.
4. On the environment canvas click **Add database** → **Laravel MySQL** (same region). Cloud injects `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` automatically — do **not** copy the local XAMPP values into Cloud.
5. Click **Add bucket** → **Laravel Object Storage**, set it as the **default** disk and make it **public** (item photos, avatars and claim proof images need public URLs). Cloud injects `FILESYSTEM_DISK` and the S3 credentials.
6. In **Settings → Environment variables** set:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://<your-cloud-domain>`
   - `FRONTEND_URL=https://<your-vercel-app>.vercel.app`
   - `CACHE_STORE=database`
   - `SESSION_DRIVER=database`
7. In **Settings → Deploy commands** use `php artisan migrate --force`. Do not run `storage:link` or `optimize:clear` on Cloud — the filesystem resets on every deploy.
8. Deploy, then test `https://<your-cloud-domain>/api/health` — it should return `{"status":"ok",...}`.

Take note of the Cloud domain (e.g. `https://campusfind-xxxx.us.laravel.app`); you need it for Vercel.

### 2. Vercel (frontend)

1. Go to https://vercel.com → **Add New → Project** → import `KatrineMatedios/CampusFind`.
2. Set **Root Directory** to `frontend`, framework **Vite**, build `npm run build`, output `dist`.
3. In **Settings → Environment Variables** add `VITE_API_URL` = `https://<your-cloud-domain>/api`.
4. Deploy (or redeploy after adding the variable).

The backend's `config/cors.php` reads `FRONTEND_URL` and also allows `*.vercel.app`, so preview deployments work too.

## Demo accounts

All seeded accounts use the password `password`.

| Role | Student ID | Email |
| --- | --- | --- |
| Student (Maria Santos) | `2024-00123` | `maria.santos@university.edu` |
| Student (John Cruz) | `2024-00456` | `john.cruz@university.edu` |
| Student (Alyssa Reyes) | `2023-00789` | `alyssa.reyes@university.edu` |
| Lost & Found staff | `STAFF-0001` | `lostandfound@university.edu` |

You can log in with either the student ID or the email address. Run `php artisan migrate:fresh --seed` at any time to restore the demo data (4 users, 11 items, seeded claims and notifications).

## Features

- Registration, login, logout, and password reset
- Mobile-first layout with bottom-tab navigation and a dedicated desktop sidebar layout
- Home dashboard with recent lost items and quick actions
- Report lost or found items with photo uploads, category, date, and location
- Browse all items with search and category filters
- Dedicated lost and found item listings
- Item details with ownership claim flow for found items
- Claim confirmation with a QR code and claim reference (`CF-XXXX-XXXX-XXXX`)
- My Reports with open/claim-pending/resolved states
- Resolved items history
- Profile editing, avatar upload, personal information, and activity history
- In-app notifications with mark-as-read support
- Help & Support page
- Staff workflow: list claims and update claim status

## API overview

Public endpoints (rate limited):

```
POST /api/register
POST /api/login
POST /api/forgot-password
POST /api/reset-password
```

Authenticated endpoints (`Authorization: Bearer <token>`):

```
GET    /api/me
POST   /api/logout
GET    /api/items
GET    /api/items/recent-lost
POST   /api/items
GET    /api/items/{item}
PUT    /api/items/{item}
DELETE /api/items/{item}
GET    /api/categories
GET    /api/dashboard/stats
GET    /api/my-reports
GET    /api/my-reports/resolved
POST   /api/items/{item}/claims
GET    /api/claims/{claim}
GET    /api/claims                      (staff only)
PATCH  /api/claims/{claim}/status       (staff only)
GET    /api/profile
PUT    /api/profile
POST   /api/profile/avatar
GET    /api/profile/activity
GET    /api/notifications
PATCH  /api/notifications/read-all
PATCH  /api/notifications/{id}/read
```

## Build

```powershell
cd frontend
npm run build
```

Uploaded images use Laravel's default filesystem disk (`public` locally, an object-storage bucket on Laravel Cloud), so run `php artisan storage:link` once after local setup. Password reset emails use the `log` mailer by default; the reset link is written to `backend/storage/logs/laravel.log`.
