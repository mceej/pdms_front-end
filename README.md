# DSWD Payout Dashboard

A Laravel app with a Vue 3 front end. Right now it has one screen, the login page, and the login form is not connected to the backend yet.

## Tech stack

| Layer | Technology | Version |
|---|---|---|
| Backend framework | [Laravel](https://laravel.com/docs) | 13.x |
| Language | PHP | 8.3 or newer |
| Frontend | [Vue 3](https://vuejs.org) single-file components (`<script setup>`) | 3.5 |
| Build tool | [Vite](https://vite.dev) with `laravel-vite-plugin` and `@vitejs/plugin-vue` | 8.x |
| Styling | Scoped CSS inside Vue components; [Tailwind CSS](https://tailwindcss.com) is installed (see note below) | 4.x |
| Database | SQLite by default (`database/database.sqlite`); the MySQL driver is also available | |
| Tests / formatting | PHPUnit, Laravel Pint | 12.x / 1.x |

**How the pieces connect:** `routes/web.php` returns `resources/views/welcome.blade.php`. That Blade page loads the Vite bundle and contains an empty `<div id="app">`. `resources/js/app.js` mounts `App.vue` into that div. There is no Inertia or Vue Router yet.

> **Tailwind note:** the `@tailwindcss/vite` package is installed but hasn't been added to the `plugins` list in `vite.config.js`. Tailwind classes won't be generated until it is added.

## Requirements

- **PHP 8.3+** with the standard Laravel extensions (`ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite`, `tokenizer`, `xml`)
- **Composer 2**
- **Node.js 20.19+ or 22.12+** (required by Vite 8), which includes npm
- **Git**

On macOS and Windows, [Laravel Herd](https://herd.laravel.com) installs PHP and Composer for you. Install Node from [nodejs.org](https://nodejs.org) or with Homebrew (`brew install node`).

To check your versions:

```sh
php -v
composer -V
node -v
npm -v
```

## First-time setup

```sh
git clone https://github.com/Jhnqst/dswd_repository.git
cd dswd_repository
composer setup
```

`composer setup` does the following, in order:

1. Installs PHP packages (`composer install`)
2. Copies `.env.example` to `.env` if `.env` doesn't exist yet
3. Generates the app key (`php artisan key:generate`)
4. Creates `database/database.sqlite` and runs the migrations
5. Installs JavaScript packages (`npm install`)
6. Builds the front end (`npm run build`)

Run it once, on a fresh clone. It generates a new `APP_KEY` every time it runs. To update packages later, use `composer install` and `npm install` instead.

<details>
<summary>Manual setup (same steps, one at a time)</summary>

```sh
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate           # answer "yes" when asked to create the SQLite database
npm install
npm run build
```

</details>

## Running the project

```sh
composer dev
```

Then open **http://127.0.0.1:8000** in your browser. Press `Ctrl+C` to stop everything.

`composer dev` starts four processes in one terminal:

| Process | Command | What it does |
|---|---|---|
| server | `php artisan serve` | Runs the Laravel app on port 8000 |
| vite | `npm run dev` | Serves the Vue and CSS files on port 5173 and hot-reloads them when you save |
| queue | `php artisan queue:listen` | Runs queued jobs |
| logs | `php artisan pail` | Shows the Laravel log live |

Port 5173 only serves the front-end files. Always open the app through port 8000.

**Using two terminals instead:**

```sh
php artisan serve    # terminal 1
npm run dev          # terminal 2
```

**Using Herd instead of `php artisan serve`:** run `herd link dswd` in the project folder, set `APP_URL=http://dswd.test` in `.env`, then run `npm run dev` and open http://dswd.test.

## Useful commands

| Command | What it does |
|---|---|
| `composer test` | Runs the PHPUnit tests |
| `./vendor/bin/pint` | Formats PHP code to PSR-12 (set in `pint.json`) |
| `npm run build` | Builds the front end into `public/build` (the app then works without `npm run dev`) |
| `php artisan migrate` | Runs new database migrations |
| `php artisan migrate:fresh` | Rebuilds the database from scratch (**deletes all data**) |
| `php artisan route:list` | Lists all routes |

## Project structure

```
app/                                PHP code (models, controllers, providers)
routes/web.php                      Web routes
resources/views/welcome.blade.php   HTML page that loads Vite and holds <div id="app">
resources/js/app.js                 Vue entry point
resources/js/App.vue                Root Vue component (renders the current page)
resources/js/pages/                 Page components (LoginPage.vue)
resources/js/components/            Reusable components (LoginForm.vue)
resources/css/app.css               Global stylesheet
logo/                               DSWD logos, imported into the Vue components through Vite
database/                           Migrations, seeders, and the SQLite file
tests/                              PHPUnit tests
memory.md                           Team coding rules (code ethics)
```

`LoginPage.vue` uses paths like `/logo/dswdlogo.png`. Vite finds these files in the `logo/` folder at the project root and adds them to the build, so they don't need to be in `public/`.

## Troubleshooting

- **"Vite manifest not found":** the front end hasn't been built. Run `npm run build`, or keep `npm run dev` running.
- **The page tries to load files from `localhost:5173` but Vite isn't running:** delete the `public/hot` file. Vite creates it while running and sometimes leaves it behind after a crash.
- **`could not find driver` (SQLite):** turn on the `pdo_sqlite` extension in your PHP installation.
- **Port 8000 is already in use:** run `php artisan serve --port=8001` and change `APP_URL` in `.env` to match.
