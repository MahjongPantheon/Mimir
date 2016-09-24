<?php
/*  Riichi mahjong API game server
 *  Copyright (C) 2016  heilage and others
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
namespace Riichi;

require_once __DIR__ . '/../helpers/Users.php';
require_once __DIR__ . '/../exceptions/InvalidUser.php';
require_once __DIR__ . '/../exceptions/Database.php';
require_once __DIR__ . '/../exceptions/BadAction.php';
require_once __DIR__ . '/../Controller.php';

class Games extends Controller
{
    /**
     * Start new game and return its hash
     *
     * @param $players array Player id list
     * @throws InvalidUserException
     * @throws DatabaseException
     * @return string Hashcode of started game
     */
    public function start($players)
    {
//        return \Idiorm\ORM::getConfig();

        $invalid = UsersHelper::valid($this->_db, $players);
        if ($invalid) {
            throw new InvalidUserException($invalid);
        }

        $newGame = $this->_db->table('session')->create();
        $newGame
            ->set('event_id', 0)
            ->set('replay_hash', null)
            ->set('orig_link', null)
            ->set('play_date', date('Y-m-d H:i:s'))
            ->set('players', implode(',', array_map('intval', $players)))
            ->set('state', 'inprogress');
        $gameHash = sha1($newGame->get('players') . $newGame->get('play_date'));
        $success = $newGame
            ->set('representational_hash', $gameHash)
            ->save();
        if (!$success) {
            throw new DatabaseException("Couldn't create session record in DB");
        }

        return $gameHash;
    }

    /**
     * @param $gameHashcode string Hashcode of game
     * @throws DatabaseException
     * @throws BadActionException
     * @return bool Success?
     */
    public function end($gameHashcode)
    {
        $game = $this->_db->table('session')->where('representational_hash', $gameHashcode)->findOne();
        if (!$game) {
            throw new DatabaseException("Couldn't find session in DB");
        }

        if ($game->get('state') !== 'inprogress') {
            throw new BadActionException("Attempted to end game that is not in progress");
        }

        return !!$game->set('state', 'finished')->save();
    }

    /**
     * @param $gameHashcode string Hashcode of game
     * @param $roundData array Structure of round data
     * @return bool Success?
     */
    public function addRound($gameHashcode, $roundData)
    {

    }
}
