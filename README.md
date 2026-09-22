# DSWD Payout Dashboard

A Vue 3 front end for tracking DSWD payouts, with a login page and a payout dashboard.

PHP handles the admin login and serves the page that loads the Vue app. Everything the dashboard displays is still **sample data** bundled into the front end; there is no database yet.

## Signing in

There is one account, `admin`. Ask the team for the password — the repository only holds a hash of it.

The login is handled by PHP, so it works only when the page is opened through the PHP server (http://localhost:8000). You stay signed in until you press **Log out** or close the browser.

To change the password:

```sh
php -r 'echo password_hash("the new password", PASSWORD_DEFAULT), PHP_EOL;'
```

Put the result in `passwordHash` in `config.php`. You can also set `ADMIN_USERNAME` and `ADMIN_PASSWORD_HASH` as environment variables instead, which is what a deployed server should do.

## Tech stack

| Layer | Technology | Version |
|---|---|---|
| Front end | [Vue 3](https://vuejs.org) single-file components (`<script setup>`) | 3.5 |
| UI components | [PrimeVue](https://primevue.org) (Aura theme) with PrimeIcons | 4.x |
| Charts | [Chart.js](https://www.chartjs.org), through PrimeVue's `Chart` component | 4.x |
| Styling | Scoped CSS in each component, plus [Tailwind CSS](https://tailwindcss.com) | 4.x |
| Build tool | [Vite](https://vite.dev) | 8.x |
| Server-side | Plain PHP (entry page and the login endpoints) | 8.4 |
| Dev environment | Docker Compose | |

**How the pieces connect:**

1. The `php` container serves `public/index.php` on port 8000. That page is just an empty `<div id="app">` plus the tags that load the Vue app.
2. In development it loads the Vue code from the Vite dev server on port 5173, so saving a file updates the browser right away. Without the `VITE_DEV_SERVER` setting, it loads the built files from `public/build` instead.
3. `resources/FrontEnd/app.js` registers the PrimeVue components and mounts `App.vue`.
4. `App.vue` asks `/api/session.php` whether this browser is already signed in, then shows `LoginPage.vue` or `Dashboard.vue`. There is no Vue Router yet.
5. Signing in posts to `/api/login.php`, which checks the password and starts a PHP session. `/api/logout.php` ends it.
6. `Dashboard.vue` gets its numbers from `resources/FrontEnd/mock/`. See [Mock data](#mock-data).

## Requirements

- **[Docker Desktop](https://www.docker.com/products/docker-desktop/)** — this is the only requirement for the setup below.

To run it without Docker you need **PHP 8.3+** and **Node.js 20.19+ or 22.12+** on your machine.

## Running the project

```sh
git clone https://github.com/Jhnqst/dswd_repository.git
cd dswd_repository
docker compose up
```

Then open **http://localhost:8000**.

The first start takes a few minutes because it installs the npm packages inside the container. Later starts are quick. Press `Ctrl+C` to stop, or run `docker compose down` from another terminal.

| Address | What it is |
|---|---|
| http://localhost:8000 | The app. Always use this one. |
| http://localhost:5173 | Vite's file server. It only serves the front-end files to the page above. |

<details>
<summary>Running without Docker</summary>

```sh
npm install

# terminal 1: front-end files with live reload
npm run dev

# terminal 2: the page itself
VITE_DEV_SERVER=http://localhost:5173 php -S localhost:8000 -t public
```

</details>

<details>
<summary>Running the built version (what gets deployed)</summary>

```sh
npm run build                  # or: docker compose run --rm vite npm run build
php -S localhost:8000 -t public
```

Leave `VITE_DEV_SERVER` unset so the page loads the built files from `public/build`. In `docker-compose.yml`, remove or comment out the `VITE_DEV_SERVER` line under the `php` service.

</details>

## Mock data

Until the PHP API exists, the dashboard reads sample data instead of calling a server:

| File | What it holds |
|---|---|
| `resources/FrontEnd/mock/payoutData.json` | 26 Region XI locations and 45 payout records |
| `resources/FrontEnd/mock/payoutDashboard.js` | Applies the filters, groups the rows by location, and works out the totals |

`fetchPayoutDashboard(filters)` returns the same shape a real endpoint should return, so filters, drill-down and the charts all work. When the PHP API is ready, replace the call in `Dashboard.vue` with a real request and delete this folder.

Uploading a served-list CSV is switched off and shows a message, because importing needs a backend.

## Useful commands

| Command | What it does |
|---|---|
| `docker compose up` | Starts the app (PHP on 8000, Vite on 5173) |
| `docker compose down` | Stops it |
| `docker compose logs -f vite` | Shows the front-end build output |
| `npm run build` | Builds the front end into `public/build` |
| `npm run dev` | Runs Vite on its own, without Docker |

## Project structure

```
docker-compose.yml                  PHP + Vite containers
vite.config.js                      Build and dev-server settings
config.php                          Settings and the admin account (not web-accessible)
src/                                PHP classes (AdminAuthenticator, AdminSession)
public/index.php                    The page that loads the Vue app
public/api/                         Login, logout and session endpoints
public/build/                       Built front end (created by npm run build)
resources/FrontEnd/app.js           Vue entry point; registers PrimeVue
resources/FrontEnd/theme.js         PrimeVue theme (DSWD colors)
resources/FrontEnd/App.vue          Root component (login or dashboard)
resources/FrontEnd/LoginPage.vue    Login page layout
resources/FrontEnd/Dashboard.vue    Payout dashboard page
resources/FrontEnd/auth/            Talks to the login endpoints
resources/FrontEnd/components/      Shared components (LoginForm.vue)
resources/FrontEnd/dashboard/       Dashboard parts (table, charts, overview)
resources/FrontEnd/serverList/      Served-list page
resources/FrontEnd/mock/            Sample data (see above)
resources/css/app.css               Global stylesheet
logo/                               DSWD logos, loaded through Vite
memory.md                           Team coding rules (code ethics)
```

## Troubleshooting

- **The page says to run `npm run build`:** either start Vite (`docker compose up`) or build the front end.
- **The page loads but has no styling, or the browser console mentions port 5173:** the Vite container isn't running. Check `docker compose ps` and `docker compose logs vite`.
- **Port 8000 or 5173 is already in use:** stop whatever is using it, or change the port mapping in `docker-compose.yml`.
- **Edits don't show up in the browser:** reload once. If it keeps happening, restart with `docker compose restart vite`.
- **npm packages look broken after switching branches:** run `docker compose down -v` to clear the container's package volume, then `docker compose up`.
