Riichi mahjong game server: API
===============================

A backend part for riichi mahjong games and tournaments assistance system.

[![Build Status](https://travis-ci.org/Furiten/riichi-api.svg?branch=master)](https://travis-ci.org/Furiten/riichi-api)

What is it for?
---------------

We needed a simple but stateful API for gaming assistance in aspects like:
- Storing game log for an offline game.
- Automated scoring and game log sanity checking (for offline games).
- Fetching game logs from online mahjong services.
- Keeping players' gameplay history and make some interesting statistics for their games.
- Automating creating and performing of online and offline tournaments.
- And many more...

What's inside?
--------------

- API uses JSON-RPC to communicate with clients.
- Supported RDBMS are sqlite, mysql and pgsql, others may work too because of PDO under hood, but only after some little coding (you'll see :) ). Anyway, other RDBMS are not tested at all, use them at your own risk.
- PHP v5.5+ is required to run the API on your own server.

Developer information
---------------------

We accept any help with developing, testing and improving our system, so please feel free to create issues or send pull requests for missing functionality.

To start developing the project, make sure you have installed a database server of your choice, PHP v5.5 or higher and appropriate PDO module.
You also will need standard `make` utility to use following shortcuts.
- Run `make deps` to install dependencies.
- Run php dev server on port 8000: `make dev`
- Use `make req METHOD_NAME [space-separated method args]` to test API methods. Port for the API is hardcoded inside, change it if you run dev server on different port.
- Use `make unit` to run unit tests and `make lint` to check code style.
- Use `make autofix` to fix all codestyle problems, that can be fixed automatically.
- Remember to use PSR2 coding standards when adding php code.
- The [DB schema](src/fixtures/init/ansi.sql) should be written in ANSI SQL92 and should pass any compliance tests. If any of DB-specific things are required, use post-processing tools (see Makefile sections for examples of generating sqlite/mysql/pgsql specific schemas).

To generate or recreate sqlite db, run `make init_sqlite`.
To generate sql dump for mysql or pgsql, run `make init_mysql` or `make init_pgsql` - this will echo dump to stdout, so you can redirect the stream into the file you want.

Distribution info
-----------------

This software is distributed under GNU GPL v3 license. Latest version of the software can be downloaded at [its primary repo](https://github.com/Furiten/riichi-api). It's also the one and only place to submit your issues and pull requests :)