# Ubuntu Server Deployment

This project now ships with a production-oriented Docker and GitHub Actions pipeline for Ubuntu deployments.

## Runtime topology

- `app`: Laravel PHP-FPM container
- `web`: Nginx container serving `public/`
- `worker`: queue worker container
- `scheduler`: Laravel scheduler container
- `mysql`: MySQL 8.4 container
- `redis`: Redis 7.4 container

All Laravel runtime containers share the `app-storage` volume so uploaded files, sessions, logs, and cache-backed artifacts stay available across restarts.

## First-time server setup

1. Install Docker Engine and the Docker Compose plugin on Ubuntu.
2. Clone the repository onto the server:

```bash
git clone https://github.com/Mabonax/AB4IR_ERP.git /var/www/ab4irerp
cd /var/www/ab4irerp
```

3. Copy the application environment file and fill in production values:

```bash
cp .env.example .env
```

Generate an application key once before the first full startup:

```bash
docker compose run --rm app php artisan key:generate --force
```

Minimum values to change before first boot:

- `APP_NAME`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain`
- `DB_CONNECTION=mysql`
- `DB_HOST=mysql`
- `DB_PORT=3306`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=database`
- `MAIL_*`
- `SUPER_ADMIN_*`

4. Start the stack:

```bash
docker compose up -d --build
```

5. Inspect startup state:

```bash
docker compose ps
docker compose logs -f app
```

The `app` container runs migrations automatically on boot when `RUN_MIGRATIONS=true`.

## GitHub Actions secrets

Configure these repository secrets for automated deployment:

- `SSH_HOST`
- `SSH_PORT`
- `SSH_USER`
- `SSH_PRIVATE_KEY`
- `DEPLOY_PATH`
- `GHCR_USERNAME` optional
- `GHCR_TOKEN` optional

The deploy workflow pushes two images to GHCR:

- `ghcr.io/mabonax/ab4ir_erp-app`
- `ghcr.io/mabonax/ab4ir_erp-web`

On each push to `main`, the workflow:

1. Builds and pushes both images.
2. SSHes into the Ubuntu server.
3. Pulls the latest git state.
4. Pulls the exact image tags for that commit.
5. Recreates the containers.
6. Runs `php artisan migrate --force`.
7. Rebuilds Laravel caches with `php artisan optimize`.

## Manual update flow on the server

If you want to deploy manually without the SSH stage:

```bash
cd /var/www/ab4irerp
git pull --ff-only origin main
docker compose pull
docker compose up -d --remove-orphans
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize
```

## Notes

- The CI workflow validates PHP, Node, asset build, and the Docker build targets.
- The web container serves `storage/app/public` through the shared Docker volume.
- If you terminate TLS upstream, keep `APP_URL` on the final HTTPS URL so cookies and generated URLs stay correct.
