# Code Ethics

Rules everyone working on this project follows. The project is a Vue 3 front end served by plain PHP — no framework.

## Coding Style

### PHP

- Follow **PSR-12**.
- Keep lines between **80 and 120 characters** max.
- Use **CamelCase** for class names (e.g. `PayoutRepository`, `CsvImporter`).

### Vue.js

- Use **CamelCase** naming.
  - Vue components: `LoginForm.vue`, `PayoutTable.vue`
  - Variables and functions: `rememberMe`, `fetchPayouts()`
- **Routes and URLs are lowercase** (e.g. `/payouts`, `/api/payout-dashboard`).

## Best Practices

- **Single responsibility:** each class, function and component does one job. Keep data loading out of page components — put it in its own module (today `resources/FrontEnd/mock/payoutDashboard.js`, later a real API module). In PHP, keep the request handling separate from the queries.
- **One query, not many:** never run a query inside a loop. Fetch what you need up front, joining or grouping in the query, then match the rows in code.

  ```php
  $sql = 'SELECT p.*, g.name AS province_name
          FROM payout_records p
          JOIN geographies g ON g.id = p.province_id';
  $payouts = $pdo->query($sql)->fetchAll();
  ```

- **Chunking:** process large datasets in batches instead of loading everything into memory.

  ```php
  $statement = $pdo->prepare('SELECT * FROM payout_records LIMIT :limit OFFSET :offset');

  // Read uploaded CSVs a row at a time, too.
  while (($row = fgetcsv($handle)) !== false) {
      // ...
  }
  ```

- **No white spaces:** no trailing white space at the end of lines (`.editorconfig` trims it in supported editors).
- **No queries in templates:** a Vue component receives its data through props or a data module; a PHP page gets its data before it renders anything. No database calls inside markup.
- **Do not read environment variables directly:** load them in one config file and read that everywhere else.

  ```php
  // config.php
  return ['viteDevServer' => getenv('VITE_DEV_SERVER') ?: ''];

  // elsewhere
  $config = require __DIR__ . '/config.php';
  $viteDevServer = $config['viteDevServer'];
  ```

  In Vue, read `import.meta.env` in a single module rather than scattering it through components.
