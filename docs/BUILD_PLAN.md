# Gym System — Build Plan (single-gym MVP)

Companion to `docs/SOW_Gym_Management_System.docx`. The SOW describes the full
product vision (multi-tenant SaaS). **This codebase deliberately starts as a
single-gym application** — one gym, optionally multiple branches — because it is
faster to ship and validate. Section 9 explains how to graduate to multi-tenant
SaaS later without throwing this away.

Stack: **Laravel 13 + Filament 5** (admin/back-office), **SQLite** in dev /
**MySQL or MariaDB** in production, **Sanctum** for the future member API,
queue + scheduler for reminders. Built for a solo developer.

---

## 1. Local setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed        # demo gym + 30 members + plans + reminder rules
php artisan make:filament-user          # or log in with the seeded account below
php artisan serve                       # http://localhost:8000/admin
php artisan queue:work                  # process reminder / SMS jobs
```

Seeded logins (password `password`): `owner@demo.test`, `reception@demo.test`.

`php artisan test` runs the suite. The reminder engine can be exercised with
`php artisan gym:send-reminders` then `php artisan queue:work --stop-when-empty`.

### Switching dev to MySQL/MariaDB (WAMP)

Set in `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=gym`, `DB_USERNAME=root`,
`DB_PASSWORD=`. Create the schema, then `php artisan migrate:fresh --seed`.
Everything is written to be portable (no SQLite-only SQL).

---

## 2. Architecture & conventions

| Concern | Decision |
|---|---|
| **Money** | Always integer **cents** (`*_cents` columns, `int` casts). Never floats. Format with `App\Domain\Support\Money`. |
| **Business logic** | Lives in `app/Domain/*` services, not in controllers/Filament. Filament pages and (later) API controllers call the same services. |
| **Numbering** | `App\Domain\Support\DocumentNumber` — member/invoice/receipt numbers. MVP derives sequences from row counts; see §8 before adding a second concurrent front desk. |
| **Member status** | Denormalised onto `members.status` + `members.current_expiry_on` by `MembershipStatusService::sync()`. Recomputed nightly (`gym:sync-statuses`) and after every sale/renew/freeze. |
| **Access control** | `MembershipStatusService::accessDecision()` returns an `AccessDecision` (`granted` / `level` = ok\|warn\|block / `message`). Policy per state in `config/gym.php` (`hard_block` vs `allow_warn`). |
| **Billing** | `BillingService`: `sellMembership()`, `createInvoice()`, `recordPayment()` (+ FIFO `allocatePayment()`), `memberDuesCents()`. All wrapped in DB transactions. |
| **Reminders** | `ReminderRule` rows → `ReminderRunner` (dedupe via unique `notification_logs.dedupe_key`) → `SendReminderMessage` job → `SmsGateway`. |
| **SMS** | `App\Support\Sms\SmsGateway` interface. `log` driver (default, writes to log) and `notifylk` driver. Swap via `SMS_DRIVER`. |
| **Config** | Gym-wide settings in `config/gym.php` (env-driven). Move to a `settings` table when the owner needs to edit them in-app. |
| **Tax** | `config('gym.tax')` — inclusive/exclusive, rate. `Money::splitInclusiveTax()` / `addTax()`. Per-plan `tax_rate` overrides. |

### Where things live

```
app/
  Domain/
    Billing/            BillingService, ...
    Members/            MembershipStatusService, AccessDecision
    Notifications/      ReminderRunner, TemplateRenderer, DefaultTemplates
    Support/            Money, DocumentNumber
  Support/Sms/          SmsGateway + LogSmsGateway + NotifyLkSmsGateway
  Jobs/                 SendReminderMessage
  Console/Commands/     SendReminders, SyncMemberStatuses
  Filament/Resources/   MemberResource, PlanResource  (admin UI)
  Filament/Widgets/     GymOverview (dashboard stats)
  Models/               18 domain models
database/
  migrations/           2026_09_06_0800xx_*  (domain schema)
  seeders/              DemoSeeder, RolesSeeder
```

---

## 3. What is already scaffolded

- **Database schema** for the whole MVP domain: branches, members, plans,
  memberships (+ freezes), invoices (+ items), payments (+ allocations),
  cash sessions, attendances, message templates, reminder rules,
  notification logs, sms messages, payment gateway events, leads.
- **Models** with relationships and casts.
- **Domain services**: billing, membership status + access decision,
  reminder runner, template rendering, SMS gateway abstraction, money helpers,
  document numbering.
- **Reminder pipeline** end-to-end (command → dedupe → queue → job → gateway →
  logged), idempotent, quiet-hours aware.
- **Nightly schedule** wired in `routes/console.php`.
- **Filament admin** with a Members list (status badges, filters) and Plans
  editor (rupee inputs), plus a dashboard stats widget.
- **Seeder** that generates a realistic demo gym by calling the real services.
- **Feature tests** for billing allocation, tax split, renewals, and the
  access-decision matrix.

This is roughly "Phase 1 foundation". The list below is what turns it into a
sellable MVP.

---

## 4. Module build order (maps to SOW §4)

Each item = a Filament resource / page + any service methods + tests.
Recommended order for a solo build; ship after Phase 1.

### Phase 1 — core operations (MVP)

1. **Members** — *scaffolded.* Add: photo upload, document uploads,
   duplicate-phone/NIC warning, a "Membership" relation-manager showing the
   member's terms, an activity timeline, quick actions (Renew, Record payment,
   Freeze).
2. **Plans** — *scaffolded.* Add discount codes later.
3. **Sell / renew flow** — a Filament page or action wrapping
   `BillingService::sellMembership()` + immediate `recordPayment()`.
   Print/download the receipt (see §8, PDFs).
4. **Payments & dues** — `PaymentResource` (list, filters by method/date),
   a "Record payment" action from a member, an **Invoices** resource with a
   dues/ageing view. Refunds & credit notes.
5. **Cash session / day close** — open float, list payments by method,
   expected vs counted, variance, print summary. `CashSession` model exists.
6. **Attendance / check-in** — a dedicated fast page: search by phone/QR,
   call `accessDecision()`, show green/amber/red, write `Attendance`.
   Add a "who's in now" list.
7. **Reminders admin** — `ReminderRuleResource`, `MessageTemplateResource`,
   a "send test" action, a delivery log view (from `notification_logs` /
   `sms_messages`). Bulk/broadcast SMS to a filtered member set.
8. **Owner dashboard** — extend `GymOverview`; add charts (revenue by day,
   new vs expired) with Filament chart widgets; a "renewals due this week" table.
9. **Settings** — move `config/gym.php` values into an editable settings page
   (gym profile, tax, reminder timing, receipt header).
10. **Staff & roles** — `UserResource`, assign Spatie roles, gate Filament
    resources/actions by permission (`filament-shield` optional).
11. **Data import** — a CSV importer for existing members + opening dues
    (Filament has `Importer` classes). Produce a reconciliation summary.

### Phase 2 — grow

12. **Leads / CRM** — `LeadResource` with a pipeline (kanban), follow-up
    task list, "convert to member" action, funnel report. `Lead` model exists.
13. **Personal training** — trainer assignment, PT session decrement on
    check-in, commission report. (`memberships.sessions_*` already there.)
14. **Classes & scheduling** — new tables (`class_types`, `class_sessions`,
    `class_bookings`); timetable + capacity + waitlist.
15. **POS & inventory** — `products`, `stock_movements`; sell at the desk,
    charge to member or walk-in; low-stock alerts.
16. **Reports** — revenue (by plan/method/branch), membership growth/retention,
    dues ageing, attendance/footfall, SMS cost. Export to Excel/PDF.
17. **Member mobile app** — Sanctum API (`routes/api.php`) + Flutter client:
    status, renew/pay online, digital QR card, attendance history, class booking.
18. **WhatsApp channel** — implement a `WhatsAppGateway` alongside `SmsGateway`;
    Meta Cloud API or a BSP; template approval.

---

## 5. Online payments (PayHere)

Wiring points already stubbed in `config/gym.php` (`gym.payments.payhere`).

To implement:

1. `App\Domain\Payments\PayHereGateway`:
   - `checkoutParams(Invoice $invoice, array $urls): array` — build the
     hashed form fields (`merchant_id`, `order_id`, `amount`, `currency`,
     `hash` = md5(merchant_id + order_id + amount + currency + md5(secret))).
   - `verifyNotification(array $payload): bool` — recompute `md5sig` and compare.
2. `PaymentNotifyController` (route `POST /payments/payhere/notify`, **no CSRF**,
   no auth):
   - Insert a `payment_gateway_events` row keyed by
     `('payhere', order_id.'-'.status_code)`. The unique index makes the
     handler **idempotent** — if the row exists and is processed, return 200.
   - On `status_code == 2` (success): `BillingService::recordPayment()` with
     `method: 'online'`, `gateway: 'payhere'`, `gateway_ref: payment_id`,
     then mark the event processed inside the same transaction.
3. Member-facing "pay" page builds the checkout form from `checkoutParams()`.
4. Sandbox first (`PAYHERE_SANDBOX=true`); go live after merchant KYC.

Never trust the browser redirect for fulfilment — only the server notify.

---

## 6. Offline check-in (Phase 2, when a gym asks)

Keep it small: a self-contained check-in **PWA page** (not full Filament) that
caches the member list (id, name, phone, member_no, status, expiry) in
IndexedDB, evaluates the access rule client-side, and queues check-ins with a
`client_uuid`. On reconnect it POSTs the queue to a
`POST /api/checkins/sync` endpoint; the `attendances.client_uuid` unique
index dedupes. The nightly `gym:sync-statuses` keeps the cached statuses honest.

---

## 7. Solo-developer schedule (indicative)

| Weeks | Work |
|---|---|
| 1 | Setup, finalise schema, this foundation (**done**), MySQL parity, deploy pipeline |
| 2–3 | Members polish, sell/renew flow, receipts (PDF) |
| 3–4 | Payments, invoices, dues/ageing, refunds |
| 4–5 | Cash session/day close, attendance check-in page |
| 5–6 | Reminders admin, templates, delivery log, broadcast SMS |
| 6–7 | Dashboard + core reports, settings page, staff/roles |
| 7–8 | Data import, pilot deploy to 1 gym, fix, harden, backups |
| ongoing | Phase 2 modules driven by pilot feedback |

~8 weeks to a gym-usable MVP at a steady solo pace.

---

## 8. Known simplifications to revisit

- **Document numbering** counts rows to derive the next sequence — safe for one
  front desk. For concurrent desks add a `number_sequences` table and increment
  inside `DB::transaction` + `lockForUpdate()`.
- **Dues on expired members**: `memberDuesCents()` counts every non-void
  invoice (correct), but `status = 'due'` is only set while an *active*
  membership exists. Dues reports must query invoices directly, not
  `members.status`.
- **`config/gym.php`** is env-driven and not editable in-app yet (Module 9).
- **No audit log** yet — add `spatie/laravel-activitylog` on financial +
  membership-changing actions before the pilot.
- **PDPA**: add consent capture on the member form, a data-export action, and a
  delete/erase workflow before go-live.

---

## 9. Growing into multi-tenant SaaS later

The SOW's SaaS model = many gyms on one deployment. To get there from here:

1. Add a `tenants` table and a nullable `tenant_id` to every domain table
   (members, plans, memberships, invoices, payments, ...).
2. Reintroduce the tenant scope: a `BelongsToTenant` trait adding a global
   `where tenant_id = <current>` scope + auto-fill on create, resolved from the
   authenticated user (or subdomain) in middleware. (This was scaffolded and
   removed in commit history — recover it from there.)
3. Give Filament a second **platform** panel for tenant provisioning +
   subscription billing to gyms.
4. Move `config/gym.php` values to per-tenant `settings`.
5. Backups, per-tenant SMS credit, usage metering.

Because all business logic is already funnelled through `app/Domain/*` services
and models, adding the scope is mostly schema + a trait + middleware — the
services barely change.

---

## 10. Deployment (single gym)

- **Host**: one small VPS (Hetzner/DO/Linode, or a local provider) via
  **Laravel Forge**, or a Docker container. Cloudflare in front.
- **DB**: managed MySQL/MariaDB or on-box with daily `mysqldump` to object
  storage (30-day retention).
- **Queue**: `php artisan queue:work` under supervisor/Forge daemon (Redis or
  `database` queue).
- **Scheduler**: one cron entry — `* * * * * php artisan schedule:run`.
- **Assets**: `npm ci && npm run build` + `php artisan filament:assets` in the
  deploy script; `php artisan optimize`.
- **TLS**: Let's Encrypt via Forge.
- **Monitoring**: Forge/Envoyer health checks + an uptime monitor; log to file
  or a cheap log service.
