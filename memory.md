# Code Ethics

Rules everyone working on this project follows.

## Coding Style

### PHP

- Follow **PSR-12**.
- Keep lines between **80 and 120 characters** max.
- Use **CamelCase** for class names (e.g. `UserService`, `PayoutController`).

### Laravel / Vue.js

- Use **CamelCase** naming.
  - Vue components: `LoginForm.vue`, `PayoutTable.vue`
  - Variables and functions: `rememberMe`, `fetchPayouts()`
- **Routes are lowercase** (e.g. `/payouts`, `payouts.index`).

## Best Practices

- **Single responsibility:** each class and method does one job. Move business logic out of controllers into service classes (e.g. `UserService`).
- **Eager loading:** load relationships up front with `with()` to avoid N+1 queries.

  ```php
  $payouts = Payout::with('beneficiary')->get();
  ```

- **Chunking:** process large datasets in chunks instead of loading everything at once.

  ```php
  Payout::chunkById(500, function ($payouts) {
      // ...
  });
  ```

- **No white spaces:** no trailing white space at the end of lines (`.editorconfig` trims it in supported editors).
- **No queries in Blade:** get the data in the controller and pass it to the view.

  ```php
  return view('payouts.index', ['payouts' => $payouts]);
  ```

- **Do not access `.env` directly:** only call `env()` inside `config/` files. Everywhere else, use `config()`.

  ```php
  // config/services.php
  'payout' => ['key' => env('PAYOUT_API_KEY')],

  // app code
  $key = config('services.payout.key');
  ```
