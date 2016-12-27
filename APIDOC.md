
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

### getCurrentGames
Parameters:
* **$playerId** (_int_) 
* **$eventId** (_int_) 

Returns: _array_ of session data

Exceptions:
* _AuthFailedException_ 

### getAllPlayers
Parameters:
* **$eventId** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getPlayerIdByIdent
Parameters:
* **$playerIdent** (_string_) unique identifying string

Returns: _int_ player id

Exceptions:
* _EntityNotFoundException_ 

### getTimerState
Parameters:
* **$eventId** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getGameOverview
Parameters:
* **$sessionHashcode** (_string_) 

Returns: _array_ 

Exceptions:
* _EntityNotFoundException_ 
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
* **$dry** (_boolean_) Dry run (without saving to db)

Returns: _bool|array_ Success|Results of dry run

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

### getLastResults
Parameters:
* **$playerId** (_int_) 
* **$eventId** (_int_) 

Returns: _array|null_ 

Exceptions:
* _EntityNotFoundException_ 

### getGameConfigT
Parameters:

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getTimerStateT
Parameters:

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getAllPlayersT
Parameters:

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### getCurrentGamesT
Parameters:

Returns: _array_ of session data

Exceptions:
* _AuthFailedException_ 

### getLastResultsT
Parameters:

Returns: _array|null_ 

Exceptions:
* _EntityNotFoundException_ 

### getPlayerT
Parameters:

Returns: _array_ 

Exceptions:
* _EntityNotFoundException_ 

### startGameT
Parameters:
* **$players** (_array_) Player id list

Returns: _string_ Hashcode of started game

Exceptions:
* _InvalidUserException_ 
* _DatabaseException_ 

### createEvent
Parameters:
* **$title** (_string_) 
* **$description** (_string_) 
* **$type** (_string_) either 'online' or 'offline'
* **$ruleset** (_string_) one of possible ruleset names ('ema', 'jpmlA', 'tenhounet', or any other supported by system)
* **$gameDuration** (_int_) duration of game in this event

Returns: _int_ 

Exceptions:
* _BadActionException_ 

### getTablesState
Parameters:
* **$eventId** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

### startTimer
Parameters:
* **$eventId** (_integer_) 

Returns: _bool_ 

Exceptions:
* _InvalidParametersException_ 

### registerPlayer
Parameters:
* **$pin** (_integer_) 

Returns: _string_ Auth token

Exceptions:
* _InvalidParametersException_ 

### enrollPlayer
Parameters:
* **$playerId** (_integer_) 
* **$eventId** (_integer_) 

Returns: _string_ Secret pin code for self-registration

Exceptions:
* _AuthFailedException_ 
* _BadActionException_ 
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

### addPlayer
Parameters:
* **$ident** (_string_) oauth ident, if any
* **$alias** (_string_) textlog alias for quicker enter
* **$displayName** (_string_) how to display user in stats
* **$tenhouId** (_string_) tenhou username

Returns: _int_ user id

Exceptions:
* _MalformedPayloadException_ 
* _InvalidUserException_ 

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

### getPlayer
Parameters:
* **$id** (_int_) 

Returns: _array_ 

Exceptions:
* _EntityNotFoundException_ 

### generateSeating
Parameters:
* **$eventId** (_int_) 
* **$groupsCount** (_int_) 
* **$seed** (_int_) 

Returns: _array_ 

Exceptions:
* _AuthFailedException_ 
* _InvalidParametersException_ 

### startGamesWithSeating
Parameters:
* **$eventId** (_int_) 
* **$groupsCount** (_int_) 
* **$seed** (_int_) 

Returns: _bool_ 

Exceptions:
* _InvalidParametersException_ 
* _AuthFailedException_ 

### getCurrentSeating
Parameters:
* **$eventId** (_integer_) 

Returns: _array_ 

Exceptions:
* _InvalidParametersException_ 

