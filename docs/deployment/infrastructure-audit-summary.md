# Infrastructure Audit Summary

## Core findings

### 1. Build determinism was weak

The previous Docker build split Node and PHP too aggressively. Vite triggered Laravel Wayfinder generation, but the frontend stage had no PHP runtime or application bootstrap available.

### 2. Runtime startup was mutating deployment state

The `app` container previously ran migrations and `php artisan optimize` during ordinary startup. That made restarts behave like deployments.

### 3. Host bootstrap was under-specified

Ubuntu server setup was too manual. Docker apt repository drift, broken `docker.list`, and `podman-docker` conflicts were not handled directly by the repo.

### 4. Git operations were not server-safe

Documentation still implied HTTPS cloning, which breaks unattended pulls now that GitHub password auth is removed.

### 5. Health signaling was incomplete

MySQL and Redis had checks, but app/worker/scheduler readiness was largely inferred instead of tested.

### 6. Production defaults were too permissive

- `.env.example` still carried starter/default values
- MySQL and Redis were published to the host
- Nginx ran with minimal hardening
- placeholder passwords were easy to miss

## Security findings

### High

- Default or placeholder passwords in env scaffolding could leak into production if not caught.
- HTTPS Git remotes on the server would fail unattended deploys and encourage unsafe ad hoc credential handling.

### Medium

- Automatic migrations during normal container startup increased recovery risk during restarts.
- Database and Redis ports were exposed by default even though they are internal services.
- Nginx did not explicitly harden hidden-file access and storage-path PHP execution.

### Low

- Logging was not yet fully standardized around container-native stdout/stderr.
- Worker and scheduler health behavior was not explicitly visible to Compose.

## Refactor outcomes

- deterministic multi-stage build with Laravel-aware asset generation
- explicit deployment script for pull, migrate, optimize, and worker restart
- Ubuntu bootstrap and host validation scripts
- SSH-first GitHub workflow
- healthchecks across all core services
- non-root runtime processes where practical
- stronger env validation and safer `.env.example`
