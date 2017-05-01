SQLITE_FILE ?= data/db.sqlite

hooks:
	cp -pnrf bin/hooks/* .git/hooks
	chmod a+x .git/hooks/*

deps: hooks
	php bin/composer.phar install

lint:
	php vendor/bin/phpcs --config-set default_standard PSR2
	php vendor/bin/phpcs --config-set show_warnings 0
	php vendor/bin/phpcs src tests www

mount_mem_data:
	# on mac, there is no tmpfs
	@if [ `uname` != "Darwin" ]; then \
		if [ -z "`mount | grep tests/data | grep tmpfs`" ]; then \
			sudo mount tmpfs -t tmpfs tests/data/; \
		fi \
	fi

# Mount tests folder for local testing
unit: mount_mem_data
	php bin/unit.php

# For CI do not mount - travis is ok with its ssd's
unit_ci:
	php bin/unit.php

check: lint unit
check_ci: lint unit_ci

apidoc:
	php bin/routeDoc.php > APIDOC.md

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
	@echo '' > $(SQLITE_FILE)
	@cat src/fixtures/init/sqlite.sql \
		| sqlite3 $(SQLITE_FILE)

init_sqlite:
	@echo "This will delete and recreate $(SQLITE_FILE)! Press Enter to confirm or Ctrl+C to abort"
	@read
	make init_sqlite_nointeractive

init_mysql:
	@cat src/fixtures/init/mysql.sql

init_pgsql:
	@cat src/fixtures/init/postgres.sql
