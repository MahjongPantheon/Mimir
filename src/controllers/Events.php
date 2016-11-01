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

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../Controller.php';

class EventsController extends Controller
{
    /**
     * Get event rules configuration
     *
     * @param integer $eventId
     * @throws InvalidParametersException
     * @return array
     */
    public function getGameConfig($eventId)
    {
        $this->_log->addInfo('Getting config for event id# ' . $eventId);

        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $rules = $event[0]->getRuleset();
        $data = [
            'allowedYaku'       => $rules->allowedYaku(),
            'startPoints'       => $rules->startPoints(),
            'withKazoe'         => $rules->withKazoe(),
            'withKiriageMangan' => $rules->withKiriageMangan(),
            'withAbortives'     => $rules->withAbortives(),
            'withNagashiMangan' => $rules->withNagashiMangan()
        ];

        $this->_log->addInfo('Successfully received config for event id# ' . $eventId);
        return $data;
    }

    /**
     * Get rating table for event
     *
     * @param integer $eventId
     * @param string $orderBy  either 'name', 'rating' or 'avg_place'
     * @param string $order  either 'asc' or 'desc'
     * @throws InvalidParametersException
     * @return array
     */
    public function getRatingTable($eventId, $orderBy, $order)
    {
        $this->_log->addInfo('Getting rating table for event id# ' . $eventId);

        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $table = (new EventModel($this->_db))
            ->getRatingTable($event[0], $orderBy, $order);

        $this->_log->addInfo('Successfully received rating table for event id# ' . $eventId);
        return $table;
    }

    /**
     * Get last games sorted by date (latest go first)
     *
     * @param integer $eventId
     * @param integer $limit
     * @param integer $offset
     * @throws InvalidParametersException
     * @return array
     */
    public function getLastGames($eventId, $limit, $offset)
    {
        $this->_log->addInfo('Getting games list [' . $limit . '/' . $offset . '] for event id# ' . $eventId);

        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $table = (new EventModel($this->_db))
            ->getLastFinishedGames($event[0], $limit, $offset);

        $this->_log->addInfo('Successfully got games list [' . $limit . '/' . $offset . '] for event id# ' . $eventId);
        return $table;
    }
}
