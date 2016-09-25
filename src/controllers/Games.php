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
require_once __DIR__ . '/../helpers/Rounds.php';
require_once __DIR__ . '/../exceptions/InvalidUser.php';
require_once __DIR__ . '/../exceptions/Database.php';
require_once __DIR__ . '/../exceptions/BadAction.php';
require_once __DIR__ . '/../Controller.php';

class GamesController extends Controller
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
        $this->_log->addInfo('Starting game with players id# ' . implode(',', $players));

        $invalid = UsersHelper::valid($this->_db, $players);
        if ($invalid) {
            throw new InvalidUserException($invalid);
        }

        $newGame = $this->_db->table('session')->create();
        $newGame->set([
            'event_id' =>       0,
            'replay_hash' =>    null,
            'orig_link' =>      null,
            'play_date' =>      date('Y-m-d H:i:s'),
            'players' =>        implode(',', array_map('intval', $players)),
            'state' =>          'inprogress'
        ]);
        $gameHash = sha1($newGame->get('players') . $newGame->get('play_date'));
        $success = $newGame
            ->set('representational_hash', $gameHash)
            ->save();
        if (!$success) {
            throw new DatabaseException("Couldn't create session record in DB");
        }

        $this->_log->addInfo('Successfully started game with players id# ' . implode(',', $players));
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
        $this->_log->addInfo('Finishing game # ' . $gameHashcode);
        $game = $this->_checkValidHashcode($gameHashcode);
        $result = !!$game->set('state', 'finished')->save();
        $this->_log->addInfo(($result ? 'Successfully finished' : 'Failed to finish') . ' game # ' . $gameHashcode);
        return $result;
    }

    /**
     * @param $gameHashcode string Hashcode of game
     * @param $roundData array Structure of round data
     * @throws DatabaseException
     * @throws BadActionException
     * @return bool Success?
     */
    public function addRound($gameHashcode, $roundData)
    {
        $this->_log->addInfo('Adding new round to game # ' . $gameHashcode);
        $game = $this->_checkValidHashcode($gameHashcode);
        RoundsHelper::checkRound($this->_db, $game, $roundData);
        $newRound = $this->_db->table('round')->create();
        $newRound->set( // Just set it, as we already checked its perfect validity.
            array_merge($roundData, [
                'session_id' => $game->get('id'),
                'event_id' =>   $game->get('event_id')
            ])
        );
        $result = $newRound->save();
        $this->_log->addInfo(($result ? 'Successfully added' : 'Failed to add') . ' new round to game # ' . $gameHashcode);
        return $result;
    }

    /**
     * Check that passed hashcode refers to valid existing game in progress
     *
     * @param $gameHashcode
     * @throws BadActionException
     * @throws DatabaseException
     * @return bool|\Idiorm\ORM
     */
    protected function _checkValidHashcode($gameHashcode)
    {
        $game = $this->_db->table('session')->where('representational_hash', $gameHashcode)->findOne();
        if (!$game) {
            throw new DatabaseException("Couldn't find session in DB");
        }

        if ($game->get('state') !== 'inprogress') {
            throw new BadActionException("Attempted to end game that is not in progress");
        }

        return $game;
    }
}
