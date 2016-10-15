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

require_once __DIR__ . '/../Model.php';
require_once __DIR__ . '/../primitives/Player.php';
require_once __DIR__ . '/../primitives/Event.php';
require_once __DIR__ . '/../primitives/Round.php';
require_once __DIR__ . '/../primitives/Session.php';
require_once __DIR__ . '/../exceptions/InvalidParameters.php';
require_once __DIR__ . '/../exceptions/InvalidUser.php';
require_once __DIR__ . '/../exceptions/BadAction.php';
require_once __DIR__ . '/../exceptions/Database.php';

/**
 * Class SessionModel
 *
 * Domain model for high-level logic
 * @package Riichi
 */
class InteractiveSessionModel extends Model
{
    /**
     * @param $eventId
     * @throws InvalidParametersException
     * @return array
     */
    public function getGameConfig($eventId)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $rules = $event[0]->getRuleset();
        return [
            'allowedYaku' => $rules->allowedYaku(),
            'startPoints' => $rules->startPoints(),
            'withKazoe' => $rules->withKazoe(),
            'withKiriageMangan' => $rules->withKiriageMangan(),
            'withAbortives' => $rules->withAbortives(),
            'withNagashiMangan' => $rules->withNagashiMangan()
        ];
    }

    /**
     * @throws InvalidParametersException
     * @throws InvalidUserException
     * @throws DatabaseException
     * @param int $eventId
     * @param int[] $playerIds
     * @param string $replayHash
     * @param string $origLink
     * @return string
     */
    public function startGame($eventId, $playerIds, $replayHash = null, $origLink = null)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        if (!is_array($playerIds)) {
            throw new InvalidParametersException('Players list is not array');
        }

        $players = PlayerPrimitive::findById($this->_db, $playerIds);
        if (count($players) !== 4) {
            throw new InvalidUserException('Some players do not exist in DB, check ids');
        }

        $newSession = new SessionPrimitive($this->_db);
        $success = $newSession
            ->setEvent($event[0])
            ->setPlayers($players)
            ->setStatus('inprogress')
            ->setReplayHash($replayHash)
            ->setOrigLink($origLink)
            ->save();
        if (!$success) {
            throw new DatabaseException('Couldn\'t save session data to DB!');
        }

        return $newSession->getRepresentationalHash();
    }

    /**
     * This method is strictly for premature end of session (timeout, etc)
     * Normal end should happen when final round is added.
     *
     * @param $gameHash
     * @return bool
     */
    public function endGame($gameHash)
    {
        $session = $this->_findGame($gameHash, 'inprogress');
        return $session->finish();
    }

    /**
     * @param $gameHashcode string Hashcode of game
     * @param $roundData array Structure of round data
     * @throws InvalidParametersException
     * @throws BadActionException
     * @return bool Success?
     */
    public function addRound($gameHashcode, $roundData)
    {
        $session = $this->_findGame($gameHashcode, 'inprogress');
        $newRound = RoundPrimitive::createFromData($this->_db, $session, $roundData);

        return $newRound->save()
            && $session->updateCurrentState($newRound);
    }

    /**
     * @param $gameHash
     * @param $withStatus
     * @return SessionPrimitive
     * @throws InvalidParametersException
     * @throws BadActionException
     */
    protected function _findGame($gameHash, $withStatus)
    {
        $game = SessionPrimitive::findByRepresentationalHash($this->_db, [$gameHash]);
        if (empty($game)) {
            throw new InvalidParametersException("Couldn't find session in DB");
        }

        if ($game[0]->getStatus() !== $withStatus) {
            throw new BadActionException("This action is not supported over the game in current status");
        }

        return $game[0];
    }
}
