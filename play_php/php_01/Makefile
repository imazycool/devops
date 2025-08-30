# Makefile (buf-based PHP stub generation)

.PHONY: help proto-php proto-php-clean composer-dev up down logs restart

help:
	@echo "Available targets:"
	@echo "  proto-php        Generate PHP stubs into app/php_client via buf"
	@echo "  proto-php-clean  Clean and regenerate PHP stubs"
	@echo "  composer-dev     Install PHP deps (host) with composer:2"
	@echo "  up               docker compose up -d"
	@echo "  down             docker compose down"
	@echo "  logs             Tail all service logs"
	@echo "  restart          down then up"

proto-php:
	docker run --rm -v "$$(pwd)/app":/work -w /work bufbuild/buf:1.45.0 generate

proto-php-clean:
	rm -rf app/php_client
	docker run --rm -v "$$(pwd)/app":/work -w /work bufbuild/buf:1.45.0 generate

composer-dev:
	docker run --rm -v "$$(pwd)/app":/app -w /app composer:2 sh -lc 'composer install --no-dev --prefer-dist --no-interaction --no-progress && composer dump-autoload -o'

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f

restart:
	docker compose down
	docker compose up -d