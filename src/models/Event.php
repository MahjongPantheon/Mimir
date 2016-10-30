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

class EventModel extends Model
{
    public function getRatingTable(EventPrimitive $event, $orderBy, $order)
    {
        if (!in_array($order, ['asc', 'desc'])) {
            throw new InvalidParametersException("Parameter order should be either 'asc' or 'desc'");
        }

        $playersHistoryItems = PlayerHistoryPrimitive::findLastByEvent($this->_db, $event->getId());
        $playerItems = $this->_getPlayers($playersHistoryItems);
        $this->_sortItems($orderBy, $playerItems, $playersHistoryItems);

        if ($order === 'desc') {
            $playersHistoryItems = array_reverse($playersHistoryItems);
        }

        return array_map(function (PlayerHistoryPrimitive $el) use (&$playerItems) {
            return [
                'id'            => (int)$el->getPlayerId(),
                'name'          => $playerItems[$el->getPlayerId()]->getDisplayName(),
                'rating'        => (float)$el->getRating(),
                'avg_place'     => round($el->getAvgPlace(), 5),
                'games_played'  => (int)$el->getGamesPlayed()
            ];
        }, $playersHistoryItems);
    }

    /**
     * @param PlayerHistoryPrimitive[] $playersHistoryItems
     * @return PlayerPrimitive[]
     */
    protected function _getPlayers($playersHistoryItems)
    {
        $ids = array_map(function (PlayerHistoryPrimitive $el) {
            return $el->getPlayerId();
        }, $playersHistoryItems);
        $players = PlayerPrimitive::findById($this->_db, $ids);

        $result = [];
        foreach ($players as $p) {
            $result[$p->getId()] = $p;
        }

        return $result;
    }

    /**
     * @param $orderBy
     * @param PlayerPrimitive[] $playerItems
     * @param PlayerHistoryPrimitive[] $playersHistoryItems
     * @throws InvalidParametersException
     */
    protected function _sortItems($orderBy, &$playerItems, &$playersHistoryItems)
    {
        switch ($orderBy) {
            case 'name':
                usort($playersHistoryItems, function (
                    PlayerHistoryPrimitive $el1,
                    PlayerHistoryPrimitive $el2
                ) use (&$playerItems) {
                    return strcmp(
                        $playerItems[$el1->getPlayerId()]->getDisplayName(),
                        $playerItems[$el2->getPlayerId()]->getDisplayName()
                    );
                });
                break;
            case 'rating':
                usort($playersHistoryItems, function (
                    PlayerHistoryPrimitive $el1,
                    PlayerHistoryPrimitive $el2
                ) {
                    if ($el1->getRating() == $el2->getRating()) {
                        return $el2->getAvgPlace() - $el1->getAvgPlace(); // lower avg place is better, so invert
                    }
                    return $el1->getRating() - $el2->getRating(); // higher rating is better
                });
                break;
            case 'avg_place':
                usort($playersHistoryItems, function (
                    PlayerHistoryPrimitive $el1,
                    PlayerHistoryPrimitive $el2
                ) {
                    if (abs($el1->getAvgPlace() - $el2->getAvgPlace()) < 0.00001) { // floats need epsilon
                        return $el2->getRating() - $el1->getRating(); // lower rating is worse, so invert
                    }
                    if ($el1->getAvgPlace() - $el2->getAvgPlace() < 0) { // higher avg place is worse
                        return -1; // usort casts return result to int, so pass explicit int here.
                    } else {
                        return 1;
                    }
                });
                break;
            default:
                throw new InvalidParametersException("Parameter orderBy should be either 'name', 'rating' or 'avg_place'");
        }
    }
}
