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
namespace Riichi;

require_once __DIR__ . '/../models/InteractiveSession.php';
require_once __DIR__ . '/../models/TextmodeSession.php';
require_once __DIR__ . '/../models/OnlineSession.php';
require_once __DIR__ . '/../Controller.php';

class GamesController extends Controller
{
    // INTERACTIVE MODE

    /**
     * Start new interactive game and return its hash
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
        $gameHash = (new InteractiveSessionModel($this->_db))->startGame($eventId, $players);
        $this->_log->addInfo('Successfully started game with players id# ' . implode(',', $players));
        return $gameHash;
    }

    /**
     * Explicitly force end of interactive game
     *
     * @param $gameHashcode string Hashcode of game
     * @throws DatabaseException
     * @throws BadActionException
     * @return bool Success?
     */
    public function end($gameHashcode)
    {
        $this->_log->addInfo('Finishing game # ' . $gameHashcode);
        $result = (new InteractiveSessionModel($this->_db))->endGame($gameHashcode);
        $this->_log->addInfo(($result ? 'Successfully finished' : 'Failed to finish') . ' game # ' . $gameHashcode);
        return $result;
    }

    /**
     * Add new round to interactive game
     *
     * @param string $gameHashcode Hashcode of game
     * @param array $roundData Structure of round data
     * @param boolean $dry Dry run (without saving to db)
     * @throws DatabaseException
     * @throws BadActionException
     * @return bool|array Success|Results of dry run
     */
    public function addRound($gameHashcode, $roundData, $dry = false)
    {
        $this->_log->addInfo('Adding new round to game # ' . $gameHashcode);
        $result = (new InteractiveSessionModel($this->_db))->addRound($gameHashcode, $roundData, $dry);
        $this->_log->addInfo(($result ? 'Successfully added' : 'Failed to add') . ' new round to game # ' . $gameHashcode);
        return $result;
    }

    /**
     * Get session overview
     * [
     *      id => sessionId,
     *      players => [ ..[
     *          id => playerId,
     *          display_name,
     *          ident
     *      ].. ],
     *      state => [
     *          dealer => playerId,
     *          round => int,
     *          riichi => [ ..playerId.. ],
     *          honba => int,
     *          scores => [ ..int.. ]
     *      ]
     * ]
     *
     * @param int $sessionId
     * @throws EntityNotFoundException
     * @throws InvalidParametersException
     * @return array
     */
    public function getSessionOverview($sessionId)
    {
        $this->_log->addInfo('Getting session overview for game # ' . $sessionId);
        $session = SessionPrimitive::findById($this->_db, [$sessionId]);
        if (empty($session)) {
            throw new InvalidParametersException("Couldn't find session in DB");
        }

        $result = [
            'id'    => $session[0]->getId(),
            'players' => array_map(function (PlayerPrimitive $player) {
                return [
                    'id' => $player->getId(),
                    'display_name' => $player->getDisplayName(),
                    'ident' => $player->getIdent()
                ];
            }, $session[0]->getPlayers()),

            'state' => [
                'dealer'    => $session[0]->getCurrentState()->getCurrentDealer(),
                'round'     => $session[0]->getCurrentState()->getRound(),
                'riichi'    => $session[0]->getCurrentState()->getRiichiBets(),
                'honba'     => $session[0]->getCurrentState()->getHonba(),
                'scores'    => $session[0]->getCurrentState()->getScores()
            ]
        ];

        $this->_log->addInfo('Successfully got session overview for game # ' . $sessionId);
        return $result;
    }

    // TEXT LOG MODE

    /**
     * Add textual log for whole game
     *
     * @param int $eventId
     * @param string $text
     * @return bool
     * @throws InvalidParametersException
     * @throws ParseException
     */
    public function addTextLog($eventId, $text)
    {
        $this->_log->addInfo('Saving new game for event id# ' . $eventId);
        $success = (new TextmodeSessionModel($this->_db))->addGame($eventId, $text);
        $this->_log->addInfo('Successfully saved game for event id# ' . $eventId);
        return $success;
    }

    // ONLINE REPLAY MODE

    /**
     * Add online replay
     *
     * @param int $eventId
     * @param string $link
     * @return bool
     * @throws InvalidParametersException
     * @throws ParseException
     */
    public function addOnlineReplay($eventId, $link)
    {
        $this->_log->addInfo('Saving new online game for event id# ' . $eventId);
        $success = (new OnlineSessionModel($this->_db))->addGame($eventId, $link);
        $this->_log->addInfo('Successfully saved online game for event id# ' . $eventId);
        return $success;
    }
}
