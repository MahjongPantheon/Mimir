hooks:
	cp -pnrf bin/hooks/* .git/hooks
	chmod a+x .git/hooks/*

deps: hooks
	php bin/composer.phar install

lint:
	php vendor/bin/phpcs --config-set default_standard PSR2
	php vendor/bin/phpcs --config-set show_warnings 0
	php vendor/bin/phpcs src tests www

unit:
	php bin/unit.php

check: lint unit

autofix:
	php vendor/bin/phpcbf --config-set default_standard PSR2
	php vendor/bin/phpcbf --config-set show_warnings 0
	php vendor/bin/phpcbf src tests www

dev:
	echo "Running dev server on port 8000..."
	cd www && php -S localhost:8000

req:
	php bin/rpc.php "$(filter-out $@,$(MAKECMDGOALS))"

init_sqlite_nointeractive:
	echo '' > data/db.sqlite
	cat src/fixtures/init/ansi.sql \
		| sed 's/--[ ]*IF EXISTS/   IF EXISTS/g' \
		| grep -v 'primary key' \
		| sed 's/^.*-- datewrap://' \
		| sed 's/integer,[ ]*--[ ]*serial/integer PRIMARY KEY AUTOINCREMENT,/g' \
		| sqlite3 data/db.sqlite

init_sqlite:
	@echo "This will delete and recreate data/db.sqlite! Press Enter to confirm or Ctrl+C to abort"
	@read
	make init_sqlite_nointeractive

init_mysql:
	@echo "SET FOREIGN_KEY_CHECKS=0;"
	@cat src/fixtures/init/ansi.sql \
		| tr "\"" "\`" \
		| sed 's/--[ ]*IF EXISTS/   IF EXISTS/g' \
		| sed 's/integer,[ ]*--[ ]*serial/integer AUTO_INCREMENT,/g' \
		| sed 's/timestamp/datetime/g'
	@echo "SET FOREIGN_KEY_CHECKS=1;"

init_pgsql:
	@cat src/fixtures/init/ansi.sql \
		| sed 's/--[ ]*IF EXISTS/   IF EXISTS/g' \
		| sed 's/integer,[ ]*--[ ]*serial/serial,/g'
