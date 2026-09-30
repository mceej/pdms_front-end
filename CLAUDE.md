# Project Rules

DSWD Payout Dashboard (DATS) — a Vue 3 front end with Firebase for logins and data, served by plain PHP. **This project does not use Laravel.** It was removed on purpose; don't reintroduce it, `composer`, or any framework without asking first.

Follow the team's code ethics in @memory.md.

## Stack

- **Front end:** Vue 3 single-file components with `<script setup>`, PrimeVue 4 (Aura theme), Chart.js, Tailwind CSS 4, built by Vite 8.
- **Logins and data:** Firebase Authentication and Realtime Database, project `pdmsfoxi`.
- **Server-side:** plain PHP 8.4 — `public/index.php` loads the Vue app, and `public/api/admin-users.php` manages accounts.
- **Environment:** Docker Compose runs a `php` container (port 8000) and a `vite` container (port 5173).

## Running it

```sh
docker compose up     # then open http://localhost:8000
```

Never point the user at port 5173; it only feeds front-end files to the page on 8000. After changing `public/index.php`, `vite.config.js` or `docker-compose.yml`, restart with `docker compose restart`.

## Secrets

- `service-account.json` is a **real secret**. It never goes in git, never in the front end, never in a log or a chat message. Only PHP reads it, through `config.php`.
- `.env` holds the Firebase web settings. Those are public identifiers, not secrets — the security rules are what protect the data.
- Never write passwords into the database. Firebase Authentication holds them.

## Where things live

- `resources/FrontEnd/` — all Vue code. Pages per role in `pages/`, shared pieces in `components/`, dashboard parts in `dashboard/`.
- `resources/FrontEnd/firebase/app.js` — the only place Firebase is initialised.
- `resources/FrontEnd/auth/session.js` — signing in and out, and the role lookup that decides the page.
- `resources/FrontEnd/data/` — every database read and every server call, one file per table. Pages must not import Firebase directly; that boundary is what keeps a later move to Firestore small.
- `resources/FrontEnd/mock/` — sample dashboard data, still in use until the payout tables move to Firebase.
- `src/` and `config.php` — PHP classes and settings, outside `public/` so they are never served.
- `database.rules.json` — security rules. Changing what a role may do means changing this file and publishing it.

## Rules that matter

- Accounts are created only by an admin, through the User Management page. There is no self-registration and no password reset.
- A browser cannot create another person's login, set someone's password, or disable an account. Those go through `public/api/admin-users.php`, which checks the caller's ID token and their `ADMIN` role first.
- Keep components small and single-purpose. Prefer a new file in `components/` over growing a page component.
- Use the PrimeVue components already registered in `resources/FrontEnd/app.js` before adding libraries.
- Styling goes in the component's own scoped `<style>` block.
- Use fixed sizes (px/rem) rather than viewport units for layout spacing, so pages stay stable when the browser is zoomed.
- PHP follows PSR-12. There is no formatter installed, so match the existing style by hand.

## Checking your work

There is no test suite. Verify changes by loading http://localhost:8000 and signing in with a real account (ask the user; the repository holds no passwords). Run `npm run build` before finishing to confirm the front end still compiles. When a change touches accounts or rules, say plainly what you tested and what you could not.
