Riichi mahjong game server: API
===============================

A backend part for riichi mahjong games and tournaments assistance system.

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
- MySQL (Percona) is supported as a database backend to store all games and tournaments info and statistics.
- PHP v5.5+ is required to run the API on your own server.

Developer information
---------------------

We accept any help with developing, testing and improving our system, so please feel free to create issues or send pull requests for missing functionality.

To start developing the project, make sure you have installed PHP v5.5 or higher and MySQL compatible database (MariaDB or Percona, latter one is preferred).
- Run `php bin/composer.phar install` to install dependencies.
- Run php dev server: `php -S localhost:8000`
- Use `php bin/rpc.php METHOD_NAME [space-separated method args]` to test API methods. Port for the API is hardcoded inside, change it if you run dev server on different port.
