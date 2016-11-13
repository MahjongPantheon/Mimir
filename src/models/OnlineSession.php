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

require_once __DIR__ . '/../Model.php';
require_once __DIR__ . '/../helpers/onlineLog/Parser.php';
require_once __DIR__ . '/../helpers/onlineLog/Downloader.php';
require_once __DIR__ . '/../primitives/Event.php';
require_once __DIR__ . '/../primitives/Session.php';
require_once __DIR__ . '/../exceptions/InvalidParameters.php';
require_once __DIR__ . '/../exceptions/Parser.php';

class OnlineSessionModel extends Model
{
    /**
     * @param $eventId int
     * @param $gameLink string
     * @return bool
     * @throws InvalidParametersException
     * @throws MalformedPayloadException
     * @throws ParseException
     */
    public function addGame($eventId, $gameLink)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $this->_checkGameExpired($gameLink, $event[0]->getRuleset());

        $downloader = new Downloader($this->_db);
        $replay = $downloader->getReplay($gameLink);

        $parser = new OnlineParser($this->_db);
        $session = (new SessionPrimitive($this->_db))
            ->setEvent($event[0])
            ->setStatus('inprogress');

        list($success, $originalScore/*, $debug*/) = $parser->parseToSession($session, $replay);
        $success = $success && $session->save();
        $success = $success && $session->finish();

        $calculatedScore = $session->getCurrentState()->getScores();
        if (array_diff($calculatedScore, $originalScore) !== []
            || array_diff($originalScore, $calculatedScore) !== []) {
            throw new ParseException("Calculated scores do not match with given ones: " . PHP_EOL
                . print_r($originalScore, 1) . PHP_EOL
                . print_r($calculatedScore, 1), 225);
        }

        return $success;
    }

    /**
     * Check if game is not older than some amount of time defined in ruleset
     *
     * @param $gameLink
     * @param Ruleset $rules
     * @throws ParseException
     */
    protected function _checkGameExpired($gameLink, Ruleset $rules)
    {
        if (!$rules->gameExpirationTime()) {
            return;
        }

        $regex = '#(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})(?<hour>\d{2})gm#is';
        $matches = [];
        if (preg_match($regex, $gameLink, $matches)) {
            $date = mktime($matches['hour'], 0, 0, $matches['month'], $matches['day'], $matches['year']);
            if (time() - $date < $rules->gameExpirationTime() * 60 * 60) {
                return;
            }
        }

        throw new ParseException('Replay is older than ' . $rules->gameExpirationTime() . ' hours (within JST timezone), so it can\'t be accepted.');
    }
}
