# Gym System

Gym management system for a single gym (optionally multi-branch), built for the
Sri Lankan market (LKR, notify.lk SMS, PayHere).

## Repository layout

```
Gym_System/
├── backend/     Laravel 13 + Filament 5 — web admin, business logic, REST API
├── mobile/      Flutter member app (planned — not yet created)
└── docs/        SOW (Statement of Work) and the build plan
```

- **backend/** is the full server-side application *and* the staff-facing admin
  UI (Filament, at `/admin`). It will also expose the JSON API the mobile app
  consumes. See [backend/CLAUDE.md](backend/CLAUDE.md) and
  [docs/BUILD_PLAN.md](docs/BUILD_PLAN.md).
- **mobile/** will hold the Flutter app for members (renew online, digital
  membership card, class booking). Create it when you reach Module 17 of the
  build plan.

## Getting started (backend)

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve          # http://localhost:8000/admin
php artisan queue:work     # process reminder / SMS jobs
```

Seeded logins (password `password`): `owner@demo.test`, `reception@demo.test`.

## Docs

| File | What |
|---|---|
| [docs/SOW_Gym_Management_System.docx](docs/SOW_Gym_Management_System.docx) | Full product statement of work (the SaaS vision) |
| [docs/BUILD_PLAN.md](docs/BUILD_PLAN.md) | Architecture, conventions, module build order, deployment |
