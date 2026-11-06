.DEFAULT_GOAL := help

DOCKER_COMPOSE ?= docker compose
BACKEND_DIR    := backend
FRONTEND_DIR   := frontend

.PHONY: help install up down restart logs ps shell shell-db shell-redis \
        backend-install backend-shell backend-test backend-lint backend-lint-fix \
        backend-phpstan backend-deptrac backend-cs-fixer backend-cs-fixer-fix \
        backend-rector backend-rector-fix \
        frontend-install frontend-build frontend-dev frontend-test \
        frontend-lint frontend-lint-fix frontend-eslint frontend-prettier frontend-typecheck \
        migrate seed test lint build clean smoke

help: ## Show available targets
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9_-]+:.*?## / {printf "  \033[36m%-24s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## ---- Lifecycle ------------------------------------------------------------

install: ## First-run bootstrap (TBD: depends on backend/frontend setup — BL-SETUP-002..005)
	@echo "[install] Infrastructure only for now. Backend/frontend install comes in BL-SETUP-002..005."
	cp -n .env.example .env || true
	$(DOCKER_COMPOSE) pull
	$(DOCKER_COMPOSE) up -d
	@echo "[install] Done. Postgres on $${POSTGRES_PORT:-5432}, Redis on $${REDIS_PORT:-6379}, Mailpit UI on $${MAILPIT_WEB_PORT:-8025}."

up: ## Start all containers
	$(DOCKER_COMPOSE) up -d

down: ## Stop and remove containers
	$(DOCKER_COMPOSE) down

restart: ## Restart all containers
	$(DOCKER_COMPOSE) restart

logs: ## Tail logs from all containers
	$(DOCKER_COMPOSE) logs -f --tail=100

ps: ## Show running containers
	$(DOCKER_COMPOSE) ps

## ---- Shells ---------------------------------------------------------------

shell: ## Open shell in backend container
	$(DOCKER_COMPOSE) exec backend sh

shell-db: ## Open psql shell
	$(DOCKER_COMPOSE) exec postgres psql -U $${POSTGRES_USER:-app} -d $${POSTGRES_DB:-stellarcrew}

shell-redis: ## Open redis-cli
	$(DOCKER_COMPOSE) exec redis redis-cli

## ---- Backend (Symfony) — TBD after BL-SETUP-002/003 ----------------------

jwt-keys: ## (Re)generate the JWT keypair under backend/config/jwt (uses backend container, see ADR-BE-004)
	$(DOCKER_COMPOSE) exec -T backend bin/console lexik:jwt:generate-keypair --overwrite

backend-install: ## composer install (uses composer:2 image until backend container is wired in BL-SETUP-003)
	docker run --rm --user "$(shell id -u):$(shell id -g)" \
		-e COMPOSER_HOME=/tmp/composer \
		-v "$(PWD)/$(BACKEND_DIR):/app" \
		-w /app \
		composer:2 install --ignore-platform-reqs

backend-shell: shell ## Alias

backend-test: ## phpunit (requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/phpunit

backend-phpstan: ## Static analysis (phpstan level 8, requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend bin/console cache:warmup --env=dev --quiet
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/phpstan analyse --memory-limit=1G --no-progress

backend-deptrac: ## Enforce modular bundle boundaries (requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/deptrac analyse --no-progress --fail-on-uncovered

backend-cs-fixer: ## Check PSR-12 + PHP84Migration (dry-run, requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

backend-cs-fixer-fix: ## Apply php-cs-fixer changes (requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/php-cs-fixer fix

backend-rector: ## Rector dry-run (requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/rector process --dry-run --no-progress-bar

backend-rector-fix: ## Apply rector refactors (requires backend container up)
	$(DOCKER_COMPOSE) exec -T backend vendor/bin/rector process --no-progress-bar

backend-lint: backend-phpstan backend-deptrac backend-cs-fixer backend-rector ## Full backend static analysis pipeline

backend-lint-fix: backend-cs-fixer-fix backend-rector-fix ## Auto-fix style + refactors (then rerun backend-lint)

## ---- Frontend (React/Vite) -----------------------------------------------

frontend-install: ## pnpm install (uses node:22-alpine image until frontend container is wired in BL-SETUP-005)
	docker run --rm \
		-v "$(PWD)/$(FRONTEND_DIR):/app" \
		-w /app \
		node:22-alpine sh -c "corepack enable && corepack prepare pnpm@9.12.3 --activate && pnpm install --frozen-lockfile && chown -R $(shell id -u):$(shell id -g) /app"

frontend-build: ## Build production bundle (uses node:22-alpine image)
	docker run --rm \
		-v "$(PWD)/$(FRONTEND_DIR):/app" \
		-w /app \
		node:22-alpine sh -c "corepack enable && pnpm build && chown -R $(shell id -u):$(shell id -g) /app"

frontend-dev: ## Start Vite dev server inside the frontend container (follows logs)
	$(DOCKER_COMPOSE) up -d frontend
	$(DOCKER_COMPOSE) logs -f --tail=100 frontend

frontend-test: ## vitest (requires frontend container up)
	$(DOCKER_COMPOSE) exec -T frontend pnpm test

frontend-eslint: ## eslint check (requires frontend container up)
	$(DOCKER_COMPOSE) exec -T frontend pnpm lint

frontend-prettier: ## prettier --check (requires frontend container up)
	$(DOCKER_COMPOSE) exec -T frontend pnpm format:check

frontend-typecheck: ## tsc --noEmit (requires frontend container up)
	$(DOCKER_COMPOSE) exec -T frontend pnpm typecheck

frontend-lint: frontend-eslint frontend-prettier frontend-typecheck ## Full frontend static analysis pipeline

frontend-lint-fix: ## Auto-fix eslint + prettier (requires frontend container up)
	$(DOCKER_COMPOSE) exec -T frontend pnpm lint:fix
	$(DOCKER_COMPOSE) exec -T frontend pnpm format

## ---- Aggregates -----------------------------------------------------------

migrate: ## Run DB migrations (TBD: BL-CORE-001+)
	@echo "[migrate] TBD: doctrine:migrations:migrate — added once backend is up."

seed: ## Seed initial data (TBD: BL-CORE-002 install CLI)
	@echo "[seed] TBD: install wizard / fixtures — added in BL-CORE-002."

test: backend-test frontend-test ## Run all tests (backend + frontend)

lint: backend-lint frontend-lint ## Run all linters

build: frontend-build ## Build production bundles (frontend bundle; backend has no build step)

clean: ## Stop containers + remove volumes (DESTRUCTIVE)
	$(DOCKER_COMPOSE) down -v
	@echo "[clean] All containers and volumes removed."

## ---- Smoke ----------------------------------------------------------------

smoke: ## Validate docker-compose + composer.json + frontend build
	$(DOCKER_COMPOSE) config --quiet
	@echo "[smoke] docker-compose.yml is valid."
	docker run --rm -v "$(PWD)/$(BACKEND_DIR):/app" -w /app composer:2 validate --no-check-publish
	@echo "[smoke] backend/composer.json is valid."
	$(MAKE) frontend-build
	@echo "[smoke] frontend builds successfully."
