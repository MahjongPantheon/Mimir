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

use Monolog\Logger;

require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../Controller.php';

class GamesController extends Controller
{
    /**
     * @var SessionModel
     */
    protected $_sessionModel;
    public function __construct(Db $db, Logger $log)
    {
        parent::__construct($db, $log);
        $this->_sessionModel = new SessionModel($this->_db);
    }

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

        // TODO: correct event id
        $gameHash = $this->_sessionModel->startGame(0, $players);

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
        $result = $this->_sessionModel->endGame($gameHashcode);
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
}
