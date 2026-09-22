# Project Rules

DSWD Payout Dashboard — a Vue 3 front end served by plain PHP. **This project does not use Laravel.** It was removed on purpose; don't reintroduce it, `composer`, or any framework without asking first.

Follow the team's code ethics in @memory.md.

## Stack

- **Front end:** Vue 3 single-file components with `<script setup>`, PrimeVue 4 (Aura theme), Chart.js, Tailwind CSS 4, built by Vite 8.
- **Server-side:** plain PHP 8.4. Right now that is only `public/index.php`, which loads the Vue app. A PHP API will be added later.
- **Environment:** Docker Compose runs a `php` container (port 8000) and a `vite` container (port 5173).

## Running it

```sh
docker compose up     # then open http://localhost:8000
```

Never point the user at port 5173; that only serves front-end files to the page on 8000. After changing `public/index.php`, `vite.config.js` or `docker-compose.yml`, restart with `docker compose restart`.

## Where things live

- `resources/FrontEnd/` — all Vue code. Pages sit at the top level (`LoginPage.vue`, `Dashboard.vue`), shared pieces in `components/`, dashboard parts in `dashboard/`, served-list page in `serverList/`.
- `resources/FrontEnd/mock/` — sample data standing in for the API that doesn't exist yet. `fetchPayoutDashboard(filters)` returns the shape a real endpoint should return. Replace it with real requests when the PHP API lands; don't build new features on top of it without saying so.
- `public/` — served directly by PHP. `public/build/` is generated; never edit it by hand.
- `logo/` — images referenced as `/logo/...`, resolved by Vite.

## Conventions

- Keep components small and single-purpose. Prefer a new file in `components/` over growing a page component.
- Use the PrimeVue components already registered in `resources/FrontEnd/app.js` before adding new libraries.
- Styling goes in the component's own scoped `<style>` block, matching the surrounding code.
- Use fixed sizes (px/rem) rather than viewport units for layout spacing, so the pages stay stable when the browser is zoomed.
- PHP follows PSR-12. There is no formatter installed now that Pint is gone, so match the existing style by hand.

## Checking your work

There is no test suite. Verify changes by loading http://localhost:8000, logging in with any username and password (tick the Privacy Policy box), and looking at the page. Run `npm run build` before finishing to confirm the front end still compiles.
