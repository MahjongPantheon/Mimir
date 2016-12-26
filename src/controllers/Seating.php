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

require_once __DIR__ . '/../Controller.php';
require_once __DIR__ . '/../helpers/Seating.php';

class SeatingController extends Controller
{
    /**
     * Generate new seating
     *
     * @param int $eventId
     * @param int $groupsCount
     * @param int $seed
     * @throws InvalidParametersException
     * @return array
     */
    public function generate($eventId, $groupsCount, $seed)
    {
        list ($playersMap, $tables) = $this->_getData($eventId);
        $seating = Seating::generateTables($playersMap, $tables, $groupsCount, $seed);
        $intersections = Seating::makeIntersectionsTable($seating, $tables);

        return [
            'seed' => $seed,
            'seating' => array_map(function ($id, $score) {
                return [
                    'id' => $id,
                    'score' => $score
                ];
            }, array_keys($seating), array_values($seating)),
            'intersections' => $intersections
        ];
    }

    public function approve($eventId, $seed)
    {
        list ($playersMap, $tables) = $this->_getData($eventId);
    }

    protected function _getData($eventId)
    {
        $histories = PlayerHistoryPrimitive::findLastByEvent($this->_db, $eventId);
        if (empty($histories)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }

        $playersMap = [];
        foreach ($histories as $h) {
            $playersMap[$h->getPlayerId()] = $h->getRating();
        }

        $seatingInfo = SessionPrimitive::getPlayersSeatingInEvent($this->_db, $eventId);
        $tables = array_chunk(array_map(function ($el) {
            return $el['user_id'];
        }, $seatingInfo), 4);

        return [$playersMap, $tables];
    }
}
