<project-overview>
# Gym System

Single-gym management application (Laravel 13 + Filament 5). Members, plans,
memberships, billing/dues, cash reconciliation, attendance/access control,
SMS renewal & payment reminders, leads. Built for one gym (optionally multiple
branches) — NOT multi-tenant. Target market: gyms in Sri Lanka (LKR, notify.lk
SMS, PayHere).

This Laravel app is the `backend/` folder of a monorepo. Sibling folders:
`../docs/` (SOW + build plan) and, later, `../mobile/` (Flutter member app).

**Read `../docs/BUILD_PLAN.md` first** — it explains the architecture,
conventions, what is scaffolded, and the module build order.
`../docs/SOW_Gym_Management_System.docx` is the full product statement of work.

## Conventions (do not break these)

- **Money is always integer cents** (`*_cents` columns). Never floats. Format via
  `App\Domain\Support\Money`.
- **Business logic lives in `app/Domain/*` services**, not controllers or
  Filament classes. Filament pages and the future API both call these services.
- Member `status` + `current_expiry_on` are **denormalised** — only
  `MembershipStatusService::sync()` writes them.
- Access-at-check-in decisions come from
  `MembershipStatusService::accessDecision()` → `AccessDecision`.
- Gym-wide settings: `config/gym.php` (env-driven).

## Common commands

```sh
php artisan migrate:fresh --seed     # rebuild dev DB (SQLite) with demo data
php artisan test                     # feature tests for billing + access rules
php artisan gym:send-reminders       # evaluate reminder rules -> queue SMS
php artisan gym:sync-statuses        # recompute member statuses
php artisan queue:work               # process reminder/SMS jobs
```

Seeded admin logins (password `password`): `owner@demo.test`, `reception@demo.test`.
Admin panel: `/admin`.
</project-overview>

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
