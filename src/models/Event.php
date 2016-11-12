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
require_once __DIR__ . '/../primitives/Event.php';
require_once __DIR__ . '/../primitives/Session.php';
require_once __DIR__ . '/../primitives/SessionResults.php';
require_once __DIR__ . '/../primitives/Player.php';
require_once __DIR__ . '/../primitives/PlayerHistory.php';
require_once __DIR__ . '/../primitives/Round.php';
require_once __DIR__ . '/../exceptions/InvalidParameters.php';

class EventModel extends Model
{
    // ------ Last games related -------

    /**
     * @param EventPrimitive $event
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function getLastFinishedGames(EventPrimitive $event, $limit, $offset)
    {
        $games = SessionPrimitive::findByEventAndStatus(
            $this->_db,
            $event->getId(),
            'finished',
            $offset,
            $limit
        );

        $sessionIds = array_map(function (SessionPrimitive $el) {
            return $el->getId();
        }, $games);

        /** @var SessionResultsPrimitive[][] $sessionResults */
        $sessionResults = $this->_getSessionResults($sessionIds); // 1st level: session id, 2nd level: player id

        /** @var RoundPrimitive[][] $rounds */
        $rounds = $this->_getRounds($sessionIds); // 1st level: session id, 2nd level: numeric index with no meaning

        $result = [
            'games' => [],
            'players' => $this->_getPlayersOfGames($games)
        ];

        foreach ($games as $session) {
            $result['games'][$session->getId()] = [
                'date' => $session->getPlayDate(),
                'replay_link' => $session->getOrigLink(),
                'players' => array_map('intval', $session->getPlayersIds()),
                'final_results' => $this->_arrayMapPreserveKeys(function (SessionResultsPrimitive $el) {
                    return [
                        'score'         => (int) $el->getScore(),
                        'rating_delta'  => (float) $el->getRatingDelta(),
                        'place'         => (int) $el->getPlace()
                    ];
                }, $sessionResults[$session->getId()]),
                'rounds' => array_map([$this, '_formatRound'], $rounds[$session->getId()]),
            ];
        }

        return $result;
    }

    /**
     * @param $sessionIds
     * @return RoundPrimitive[][]
     */
    protected function _getRounds($sessionIds)
    {
        $rounds = RoundPrimitive::findBySessionIds($this->_db, $sessionIds);

        $result = [];
        foreach ($rounds as $item) {
            if (empty($result[$item->getSessionId()])) {
                $result[$item->getSessionId()] = [];
            }
            $result[$item->getSessionId()] []= $item;
        }

        return $result;
    }

    /**
     * @param $sessionIds
     * @return SessionResultsPrimitive[][]
     */
    protected function _getSessionResults($sessionIds)
    {
        $results = SessionResultsPrimitive::findBySessionId($this->_db, $sessionIds);

        $result = [];
        foreach ($results as $item) {
            if (empty($result[$item->getSessionId()])) {
                $result[$item->getSessionId()] = [];
            }
            $result[$item->getSessionId()][$item->getPlayerId()] = $item;
        }

        return $result;
    }

    /**
     * @param SessionPrimitive[] $games
     * @return array
     */
    protected function _getPlayersOfGames($games)
    {
        $players = PlayerPrimitive::findById($this->_db, array_reduce($games, function ($acc, SessionPrimitive $el) {
            return array_merge($acc, $el->getPlayersIds());
        }, []));

        $result = [];
        foreach ($players as $player) {
            $result[$player->getId()] = [
                'id'            => (int) $player->getId(),
                'display_name'  => $player->getDisplayName(),
                'tenhou_id'     => $player->getTenhouId()
            ];
        }

        return $result;
    }

    protected function _formatRound(RoundPrimitive $round)
    {
        switch ($round->getOutcome()) {
            case 'ron':
                return [
                    'round_index'   => (int) $round->getRoundIndex(),
                    'outcome'       => $round->getOutcome(),
                    'winner_id'     => (int) $round->getWinnerId(),
                    'loser_id'      => (int) $round->getLoserId(),
                    'han'           => (int) $round->getHan(),
                    'fu'            => (int) $round->getFu(),
                    'yaku'          => $round->getYaku(),
                    'riichi_bets'   => implode(',', $round->getRiichiIds()),
                    'dora'          => (int) $round->getDora(),
                    'uradora'       => (int) $round->getUradora(), // TODO: not sure if we really need these guys
                    'kandora'       => (int) $round->getKandora(),
                    'kanuradora'    => (int) $round->getKanuradora()
                ];
            case 'multiron':
                /** @var MultiRoundPrimitive $mRound */
                $mRound = $round;
                $rounds = $mRound->rounds();

                return [
                    'round_index'   => (int) $rounds[0]->getRoundIndex(),
                    'outcome'       => $mRound->getOutcome(),
                    'loser_id'      => (int) $mRound->getLoserId(),
                    'multi_ron'     => (int) $rounds[0]->getMultiRon(),
                    'wins'          => array_map(function (RoundPrimitive $round) {
                        return [
                            'winner_id'     => (int) $round->getWinnerId(),
                            'han'           => (int) $round->getHan(),
                            'fu'            => (int) $round->getFu(),
                            'yaku'          => $round->getYaku(),
                            'riichi_bets'   => implode(',', $round->getRiichiIds()),
                            'dora'          => (int) $round->getDora(),
                            'uradora'       => (int) $round->getUradora(), // TODO: not sure if we really need these guys
                            'kandora'       => (int) $round->getKandora(),
                            'kanuradora'    => (int) $round->getKanuradora()
                        ];
                    }, $rounds)
                ];
            case 'tsumo':
                return [
                    'round_index'   => (int) $round->getRoundIndex(),
                    'outcome'       => $round->getOutcome(),
                    'winner_id'     => (int) $round->getWinnerId(),
                    'han'           => (int) $round->getHan(),
                    'fu'            => (int) $round->getFu(),
                    'yaku'          => $round->getYaku(),
                    'riichi_bets'   => implode(',', $round->getRiichiIds()),
                    'dora'          => (int) $round->getDora(),
                    'uradora'       => (int) $round->getUradora(), // TODO: not sure if we really need these guys
                    'kandora'       => (int) $round->getKandora(),
                    'kanuradora'    => (int) $round->getKanuradora()
                ];
            case 'draw':
                return [
                    'round_index'   => (int) $round->getRoundIndex(),
                    'outcome'       => $round->getOutcome(),
                    'riichi_bets'   => implode(',', $round->getRiichiIds()),
                    'tempai'        => implode(',', $round->getTempaiIds())
                ];
            case 'abort':
                return [
                    'round_index'   => $round->getRoundIndex(),
                    'outcome'       => $round->getOutcome(),
                    'riichi_bets'   => implode(',', $round->getRiichiIds())
                ];
            case 'chombo':
                return [
                    'round_index'   => (int) $round->getRoundIndex(),
                    'outcome'       => $round->getOutcome(),
                    'loser_id'      => (int) $round->getLoserId()
                ];
            default:
                throw new DatabaseException('Wrong outcome detected! This should not happen - DB corrupted?');
        }
    }

    protected function _arrayMapPreserveKeys(callable $cb, $array)
    {
        return array_combine(array_keys($array), array_map($cb, array_values($array)));
    }

    // ------ Rating table related -------

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

        // TODO: среднеквадратичное отклонение

        return array_map(function (PlayerHistoryPrimitive $el) use (&$playerItems, $event) {
            return [
                'id'            => (int)$el->getPlayerId(),
                'display_name'  => $playerItems[$el->getPlayerId()]->getDisplayName(),
                'rating'        => (float)$el->getRating(),
                'winner_zone'   => ($el->getRating() >= $event->getRuleset()->startRating()),
                'avg_place'     => round($el->getAvgPlace(), 4),
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
                    if (abs($el1->getAvgPlace() - $el2->getAvgPlace()) < 0.0001) { // floats need epsilon
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
