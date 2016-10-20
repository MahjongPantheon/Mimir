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

require_once __DIR__ . '/../helpers/textLog/Parser.php';

class TextmodeSessionModel extends Model
{
    /**
     * @param $eventId int
     * @param $gameLog string
     * @return bool
     * @throws InvalidParametersException
     * @throws MalformedPayloadException
     * @throws ParseException
     */
    public function addGame($eventId, $gameLog)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $parser = new TextlogParser($this->_db);
        $session = (new SessionPrimitive($this->_db))
            ->setEvent($event[0])
            ->setStatus('inprogress');

        $originalScore = $parser->parseToSession($session, $gameLog);
        $success = true;
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
}
