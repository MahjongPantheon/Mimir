<?php
/*  Riichi mahjong API game server
 *  Copyright (C) 2016  o.klimenko aka ctizen
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Configure entry points for api methods.
 *
 * The following will cause execution of Implementation_class::someMethodAlias
 * when user requests someMethod.
 *
 * [
 *      'someMethod' => ['Implementation_class', 'someMethodAlias']
 *      ...
 * ]
 *
 */
return [
    // client
    'getGameConfig'      => ['EventsController', 'getGameConfig'],
    'getRatingTable'     => ['EventsController', 'getRatingTable'],
    'getLastGames'       => ['EventsController', 'getLastGames'],
    'getCurrentGames'    => ['PlayersController', 'getCurrentSessions'],
    'getAllPlayers'      => ['EventsController', 'getAllRegisteredPlayers'],
    'getPlayerIdByIdent' => ['PlayersController', 'getIdByIdent'],
    'getTimerState'      => ['EventsController', 'getTimerState'],
    'getGameOverview'    => ['GamesController', 'getSessionOverview'],
    'getPlayerStats'     => ['PlayersController', 'getStats'],
    'addRound'           => ['GamesController', 'addRound'],
    'addOnlineReplay'    => ['GamesController', 'addOnlineReplay'],
    'getLastResults'     => ['PlayersController', 'getLastResults'],

    // admin
    'createEvent'       => ['EventsController', 'createEvent'],
    'startTimer'        => ['EventsController', 'startTimer'],
    'registerPlayer'    => ['EventsController', 'registerPlayer'],
    'startGame'         => ['GamesController', 'start'],
    'endGame'           => ['GamesController', 'end'],
    'addTextLog'        => ['GamesController', 'addTextLog'],
    'addPlayer'         => ['PlayersController', 'add'],
    'updatePlayer'      => ['PlayersController', 'update'],
    'getPlayer'         => ['PlayersController', 'get'],
    'generateSortition' => ['SortitionController', 'generate']
];
