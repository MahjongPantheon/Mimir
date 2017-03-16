## Mimir: game storage

![Mimir](www/mimirhires.png?raw=true "Mimir")

**Mimir** is a backend for [Tyr]() and [Rheda](), which provides storage of game information for japanese (riichi) mahjong sessions. 

[![Build Status](https://travis-ci.org/MahjongPantheon/Mimir.svg?branch=master)](https://travis-ci.org/MahjongPantheon/Mimir)

**Mimir** is a part of [Pantheon](https://github.com/MahjongPantheon) system.

### Features

We needed a simple but stateful API for gaming assistance in aspects like:
- Storing game log for an offline game.
- Automated scoring and game log sanity checking (for offline games).
- Fetching game logs from online mahjong services.
- Keeping players' gameplay history and make some interesting statistics for their games.
- Automating creating and performing of online and offline tournaments.
- And many more...

### What's inside?

- Mimir uses JSON-RPC to communicate with clients.
- Supported RDBMS are sqlite, mysql (v5.5+) and pgsql (v9.5+), others may work too because of PDO under hood, but only after some little coding (you'll see :) ). Anyway, other RDBMS are not tested at all, use them at your own risk.
- PHP v5.5+ is required to run the API on your own server.
- [Api doc here](APIDOC.md)

### Installation gotchas

- PHP v5.6.x comes with `always_populate_raw_post_data = 0` in default php.ini, and this breaks JSON reply validity, if errors output is not disabled (you should disable it on production anyway! But it will flood your log files with crap :( ). When using this PHP version, you should set `always_populate_raw_post_data = -1` in your ini file.

### Developer information

We accept any help with developing, testing and improving our system, so please feel free to create issues or send pull requests for missing functionality.

To start developing the project, make sure you have installed a database server of your choice, PHP v5.5 or higher and appropriate PDO module.
You also will need standard `make` utility to use following shortcuts.
- Run `make deps` to install dependencies.
- Run php dev server on port 8000: `make dev`
- Use `make req METHOD_NAME [space-separated method args]` to test API methods. Port for the API is hardcoded inside, change it if you run dev server on different port.
- Use `make unit` to run unit tests and `make lint` to check code style.
- Use `make autofix` to fix all codestyle problems, that can be fixed automatically.
- Use `make apidoc` to regenerate api methods documentation file.
- Remember to use PSR2 coding standards when adding php code.
- The [DB schema](src/fixtures/init/ansi.sql) should be written in ANSI SQL92 and should pass any compliance tests. If any of DB-specific things are required, use post-processing tools (see Makefile sections for examples of generating sqlite/mysql/pgsql specific schemas).

To generate or recreate sqlite db, run `make init_sqlite`.
To generate sql dump for mysql or pgsql, run `make init_mysql` or `make init_pgsql` - this will echo dump to stdout, so you can redirect the stream into the file you want.

### Legend

**Mimir** is a figure in Norse mythology renowned for his knowledge and wisdom. See wikipedia for details :)

