test:
	./vendor/bin/phpunit tests/

lint:
	./vendor/bin/php-cs-fixer fix --dry-run --diff .

lint-fix:
	./vendor/bin/php-cs-fixer fix .

ci: lint test