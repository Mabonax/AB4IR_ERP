# cPanel Subdomain Deployment

Target URL: `https://erp.programofaction.org/`

The current directory listing at the target URL means LiteSpeed is serving the subdomain folder itself. For Laravel, the subdomain document root must serve the application `public` directory, because `public/index.php` is the front controller and `public/.htaccess` routes all non-file requests into Laravel.

## Required cPanel Settings

1. In cPanel, open **Domains** or **Subdomains** and edit `erp.programofaction.org`.
2. Set the document root to the Laravel public directory, for example:

   ```text
   /home/<cpanel-user>/program-of-action-erp/public
   ```

   If cPanel does not allow a document root outside `public_html`, place the project one level above the web root and point the subdomain to that project's `public` folder. Do not point the subdomain at the project root.

3. Disable directory indexes for the subdomain if cPanel exposes that option.
4. Confirm PHP is set to PHP 8.2 or newer.
5. Enable required PHP extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, and `zip`.

## Files To Upload

Upload the whole Laravel project except local-only folders such as `node_modules`, `.git`, `.tmp-build`, and any local `.env`.

The deployed server must contain:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
vendor/
artisan
composer.json
composer.lock
package.json
package-lock.json
```

If Composer is available in cPanel Terminal, install production PHP dependencies on the server. If it is not available, run Composer locally and upload the generated `vendor` directory.

## Environment File

1. Copy `.env.cpanel.example` to `.env` on the server.
2. Set real cPanel MySQL credentials:

   ```dotenv
   DB_DATABASE=<cpanel-user>_poaerp
   DB_USERNAME=<cpanel-user>_poaerp
   DB_PASSWORD=<real-password>
   ```

3. Generate and keep a production app key:

   ```sh
   php artisan key:generate --force
   ```

4. Replace all placeholder passwords and mail credentials before running validation.

For shared cPanel hosting, this template uses database-backed sessions, cache, and queues instead of Redis.

## Build Assets

Build frontend assets before upload or in cPanel Terminal:

```sh
npm ci
npm run build
```

Do not deploy `public/hot`. That file is only for local Vite development and causes Laravel to look for a dev server instead of `public/build`.

## Post-Deploy Commands

From the project root on cPanel:

```sh
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
php artisan system:validate-deployment --services --strict
php artisan system:deployment-status
```

If `storage:link` fails because symlinks are disabled on the host, create a cPanel symlink from `public/storage` to `storage/app/public`, or ask the host to enable symlink creation for the account.

## Queues And Scheduler

This application uses Laravel queues. On cPanel, configure:

```cron
* * * * * cd /home/<cpanel-user>/program-of-action-erp && php artisan schedule:run >> /dev/null 2>&1
```

If the host supports persistent processes, run a queue worker:

```sh
php artisan queue:work database --queue=default --tries=3 --timeout=120
```

If persistent workers are not available, add a cron fallback:

```cron
* * * * * cd /home/<cpanel-user>/program-of-action-erp && php artisan queue:work database --stop-when-empty --queue=default --tries=3 --timeout=120 >> /dev/null 2>&1
```

## Smoke Checks

After deployment:

1. Visit `https://erp.programofaction.org/up`.
2. Visit `https://erp.programofaction.org/` and confirm it no longer shows `Index of /`.
3. Run `php artisan system:deployment-status`.
4. Confirm `storage_writable`, `database_connected`, and `redis_connected` are all true. `redis_required` should be false for the cPanel env template.
