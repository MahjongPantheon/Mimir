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
     * Get event rules configuration
     *
     * @param $eventId
     * @return InvalidParametersException
     */
    public function getGameConfig($eventId)
    {
        $this->_log->addInfo('Getting config for event id# ' . $eventId);
        $data = $this->_sessionModel->getGameConfig($eventId);
        $this->_log->addInfo('Successfully received config for event id# ' . $eventId);
        return $data;
    }

    /**
     * Start new game and return its hash
     *
     * @param int $eventId Event this session belongs to
     * @param array $players Player id list
     * @throws InvalidUserException
     * @throws DatabaseException
     * @return string Hashcode of started game
     */
    public function start($eventId, $players)
    {
        $this->_log->addInfo('Starting game with players id# ' . implode(',', $players));
        $gameHash = $this->_sessionModel->startGame($eventId, $players);
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
        $result = $this->_sessionModel->addRound($gameHashcode, $roundData);
        $this->_log->addInfo(($result ? 'Successfully added' : 'Failed to add') . ' new round to game # ' . $gameHashcode);
        return $result;
    }
}
