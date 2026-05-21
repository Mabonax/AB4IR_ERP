# Ubuntu Server Deployment

This deployment pack is now built for deterministic Ubuntu Server rollouts using:

- Docker Engine
- Docker Compose
- Nginx
- PHP-FPM
- Redis
- MySQL
- queue workers
- scheduler
- GHCR images
- Tailscale-reachable host networking

The key hardening change is that normal container startup is no longer treated as a deployment event. Runtime containers validate and start. Deployment scripts handle git pull, image pull, migrations, optimize, and queue restart explicitly.

## Runtime topology

- `app`: Laravel PHP-FPM runtime
- `web`: Nginx reverse proxy serving `public/`
- `worker`: queue worker runtime
- `scheduler`: Laravel scheduler runtime
- `mysql`: MySQL 8.4
- `redis`: Redis 7.4
- `backup`: one-shot ops profile for manual DB dumps

Laravel runtime containers share `app-storage` so uploaded files, cache-backed artifacts, and backup output survive container replacement.

## 1. Bootstrap the Ubuntu host

Use the bundled bootstrap script:

```bash
sudo bash scripts/deployment/bootstrap-ubuntu.sh
```

What it fixes:

- removes conflicting `podman-docker` and old Docker apt packages
- recreates the Docker apt repository using the correct Ubuntu codename
- installs Docker Engine, Buildx, and Compose plugin
- starts and enables Docker

After that, add the deployment user to the docker group:

```bash
sudo usermod -aG docker <deploy-user>
newgrp docker
```

Verify host readiness:

```bash
bash scripts/deployment/check-host-access.sh
```

If you see `permission denied while trying to connect to the docker API`, the user session does not yet have Docker group access. Re-login and rerun the check.

## 2. Clone with GitHub SSH, not HTTPS

Do not use HTTPS cloning for the server checkout.

Recommended:

```bash
git clone git@github.com:Mabonax/AB4IR_ERP.git /var/www/ab4irerp
cd /var/www/ab4irerp
```

If the repo already exists and was cloned with HTTPS, fix it:

```bash
git remote set-url origin git@github.com:Mabonax/AB4IR_ERP.git
```

Verify SSH access:

```bash
bash scripts/deployment/check-github-ssh.sh
```

Recommended server strategy:

- create a dedicated deploy key on the Ubuntu host
- add the public key to the GitHub repository as a read-only deploy key
- keep GHCR credentials separate from Git credentials

## 3. Prepare the environment

Copy the environment file:

```bash
cp .env.example .env
```

Minimum production values:

- `APP_NAME=AB4IRERP`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-final-hostname`
- `APP_KEY=...`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `REDIS_PASSWORD`
- `MAIL_*`
- `SUPER_ADMIN_*`
- `STAFF_USER_DEFAULT_PASSWORD`

Generate the application key once:

```bash
docker compose run --rm app php artisan key:generate --force
```

Run deployment validation before first boot:

```bash
docker compose run --rm app php artisan system:validate-deployment --strict
```

What this validates:

- required env keys exist
- `APP_DEBUG` is not enabled in production
- public registration remains disabled
- placeholder passwords are not still present
- production mailer is not left on `log`

## 4. First deployment

Build or pull the images, then bring the stack up:

```bash
docker compose pull
docker compose up -d
docker compose exec -T app php artisan system:validate-deployment --services --strict
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize
docker compose exec -T worker php artisan queue:restart || true
```

Seed the permanent bootstrap super-admin:

```bash
docker compose exec -T app php artisan db:seed --class=SuperAdminUserSeeder --force
```

Health validation:

```bash
docker compose ps
docker compose logs -f app
curl -fsS http://127.0.0.1:${APP_PORT:-8080}/up
```

## 5. Standard release flow

The repo now ships with a server-safe deployment script:

```bash
bash scripts/deployment/deploy-release.sh
```

What it does:

1. verifies Docker daemon access
2. verifies GitHub SSH remote and SSH auth
3. optionally logs into GHCR
4. pulls `main`
5. pulls images
6. recreates the app/runtime stack
7. validates DB and Redis connectivity
8. runs migrations
9. rebuilds Laravel caches
10. gracefully restarts workers

When using SHA-pinned GHCR images:

```bash
APP_RUNTIME_IMAGE=ghcr.io/mabonax/ab4ir_erp-app:sha-<commit-sha> \
WEB_RUNTIME_IMAGE=ghcr.io/mabonax/ab4ir_erp-web:sha-<commit-sha> \
GHCR_USERNAME=<ghcr-user> \
GHCR_TOKEN=<ghcr-token> \
bash scripts/deployment/deploy-release.sh
```

## 6. Rollback strategy

Rollback is image-tag based. Reuse a previously known-good SHA tag:

```bash
APP_RUNTIME_IMAGE=ghcr.io/mabonax/ab4ir_erp-app:sha-<old-sha> \
WEB_RUNTIME_IMAGE=ghcr.io/mabonax/ab4ir_erp-web:sha-<old-sha> \
GHCR_USERNAME=<ghcr-user> \
GHCR_TOKEN=<ghcr-token> \
bash scripts/deployment/deploy-release.sh
```

This keeps rollback deterministic because the image and code revision are both pinned to the same release commit.

## 7. GitHub Actions and GHCR

Required repository secrets:

- `SSH_HOST`
- `SSH_PORT`
- `SSH_USER`
- `SSH_PRIVATE_KEY`
- `DEPLOY_PATH`
- `GHCR_USERNAME`
- `GHCR_TOKEN`

The deploy workflow now:

1. builds `app` and `web` images with Buildx cache
2. publishes `latest` and `sha-<commit>` tags to GHCR
3. SSHes into the Ubuntu host
4. runs `scripts/deployment/deploy-release.sh` on the server

## 8. Healthchecks

Implemented runtime checks:

- `app`: PHP-FPM ping over FastCGI
- `web`: HTTP `GET /up`
- `worker`: verifies `queue:work` process is alive
- `scheduler`: verifies a fresh heartbeat file
- `mysql`: `mysqladmin ping`
- `redis`: `redis-cli ping`

These are used by Compose for dependency readiness and by operators for faster fault isolation.

## 9. Logging and restarts

Production defaults now favor container-native logs:

- Laravel logs to `stderr`
- PHP-FPM worker output is forwarded to container logs
- Nginx access logs go to stdout and errors go to stderr
- Compose uses `json-file` rotation with:
  - `max-size=10m`
  - `max-file=5`

Service restart behavior:

- runtime services use `restart: unless-stopped`
- backup runs as an explicit one-shot ops profile
- worker and scheduler use `init: true` and grace periods for cleaner shutdown

## 10. Backups

Automatic DB dumps are still scheduled through Laravel Scheduler when:

```env
BACKUP_ENABLED=true
```

Manual backup:

```bash
docker compose exec -T app php artisan system:backup-database --prune
```

One-shot backup container:

```bash
docker compose --profile ops run --rm backup
```

Retention is controlled by:

- `BACKUP_RETENTION_DAYS`
- `BACKUP_DISK`
- `BACKUP_PATH`

## 11. Security notes

Hardening changes in this refactor:

- `.env` is never copied into the image
- PHP app processes run as `www-data`
- Nginx runs as non-root on port `8080`
- MySQL and Redis are no longer published to the host by default
- Nginx blocks hidden files and PHP execution from storage paths
- placeholder passwords in `.env.example` now fail deployment validation in strict mode
- public registration remains disabled by default

Remaining operator responsibility:

- store real secrets outside Git
- rotate the bootstrap super-admin password after first use if needed
- keep TLS termination in front of the web container
- restrict server SSH access to known operators / Tailscale policy

## 12. Validation commands

Useful operational checks:

```bash
docker compose config
docker compose ps
docker compose logs -f app
docker compose logs -f worker
docker compose exec -T app php artisan system:validate-deployment --services --strict
docker compose exec -T app php artisan about
curl -fsS http://127.0.0.1:${APP_PORT:-8080}/up
```

## 13. Why this architecture is safer

- Build-time Laravel dependencies are now available during the Vite build, so Wayfinder no longer breaks image builds.
- Runtime containers no longer rebuild the world on every restart; deploy steps are explicit and repeatable.
- Healthchecks give Compose real readiness signals instead of blind process startup.
- Non-root runtime processes reduce the blast radius of a compromised service.
- SSH-first git access avoids failed HTTPS auth during unattended server pulls.
- Host bootstrap scripts remove the Docker apt drift and podman conflicts that caused the original Ubuntu setup failures.
