# DSWD Payout Dashboard (DATS)

A Vue 3 front end for tracking DSWD payouts. People sign in with a Firebase account, and the page they get depends on their role: Admin, MANCOM, RDV Focal (CIS) or RDV Focal (DRMD).

Logins, users, targets, payout figures and the audit log all live in Firebase.

## Tech stack

| Layer | Technology | Version |
|---|---|---|
| Front end | [Vue 3](https://vuejs.org) single-file components (`<script setup>`) | 3.5 |
| UI components | [PrimeVue](https://primevue.org) (Aura theme) with PrimeIcons | 4.x |
| Charts | [Chart.js](https://www.chartjs.org), through PrimeVue's `Chart` component | 4.x |
| Styling | Scoped CSS in each component, plus [Tailwind CSS](https://tailwindcss.com) | 4.x |
| Build tool | [Vite](https://vite.dev) | 8.x |
| Logins and data | [Firebase](https://firebase.google.com) Authentication + Realtime Database | 12.x |
| Server-side | Plain PHP — the entry page and account management | 8.4 |
| Dev environment | Docker Compose | |

**How the pieces connect:**

1. The `php` container serves `public/index.php` on port 8000: an empty `<div id="app">` plus the tags that load the Vue app.
2. In development the page loads the Vue code from Vite on port 5173, so saving a file updates the browser. Without `VITE_DEV_SERVER`, it loads the built files from `public/build`.
3. `resources/FrontEnd/app.js` registers the PrimeVue components and mounts `App.vue`.
4. Signing in goes through Firebase Authentication. The person's role comes from `users/{uid}` in the database, and `App.vue` shows the page for that role.
5. Creating accounts, setting passwords and blocking sign-in happen in `public/api/admin-users.php`, because a browser is not allowed to manage other people's logins.
6. Every table reads the database through `resources/FrontEnd/data/`, so pages never touch Firebase directly.

## Requirements

- **[Docker Desktop](https://www.docker.com/products/docker-desktop/)**
- Access to the **`pdmsfoxi`** Firebase project

To run it without Docker you need **PHP 8.3+** and **Node.js 20.19+ or 22.12+**.

## First-time setup

```sh
git clone https://github.com/mceej/pdms_front-end.git
cd pdms_front-end
cp .env.example .env
```

Two files are needed, and **neither belongs in git**:

| File | Where it comes from | What it is for |
|---|---|---|
| `.env` | Firebase console → Project settings → General → Your apps | Lets the browser reach Firebase. Not secret. |
| `service-account.json` | Firebase console → Project settings → Service accounts → Generate new private key | Lets the server manage accounts. **Secret.** |

Then start it:

```sh
docker compose up
```

Open **http://localhost:8000**. The first start takes a few minutes while it installs npm packages inside the container. `Ctrl+C` stops it, or `docker compose down` from another terminal.

| Address | What it is |
|---|---|
| http://localhost:8000 | The app. Always use this one. |
| http://localhost:5173 | Vite's file server, which only feeds the page above. |

## Signing in

There is no self-registration and no password reset. An administrator creates every account on the **User Management** page, choosing the person's name, email, section, role and first password.

Roles decide the page:

| Role | Section | Page |
|---|---|---|
| ADMIN | any | Admin (dashboard, audit log, user management) |
| MANCOM | any | MANCOM |
| RDV Focal | CIS | RDV CIS |
| RDV Focal | DRMD | RDV DBRM |

An account marked **Inactive** cannot sign in at all — the status also disables the Firebase login.

## Firebase

| Piece | Where |
|---|---|
| Project | `pdmsfoxi` ([console](https://console.firebase.google.com/project/pdmsfoxi/overview)) |
| Logins | Authentication, email and password |
| Data | Realtime Database (`asia-southeast1`) |
| Security rules | [`database.rules.json`](database.rules.json) in this repository |

The rules deny everything by default: people read their own profile, admins read and write all of them, signed-in staff read targets while Admin or the matching RDV Focal writes them, payout data is read-only except to admins, and audit entries can be created but never changed.

After editing the rules, publish them:

```sh
npx firebase-tools login
npx firebase-tools deploy --only database
```

The database branches are `users`, `targets`, `geographies`, `payoutRecords`, `summaries` and `auditLogs`. Each holds flat records keyed by id, so the data can move to Firestore later without reshaping it.

## Sample data

The dashboard reads Firebase. A new database starts empty, so fill it with the sample set — 26 Region XI locations, 45 payout records and 10 targets per section:

```sh
php scripts/seed-firebase.php     # add --force to overwrite branches that already hold data
```

It never touches `users`, so logins and profiles are safe. The figures it loads come from `resources/FrontEnd/mock/payoutData.json`.

## Importing a served list

An RDV Focal or an admin imports a CSV on the **Import Served List** page. The file goes to
`public/api/served-lists.php`, which checks it, writes the payout records and records the import in the audit
log — successes and failures alike.

The file holds one beneficiary per row. Where the payout happened comes from the form, not the file:

| Column | Required | Example |
|---|---|---|
| `served_date` | yes | 2026-09-20 |
| `is_paid` | yes | yes / no / true / false / 1 / 0 |
| `disbursed_amount` | yes | 5000 |
| `beneficiary_reference` | no | BEN-001 |
| `target_amount` | no | 5000 (defaults to the disbursed amount) |
| `payout_site` | no | Covered Court |

[`scripts/sample-served-list.csv`](scripts/sample-served-list.csv) is a working example. Rows that fail a check
are skipped and counted, and the reason is kept in the audit entry; the rest still import.


### Every payout record belongs to an import

Each import creates a record in the `servedLists` branch, and every payout row it brings in carries that
import's id. The sample data is no different: seeding creates an import called "Region XI sample data" holding
its 45 rows.

Deleting an import on the Import Served List page therefore removes its payout records as well, and the dashboard
figures change accordingly. The page asks first, naming the file and how many records will go. An RDV Focal may
delete the imports they made; an administrator may delete any. Both are recorded in the audit log.

The programme follows the person's section — CIS imports AICS, DRMD imports ECT — and an ECT import must name
the disaster. Importing the same file into the same barangay twice is refused; send it again with `replace` to
overwrite the earlier rows.

## Useful commands

| Command | What it does |
|---|---|
| `docker compose up` | Starts the app (PHP on 8000, Vite on 5173) |
| `docker compose down` | Stops it |
| `docker compose logs -f vite` | Shows the front-end build output |
| `npm run build` | Builds the front end into `public/build` |
| `npx firebase-tools deploy --only database` | Publishes the security rules |
| `php scripts/seed-firebase.php` | Fills the database with sample data |

## Project structure

```
docker-compose.yml                  PHP + Vite containers
vite.config.js                      Build and dev-server settings
database.rules.json                 Firebase security rules
config.php                          Server settings (not web-accessible)
service-account.json                Firebase admin key — secret, never committed
src/                                PHP classes (FirebaseAdmin, GoogleServiceAccount, HttpJson)
public/index.php                    The page that loads the Vue app
public/api/admin-users.php          Create accounts, set passwords, block sign-in
public/api/audit.php                Records audit entries, with the caller’s address
scripts/seed-firebase.php           Loads the sample data
resources/FrontEnd/app.js           Vue entry point; registers PrimeVue
resources/FrontEnd/firebase/        Firebase connection
resources/FrontEnd/auth/            Sign in, sign out, role lookup
resources/FrontEnd/data/            Database reads and server calls, one file per table
resources/FrontEnd/App.vue          Root component (login or the role's page)
resources/FrontEnd/LoginPage.vue    Login page layout
resources/FrontEnd/pages/           One folder per role
resources/FrontEnd/dashboard/       Dashboard parts (table, charts, overview)
resources/FrontEnd/components/      Shared components (sidebar, login form, dialogs)
resources/FrontEnd/mock/            The sample figures the seed script reads
logo/                               DSWD logos, loaded through Vite
memory.md                           Team coding rules (code ethics)
```

## Troubleshooting

- **"Firebase is not set up yet":** `.env` is missing or empty. Copy `.env.example` and fill it in, then `docker compose restart vite`.
- **"Only an active administrator can do this":** the signed-in account isn't an active Admin in `users`.
- **"Permission denied" in the browser console:** the security rules haven't been published, or the account is reading something its role can't.
- **Account management fails with a key error:** `service-account.json` is missing from the project root.
- **The page says to run `npm run build`:** either start Vite (`docker compose up`) or build the front end.
- **Port 8000 or 5173 is already in use:** stop whatever is using it, or change the port mapping in `docker-compose.yml`.
- **npm packages look broken after switching branches:** `docker compose down -v`, then `docker compose up`.
