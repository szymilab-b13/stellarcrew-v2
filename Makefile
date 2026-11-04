.DEFAULT_GOAL := help

DOCKER_COMPOSE ?= docker compose
BACKEND_DIR    := backend
FRONTEND_DIR   := frontend

.PHONY: help install up down restart logs ps shell shell-db shell-redis \
        backend-install backend-shell backend-test backend-lint \
        frontend-install frontend-dev frontend-test frontend-lint \
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

backend-install: ## composer install (uses composer:2 image until backend container is wired in BL-SETUP-003)
	docker run --rm --user "$(shell id -u):$(shell id -g)" \
		-e COMPOSER_HOME=/tmp/composer \
		-v "$(PWD)/$(BACKEND_DIR):/app" \
		-w /app \
		composer:2 install --ignore-platform-reqs

backend-shell: shell ## Alias

backend-test: ## phpunit (TBD: BL-SETUP-009)
	@echo "[backend-test] TBD: phpunit — added in BL-SETUP-009."

backend-lint: ## phpstan + deptrac + cs-fixer (TBD: BL-SETUP-007)
	@echo "[backend-lint] TBD: phpstan + deptrac + php-cs-fixer — added in BL-SETUP-007."

## ---- Frontend (React/Vite) — TBD after BL-SETUP-004/005 ------------------

frontend-install: ## pnpm install (TBD: BL-SETUP-004)
	@echo "[frontend-install] TBD: pnpm install — added in BL-SETUP-004."

frontend-dev: ## pnpm dev (TBD: BL-SETUP-004)
	@echo "[frontend-dev] TBD: pnpm dev — added in BL-SETUP-004."

frontend-test: ## vitest (TBD: BL-SETUP-009)
	@echo "[frontend-test] TBD: vitest — added in BL-SETUP-009."

frontend-lint: ## eslint + prettier + tsc (TBD: BL-SETUP-008)
	@echo "[frontend-lint] TBD: eslint + prettier + tsc — added in BL-SETUP-008."

## ---- Aggregates -----------------------------------------------------------

migrate: ## Run DB migrations (TBD: BL-CORE-001+)
	@echo "[migrate] TBD: doctrine:migrations:migrate — added once backend is up."

seed: ## Seed initial data (TBD: BL-CORE-002 install CLI)
	@echo "[seed] TBD: install wizard / fixtures — added in BL-CORE-002."

test: backend-test frontend-test ## Run all tests (backend + frontend)

lint: backend-lint frontend-lint ## Run all linters

build: ## Build production bundles (TBD: BL-SETUP-004/005)
	@echo "[build] TBD: frontend production build — added in BL-SETUP-005."

clean: ## Stop containers + remove volumes (DESTRUCTIVE)
	$(DOCKER_COMPOSE) down -v
	@echo "[clean] All containers and volumes removed."

## ---- Smoke ----------------------------------------------------------------

smoke: ## Validate docker-compose + composer.json
	$(DOCKER_COMPOSE) config --quiet
	@echo "[smoke] docker-compose.yml is valid."
	docker run --rm -v "$(PWD)/$(BACKEND_DIR):/app" -w /app composer:2 validate --no-check-publish
	@echo "[smoke] backend/composer.json is valid."
