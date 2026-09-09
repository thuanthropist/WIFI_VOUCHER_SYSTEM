# WiFi Voucher Billing System

A payment-triggered WiFi access system: a customer pays via mobile money (M-Pesa,
Airtel Money, Tigo Pesa) on a captive portal, and network access is granted
automatically — no attendant, no manual voucher handout.

The system is **vendor-agnostic by design**. Instead of integrating each router
brand's proprietary management API, every site's router/controller talks
standard **RADIUS** to a FreeRADIUS server, and FreeRADIUS reads/writes its
`radcheck` / `radreply` / `radacct` tables directly in this app's database.
Adding a new hardware brand means adding one `device_vendor` case for RADIUS
reply attributes ([GenerateVoucherJob.php](app/Jobs/GenerateVoucherJob.php)) —
never a new vendor SDK integration.

## Architecture

```
Customer phone --> Captive portal (Livewire) --> PaymentService --> Mobile money gateway
                                                        |
                                                        v  webhook (signature-verified)
                                                  PaymentService::handleCallback()
                                                        |
                                                        v
                                              GenerateVoucherJob (queued)
                                                 |              |
                                                 v              v
                                        vouchers table    radcheck / radreply
                                                                  ^
                                                                  |
Router/Controller (any vendor) <-- RADIUS (auth+acct) --> FreeRADIUS 3.x
```

- **Billing tables** (`plans`, `payments`, `sites`, `vouchers`) live on the
  default database connection.
- **RADIUS tables** (`radcheck`, `radreply`, `radgroupcheck`, `radgroupreply`,
  `radusergroup`, `radacct`, `radpostauth`, `nas`) live on the `radius`
  connection (`config/database.php`), which defaults to the *same* database
  but can point at a dedicated `radius` DB via `RADIUS_DB_*` env vars.
- FreeRADIUS is **not** part of this codebase — it's an external service you
  point at this database. See [Configuring a site's router](#configuring-a-sites-routercontroller)
  below.

## Requirements

- PHP 8.2+
- MySQL/MariaDB
- Composer
- Node.js (for building portal/admin assets)
- FreeRADIUS 3.x (external, see below)

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Edit `.env`: set `DB_*` to your MySQL instance, and `PAYMENT_GATEWAY` plus the
matching `SELCOM_*` / `CLICKPESA_*` / `AZAMPAY_*` credentials for whichever
mobile money aggregator you're integrating.

```bash
php artisan migrate
php artisan db:seed          # demo plans + a demo site + an admin login
php artisan storage:link
```

Run the app:

```bash
php artisan serve            # web app
php artisan queue:work       # processes GenerateVoucherJob after payment confirmation
php artisan reverb:start     # optional: real-time "payment confirmed" push (portal falls back to polling without it)
```

For scheduled cleanup/reporting to actually fire, add the standard Laravel
cron entry on your server:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Seeded admin login: `admin@wifi-billing.local` / `password` — **change this
immediately** in any non-local environment, and consider removing Breeze's
public `/register` route entirely for production (this is an internal billing
admin tool, not a public signup system).

## Key routes

| Route | Purpose |
|---|---|
| `GET /portal` | Captive portal (plan selection → phone number → payment → voucher). Routers should redirect unauthenticated clients here, appending `?site=<radius_nas_ip>`. |
| `POST /api/payments/initiate` | Starts a USSD/STK push charge. Throttled 6/min. |
| `POST /api/payments/callback/{gateway}` | Webhook the payment gateway calls back on. Signature-verified inside the gateway driver — **not** CSRF-protected (it's an API route with no session), don't add `web` middleware to it. |
| `GET /api/payments/{reference}/status` | Polling fallback for clients without a live websocket connection. |
| `GET /admin` | Dashboard: revenue, active sessions. Auth-protected. |
| `GET /admin/plans`, `/admin/sites`, `/admin/vouchers` | CRUD + history, auth-protected. |

## Swapping / adding a payment gateway

`PaymentGatewayManager` (`app/Services/PaymentGatewayManager.php`) resolves the
active driver from `PAYMENT_GATEWAY` in `.env`. Each driver
(`app/Services/PaymentGateways/*.php`) implements `PaymentGatewayContract`:
`initiate()`, `verifyWebhookSignature()`, `parseCallback()`. To add a fourth
aggregator, add its config block to `config/services.php`, a new driver class,
and a `create<Name>Driver()` method on the manager — nothing else changes.

## Configuring a site's router/controller

Every site needs two things: (1) FreeRADIUS pointed at this app's database,
and (2) the site's router registered as a RADIUS client with a shared secret
(done for you in `/admin/sites` — it writes both the `sites` row and the
matching `nas` table entry FreeRADIUS reads).

### 1. Point FreeRADIUS at this database

On the FreeRADIUS host, edit `mods-available/sql`:

```
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"
    server = "<DB_HOST>"
    port = <DB_PORT>
    login = "<DB_USERNAME>"
    password = "<DB_PASSWORD>"
    radius_db = "<DB_DATABASE or RADIUS_DB_DATABASE>"
    read_clients = yes
    client_table = "nas"
}
```

Enable it:

```bash
ln -s ../mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
```

In `sites-enabled/default`, make sure `sql` is uncommented inside the
`authorize {}`, `accounting {}`, and `post-auth {}` sections. Restart
FreeRADIUS (`systemctl restart freeradius`) after registering the first site,
since `read_clients = yes` loads NAS clients from the `nas` table at startup.

### 2. Register the site in `/admin/sites`

Fill in the router's management/RADIUS-source IP as **NAS IP**, pick the
**device vendor**, and save — a shared secret is auto-generated (stored
encrypted via Laravel's `encrypted` cast) and pushed into the `nas` table.

### 3. Configure the router itself

The shared secret and FreeRADIUS host/port (**1812/1813**, standard RADIUS
auth/accounting ports) are the only things that differ per vendor:

**MikroTik (RouterOS + Hotspot)**
```
/radius add service=hotspot address=<freeradius-host> secret=<shared-secret>
/ip hotspot profile set [find] use-radius=yes
```

**TP-Link Omada Controller**
Settings → Authentication → RADIUS Profile → add profile with the FreeRADIUS
host/port/secret, then set the site's Portal → Authentication Type to
"RADIUS" and select the profile.

**Ubiquiti UniFi Network**
Settings → Profiles → RADIUS → add server (host/port/secret). Under the
Guest Portal for the site, enable "RADIUS Authentication" and select the
profile.

**OpenWrt (CoovaChilli / wifidog captive portal)**
In `/etc/chilli/config`, set:
```
HS_RADIUS=<freeradius-host>
HS_RADSECRET=<shared-secret>
HS_UAMHOMEPAGE=https://<this-app-host>/portal?site=<this NAS's IP>
```

Whatever the vendor, the RADIUS reply attributes actually enforcing the
voucher (`Session-Timeout`, plus bandwidth: `Mikrotik-Rate-Limit` for
MikroTik, `WISPr-Bandwidth-Max-Up/Down` elsewhere) are already written by
`GenerateVoucherJob` at voucher-generation time — no per-vendor code runs at
authentication time, only at provisioning time.

## Scheduled tasks

| Command | Schedule | Purpose |
|---|---|---|
| `radius:sync-voucher-sessions` | every 5 min | Promotes `unused` → `active` when `radacct` shows the voucher was redeemed; flips `active` → `expired` once the plan's connected-time budget elapses. |
| `radius:cleanup-expired-sessions` | every 5 min | Deletes `radcheck`/`radreply` rows for any `expired` voucher, so it can never authenticate again. |
| `vouchers:cleanup-expired` | daily 00:10 | Expires vouchers that were never redeemed before their deadline. |
| `reports:daily-revenue` | daily 06:00 | Emails `ADMIN_REPORT_EMAIL` a revenue/voucher summary for the previous day (skipped if unset). |

Note: none of these can force-disconnect an in-progress session — that needs
a RADIUS Disconnect-Request/CoA, which isn't universally supported across
vendors. The NAS itself is what cuts the connection once `Session-Timeout`
elapses; the scheduled tasks just make sure the credentials can't be reused
afterwards.

## Security notes

- Site RADIUS shared secrets are encrypted at rest (`Site::$casts['shared_secret'] = 'encrypted'`).
- Payment webhooks are verified via each gateway's HMAC signature scheme
  inside its driver (`verifyWebhookSignature()`) — never trusted on
  Content-Type/IP alone.
- `/api/payments/initiate` is throttled (6/min); the callback route is
  throttled separately (60/min) since gateways may retry.
- Vouchers are single-use (one `radcheck` row per voucher code) and
  time-bound (`Session-Timeout` reply attribute + the scheduled expiry sweep).
