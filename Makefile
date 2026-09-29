-include .env

DC      = docker compose
PHP     = $(DC) exec -T php
CONSOLE = $(PHP) bin/console
PORT    = $(or $(NGINX_PORT),8080)

.PHONY: up down install db test match match-cli

up: ## Build and start containers
	$(DC) up -d --build --wait

down: ## Stop containers
	$(DC) down

install: ## Install PHP dependencies
	$(PHP) composer install --no-interaction

db: ## Run migrations and load fixtures (dev database)
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

test: ## Run PHPUnit (tests/bootstrap.php rebuilds the app_test database)
	$(PHP) bin/phpunit

match: ## Query the endpoint: make match NAME="Staszic" [CITY="Warszawa"]
	@curl -sG "http://localhost:$(PORT)/api/schools/match" \
		--data-urlencode "name=$(NAME)" $(if $(CITY),--data-urlencode "city=$(CITY)") ; echo

match-cli: ## Run the console command: make match-cli NAME="Staszic" [CITY="Warszawa"]
	@$(CONSOLE) app:school:match "$(NAME)" $(if $(CITY),--city="$(CITY)")
