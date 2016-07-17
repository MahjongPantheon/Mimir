hooks:
	cp -pnr bin/hooks/* .git/hooks
	chmod a+x .git/hooks/*

deps: hooks
	php bin/composer.phar install

lint:
	php vendor/bin/phpcs --config-set default_standard PSR2
	php vendor/bin/phpcs --config-set show_warnings 0
	php vendor/bin/phpcs src tests index.php

unit:
	php bin/unit.php

check: lint unit

dev:
	echo "Running dev server on port 8000..."
	php -S localhost:8000

req:
	php bin/rpc.php $(filter-out $@,$(MAKECMDGOALS))
