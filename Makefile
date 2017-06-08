test:
	./vendor/bin/phpunit

lint:
	./vendor/bin/php-cs-fixer fix --dry-run --diff .

lint-fix:
	./vendor/bin/php-cs-fixer fix .

ci: lint test