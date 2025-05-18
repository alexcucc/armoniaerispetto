.PHONY: migrate

init:

start-infra:
	@echo "Starting the infrastructure..."
	@docker-compose up -d

stop-infra:
	@echo "Stopping the infrastructure..."
	@docker-compose down

restart-infra:
	@echo "Restarting the infrastructure..."
	@docker-compose down
	@docker-compose up -d

migrate:
    @echo "Applying migrations with Phinx..."
    @vendor/bin/phinx migrate --configuration=phinx.yml --environment=development

start:
	@echo "Starting the database..."
	@docker-compose up -d