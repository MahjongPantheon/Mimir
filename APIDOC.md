
Api methods
-----------

### getGameConfig
Parameters:
* **$eventId** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getRatingTable
Parameters:
* **$eventId** (_integer_) 
* **$orderBy** (_string_) either 'name', 'rating' or 'avg_place'
* **$order** (_string_) either 'asc' or 'desc'

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getLastGames
Parameters:
* **$eventId** (_integer_) 
* **$limit** (_integer_) 
* **$offset** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getPlayerStats
Parameters:
* **$playerId** (_int_) player to get stats for
* **$eventId** (_int_) event to get stats for

Returns: _array_ of statistics

Exceptions:
* _EntityNotFoundException_ 

### addRound
Parameters:
* **$gameHashcode** (_string_) Hashcode of game
* **$roundData** (_array_) Structure of round data

Returns: _bool_ Success?

Exceptions:
* _DatabaseException_ 
* _BadActionException_ 

### addOnlineReplay
Parameters:
* **$eventId** (_int_) 
* **$link** (_string_) 

Returns: _bool_ 

Exceptions:
* _InvalidParametersException_ 
* _ParseException_ 

### getAllPlayers
Parameters:
* **$eventId** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### startGame
Parameters:
* **$eventId** (_int_) Event this session belongs to
* **$players** (_array_) Player id list

Returns: _string_ Hashcode of started game

Exceptions:
* _InvalidUserException_ 
* _DatabaseException_ 

### endGame
Parameters:

Returns: _bool_ Success?

Exceptions:
* _DatabaseException_ 
* _BadActionException_ 

### addTextLog
Parameters:
* **$eventId** (_int_) 
* **$text** (_string_) 

Returns: _bool_ 

Exceptions:
* _InvalidParametersException_ 
* _ParseException_ 

### registerPlayer
Parameters:
* **$eventId** (_integer_) 
* **$playerId** (_integer_) 

Returns: _bool_ 

Exceptions:
* _InvalidParametersException_ 

### addPlayer
Parameters:
* **$ident** (_string_) oauth ident, if any
* **$alias** (_string_) textlog alias for quicker enter
* **$displayName** (_string_) how to display user in stats
* **$tenhouId** (_string_) tenhou username

Returns: _int_ user id

Exceptions:
* _MalformedPayloadException_ 

### updatePlayer
Parameters:
* **$id** (_int_) user to update
* **$ident** (_string_) oauth ident, if any
* **$alias** (_string_) textlog alias for quicker enter
* **$displayName** (_string_) how to display user in stats
* **$tenhouId** (_string_) tenhou username

Returns: _int_ user id

Exceptions:
* _EntityNotFoundException_ 
* _MalformedPayloadException_ 

### generateSortition
Parameters:

