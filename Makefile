# Dyno — climbing training PWA. Docker/Sail workflow (no local PHP required).
# First run: make setup

SAIL := ./vendor/bin/sail
COMPOSER_IMAGE := docker run --rm --user "$$(id -u):$$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$(CURDIR):/app" -w /app composer

.PHONY: setup up down build test fresh admin logs

setup: ## First-time setup: deps, boot Sail, migrate, build assets
	@[ -d vendor ] || $(COMPOSER_IMAGE) install --ignore-platform-reqs --no-interaction
	@[ -f .env ] || cp .env.example .env
	$(SAIL) up -d
	@grep -q '^APP_KEY=.\+' .env || $(SAIL) artisan key:generate --no-interaction
	@echo "Waiting for MySQL…"
	@until $(SAIL) artisan migrate --force >/dev/null 2>&1; do sleep 3; done
	$(SAIL) npm install
	$(SAIL) npm run build
	@echo ""
	@echo "Ready → http://127.0.0.1:8091   Admin → http://127.0.0.1:8091/admin"
	@echo "Create the first admin: make admin"

up: ## Start containers
	$(SAIL) up -d

down: ## Stop containers
	$(SAIL) down

build: ## Rebuild frontend assets
	$(SAIL) npm run build

test: ## Run the PHPUnit suite
	$(SAIL) artisan test

fresh: ## Drop + re-migrate the database
	$(SAIL) artisan migrate:fresh

admin: ## Create (or promote) an admin — invite-only bootstrap account
	$(SAIL) artisan app:create-admin

logs: ## Tail application logs
	$(SAIL) logs -f
