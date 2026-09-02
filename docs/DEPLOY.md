# Deploying v1 to Hostinger

The franchise, payments, complaints and attendance work is committed and ready.
This is how it reaches the live site without losing anything.

---

## Setting up a fresh subdomain, start to finish

This is the sequence that was used to stand up `testv1.eswachh.in`. Upload the
code first, then:

**1. Remove Hostinger's placeholder** — it can hide the site.

```bash
cd ~/domains/eswachh.in/public_html/testv1
rm default.php
```

**2. Write the `.env`.** There is no usable template in the repo — `.env.example`
is the stock Laravel one and is missing every key this application needs.

```bash
nano .env
```

```
APP_NAME=eSwachh
APP_ENV=production
APP_KEY=
APP_DEBUG=true
APP_URL=https://testv1.eswachh.in

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u841499718_eswachh_v1
DB_USERNAME=
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@eswachh.in"
MAIL_FROM_NAME="${APP_NAME}"

RAZOR_KEY=
RAZOR_SECRET=

SMS_ENABLED=false
MSG91_WHATSAPP_NUMBER=
MSG91_AUTH_TOKEN=
```

`SMS_ENABLED=false` is not optional on a test site. It is pointed at real
customer phone numbers, and the only thing standing between a test click and a
message to a real person is that line. Razorpay **test** keys here, never live
ones.

**3. Key and permissions.**

```bash
php artisan key:generate
chmod -R 775 storage bootstrap/cache
```

**4. Build the database.**

```bash
php artisan eswachh:deploy
```

It names the database and asks before touching it. Read the name.

**5. Open the site**, then set `APP_DEBUG=false` and run
`php artisan config:cache`. With debug on, any error page shows the database
credentials to whoever is looking.

---

## What still needs setting up after the site loads

### The scheduler — one cron entry

In hPanel → Advanced → Cron Jobs:

```
* * * * * cd /home/u841499718/domains/eswachh.in/public_html/testv1 && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

One entry, not one per job. Laravel decides each minute what is due.

| When | Command | What it does |
|---|---|---|
| Daily 01:00 | `backup:clean` | Deletes backups past the retention rule. |
| Daily 09:00 | `renewal:send-notifications` | Chases renewals, expiries, holds and low cloth counts. |
| Mon 09:30 | `orders:auto-hold` | Parks subscriptions a week past renewal on hold. |

All three fall on five-minute boundaries, so a five-minute cron works if that is
the minimum the plan allows.

### The queue must stay on `sync`

```
QUEUE_CONNECTION=sync
```

**This one is not a preference.** v1 has listeners marked `ShouldQueue`,
including `UserCreatedProfileCreate` — the thing that writes a customer's
profile row when their account is made. On `sync` it runs inline and everything
works. Switch to `database` without a worker running and it goes into a table
nobody drains: accounts appear with no profile behind them, and nothing anywhere
says so. Shared hosting has no supervisor to keep `queue:work` alive, so leave
it as it is.

### Checking it works

```bash
php artisan schedule:list          # what is due
php artisan schedule:run           # run the whole schedule once, by hand
```

If nothing happens the day after adding the cron, check the path and the PHP
binary in the cron line first — both differ from what `php artisan` resolves to
in an SSH session.

### Nothing is actually backing up

`backup:clean` is scheduled. **`backup:run` is not.** The cleaner runs every
night over a folder that never receives anything, so there is no automated
database backup at all, despite the schedule looking like there is.

Adding it is one line in `app/Console/Kernel.php`:

```php
$schedule->command('backup:run --only-db')->daily()->at('00:30');
```

Left out rather than added quietly: it starts writing database dumps to the
account's disk and mails on failure, and whether to run it here or rely on
Hostinger's own backups is a decision about the hosting rather than the code.

---

Everything below was checked against this repository and against the two
database copies on this machine — the numbers are real, not illustrative.

## A change to what customers receive

`renewal:send-notifications` used to message four groups every morning. One of
them was everybody renewing **within the next seven days** — and it sent them the
`subscription_expire` template, whose approved wording is *"your car subscription
expired and is due on …"*.

So twelve customers a day, with perfectly current plans, were told theirs had
expired. Every morning, until it actually did.

That group is gone. Nothing is sent before a plan has run out, which also
matches what the provider will carry: there is no approved template that says a
renewal is coming up. The daily volume goes from **36 to 24** — eleven past
their date, thirteen on hold — and those two keep going every day until the
customer renews or the order is taken out of that state by hand.

The numbers are not configurable in v1 and deliberately so: daily is what the
job does by running daily and not deduping, and v1 is being replaced. The
equivalent settings live on v2's Settings screen.

## What is actually being deployed

Two commits, `997f9b6` and `d70743a`, plus the change above. Between them they touch **17 files**:
controllers, Blade views, models, `routes/web.php`, `config/services.php`.

Three things that are *not* involved, which shortens the job considerably:

| | |
|---|---|
| **No `composer install`** | Neither commit touches `composer.json` or `composer.lock`. |
| **No `npm run build`** | No front-end source changed. The views are Blade, rendered on the server, and the committed `build/` assets are untouched since Jul 2024. |
| **No new packages, no PHP version change** | Same dependencies as the running site. |

## The 14 database migrations

`u841499718_eswachh_live` has run 32 of the repository's 46 migrations. These 14
are pending:

```
2024_05_03_073029_create_customers_table
2026_08_13_120000_create_sector_user_table
2026_08_13_120100_add_sector_id_to_orders_table
2026_08_13_120150_fix_payment_history_date_auto_update
2026_08_13_120200_add_sector_id_to_payment_history_table
2026_08_13_120300_add_indexes_for_sector_reporting
2026_08_13_120400_create_franchise_owner_role
2026_08_13_130000_allow_franchise_owner_to_manage_users
2026_08_13_140000_add_payment_id_to_payment_history_table
2026_08_14_100000_add_payment_tracking_to_payment_history_table
2026_08_14_100100_create_payment_permissions
2026_08_14_110000_create_complaints_table
2026_08_14_110100_create_cleaner_attendances_table
2026_08_14_110200_create_complaint_and_attendance_permissions
```

**Every one is additive.** New tables, new columns, new indexes, and permissions
created with `firstOrCreate`. The only `drop` statements in any of them are in
`down()`, which runs on rollback and not on deploy. Nothing in `up()` deletes,
truncates or rewrites a row.

Two are worth knowing about individually:

- `create_customers_table` checks `Schema::hasTable('customers')` first. The
  table already exists on the server without the migration recorded, and without
  that guard `php artisan migrate` would abort on it and leave the other
  thirteen unrun.
- `fix_payment_history_date_auto_update` alters the column that used to rewrite
  itself on every update. Existing values are kept.

`u841499718_eswachh_testing` has run 45 of the 46 — only
`create_customers_table` is pending there. If that is the database behind the
test subdomain, it is already almost current.

## Before you touch anything

**Take a database backup and download it.** Hostinger's own backup is a
convenience, not a guarantee — a copy on your machine is what you actually
restore from at 11pm.

**Know which database the subdomain uses.** This is the one mistake that cannot
be undone by re-uploading files. If `testv1.eswachh.in` is pointed at the live
database, then "testing" the migrations migrates production. Open the `.env` in
`public_html/testv1` and read `DB_DATABASE` before running anything.

## The single command

```bash
php artisan eswachh:deploy
```

It closes the site, runs the pending migrations, seeds users and permissions if
the database is empty, clears and rebuilds the caches in the right order, links
storage, and opens the site again — reporting what it did at each step. Safe to
run twice.

| Option | |
|---|---|
| `--pretend` | Says what it would do and changes nothing. Run this first. |
| `--force` | Skips the confirmation. For scripts; type the confirmation by hand on production. |
| `--seed` | Seeds users, roles and permissions on a database that already has tables. Assumed automatically when the database is empty. |

It names the database it is about to touch before doing anything, and asks for
confirmation. That question is the point of the command: pointing a test
subdomain at the live database and running migrations is the one mistake here
that re-uploading files cannot undo.

### On a blank database

`u841499718_eswachh_v1` is empty, so the command builds it from
`database/schema/mysql-schema.sql` — which Laravel loads by running the `mysql`
client. **The migrations cannot build a fresh database on their own**: they were
squashed into that dump, and running them one at a time stops partway with
`Unknown column 'user_id' in 'orders'` and leaves about thirty tables behind.

The command checks it can reach the `mysql` client *before* taking the site
down, and stops with an explanation if it cannot. If that happens on the
hosting, import a dump of the live database through phpMyAdmin instead and then
run the command — it will apply the fourteen migrations on top.

For a test subdomain, importing a dump is the better route anyway: it gives you
real data to test the new screens against.

### What it looks like on a database with data

Verified against a restored copy of the live database:

```
Database eswachh_v1_live_copy
14 migration(s) to run: ...

data intact:  users=509 orders=1965 payments=484
new tables:   complaints, cleaner_attendances, sector_user
new columns:  orders.sector_id, payment_history.payment_id
migrations:   46 recorded
```

Every row still there, every new table and column added.

### One thing it deliberately does not do

**It does not run `route:cache`.** Three route names are registered twice —
`frontend.orders.index`, `frontend.orders.show` and `backend.orders.renew` —
because `Modules/Order/routes/api.php` reuses names from `web.php`. Laravel
refuses to serialise a route table with a repeated name, so `route:cache` fails
outright.

This has been true since the original import; it is not something the franchise
work introduced. The site runs perfectly well without the route cache. The
command detects the duplicates, names them, skips that one step, and will start
caching routes on its own the day the names are made unique.

## Step by step

### 1. Rehearse on testv1

1. Restore the production database dump into a **separate** database — not the
   live one. Point `testv1`'s `.env` at that copy.
2. Upload the working tree, or pull the branch if the subdomain is wired to
   GitHub. `vendor/`, `.env` and `node_modules/` are gitignored, so a `git pull`
   will not touch them; a manual upload must not include them either.
3. Add the missing keys to `testv1`'s `.env` (see below).
4. Run, in this order:

   ```bash
   php artisan down
   php artisan migrate --force
   php artisan config:clear && php artisan cache:clear
   php artisan route:clear && php artisan view:clear
   php artisan config:cache
   php artisan up
   ```

5. Walk the new features: an order list filtered by sector, a payment taken end
   to end, a complaint raised and assigned, a cleaner marked present. Sign in as
   a franchise owner as well as an administrator — the sector scoping is the
   part most likely to behave differently with real data.

### 2. Then production

Same sequence, on the live folder and the live database. `php artisan down`
first is not ceremony: it stops an order being placed against half-migrated
tables.

Keep the maintenance window short by uploading the files first and running the
migrations second — the upload is the slow part and is harmless on its own.

## The `.env` keys that must be added

`config/services.php` now reads these. **Read through config rather than `env()`
at runtime, deliberately** — `env()` returns null once `config:cache` has run, so
without this a cached deploy would have made every payment fail silently.

| Key | Why |
|---|---|
| `RAZOR_KEY` | Razorpay. Missing means payments fail. |
| `RAZOR_SECRET` | Razorpay. |
| `MSG91_AUTH_TOKEN` | WhatsApp. **Not in the local `.env` either** — find it in the MSG91 dashboard. |
| `MSG91_WHATSAPP_NUMBER` | WhatsApp sender. |
| `SMS_ENABLED` | Not in the local `.env`. Set it deliberately: unset reads as off. |

Use the **live** Razorpay keys on production. The local `.env` holds a test key
(`rzp_te…`), and deploying that means real customers paying into a test account
that never settles.

Run `php artisan config:cache` **after** editing `.env`, never before, or the
cache keeps the old values.

## If it goes wrong

```bash
php artisan down
php artisan migrate:rollback --step=14   # only if a migration is the problem
# restore the files, or the database dump if data changed
php artisan config:clear && php artisan config:cache
php artisan up
```

Rolling back the migrations drops the new tables and columns. Anything entered
into them since the deploy — complaints, attendance, sector assignments — goes
with them. That is why the dump comes first.

## Two data problems worth fixing while you are in there

Neither is caused by this deploy; both are already in the live data and both get
worse the longer they sit.

- **38 customer profiles point at a society that no longer exists** (`society_id`
  above 7, orphaned when the society list was rebuilt). 20 of them have orders.
  Their price is missing whatever surcharge that society carries.
- **7 car numbers are registered to more than one customer.** Anything that
  finds a plan by car number takes the first match, so one of those customers
  reaches the other's plan.
