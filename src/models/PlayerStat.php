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

class PlayerStatModel extends Model
{
    /**
     * Get stats for player in event
     * Now it costs 6 indexed queries to DB (should be fast),
     * but be careful about adding some other stats.
     *
     * @param $eventId
     * @param $playerId
     * @return array
     * @throws EntityNotFoundException
     */
    public function getStats($eventId, $playerId)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new EntityNotFoundException('Event id#' . $eventId . ' not found in DB');
        }
        $event = $event[0];

        $player = PlayerPrimitive::findById($this->_db, [$playerId]);
        if (empty($player)) {
            throw new EntityNotFoundException('Player id#' . $playerId . ' not found in DB');
        }
        $player = $player[0];

        $games = $this->_fetchGamesHistory($event, $player);
        $rounds = $this->_fetchRounds($games);

        $scoresAndPlayers = $this->_getScoreHistoryAndPlayers($playerId, $games);
        return [
            'rating_history'        => $this->_getRatingHistorySequence($event, $playerId, $games),
            'score_history'         => $scoresAndPlayers['scores'],
            'players_info'          => $scoresAndPlayers['players'],
            'places_summary'        => $this->_getPlacesSummary($playerId, $games),
            'total_played_games'    => count($games),
            'win_summary'           => $this->_getOutcomeSummary($playerId, $rounds),
            'hands_value_summary'   => $this->_getHanSummary($playerId, $rounds),
            'yaku_summary'          => $this->_getYakuSummary($playerId, $rounds),
        ];
    }

    /**
     * @param EventPrimitive $event
     * @param $playerId
     * @param $games
     * @return array
     */
    protected function _getRatingHistorySequence(EventPrimitive $event, $playerId, $games)
    {
        $rating = $event->getRuleset()->startRating();
        $ratingHistory = array_filter(
            array_map(
                function ($game) use ($rating, $playerId) {
                    /** @var $results SessionResultsPrimitive[] */
                    $results = $game['results'];
                    $rating += $results[$playerId]->getRatingDelta();
                    return $rating;
                },
                $games
            )
        );
        array_unshift($ratingHistory, $event->getRuleset()->startRating());
        return $ratingHistory;
    }

    /**
     * Scores and rating deltas of all players
     *
     * @param $playerId
     * @param $games
     * @return array
     */
    protected function _getScoreHistoryAndPlayers($playerId, $games)
    {
        $scoreHistory = array_map(function ($game) use ($playerId) {
            /** @var $results SessionResultsPrimitive[] */
            $results = $game['results'];

            return array_map(function (SessionResultsPrimitive $sr, $playerId) {
                return [
                    'player_id'     => (int)$playerId,
                    'score'         => (int)$sr->getScore(),
                    'rating_delta'  => (float)$sr->getRatingDelta(),
                    'place'         => (int)$sr->getPlace()
                ];
            }, array_values($results), array_keys($results));
        }, $games);

        return [
            'scores'    => $scoreHistory,
            'players'   => $this->_fetchPlayersInfo($scoreHistory)
        ];
    }

    /**
     * Fetch players info needed to display score history.
     * 1) get all ids,
     * 2) get all rows by single query
     *
     * @param $scoreHistory
     * @return array
     */
    protected function _fetchPlayersInfo($scoreHistory)
    {
        $playerIds = array_reduce($scoreHistory, function ($acc, $item) {
            return array_merge($acc, array_map(function ($playerInfo) {
                return $playerInfo['player_id'];
            }, $item));
        }, []);
        $players = array_map(function (PlayerPrimitive $p) {
            return [
                'id'            => (int)$p->getId(),
                'display_name'  => $p->getDisplayName(),
                'tenhou_id'     => $p->getTenhouId(),
            ];
        }, PlayerPrimitive::findById($this->_db, $playerIds));

        return $players;
    }

    /**
     * Get places stats for player
     *
     * @param $playerId
     * @param $games
     * @return mixed
     */
    protected function _getPlacesSummary($playerId, $games)
    {
        return array_reduce($games, function ($acc, $game) use ($playerId) {
            /** @var $results SessionResultsPrimitive[] */
            $results = $game['results'];

            $acc[$results[$playerId]->getPlace()] ++;
            return $acc;
        }, [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0
        ]);
    }

    /**
     * @param $playerId
     * @param RoundPrimitive[] $rounds
     * @return array
     */
    protected function _getOutcomeSummary($playerId, $rounds)
    {
        return array_reduce($rounds, function ($acc, RoundPrimitive $r) use ($playerId) {
            switch ($r->getOutcome()) {
                case 'ron':
                    if ($r->getLoserId() == $playerId) {
                        $acc['feed'] ++;
                    } else if ($r->getWinnerId() == $playerId) {
                        $acc['ron'] ++;
                    }
                    break;
                case 'tsumo':
                    if ($r->getWinnerId() == $playerId) {
                        $acc['tsumo'] ++;
                    } else {
                        $acc['tsumofeed'] ++;
                    }
                    break;
                case 'chombo':
                    if ($r->getLoserId() == $playerId) {
                        $acc['chombo'] ++;
                    }
                    break;
                default:
                    ;
            }
            return $acc;
        }, [
            'ron'       => 0,
            'tsumo'     => 0,
            'chombo'    => 0,
            'feed'      => 0,
            'tsumofeed' => 0
        ]);
    }

    /**
     * Get yaku summary stats for player
     *
     * @param $playerId
     * @param $rounds
     * @return array
     */
    protected function _getYakuSummary($playerId, $rounds)
    {
        return array_reduce($rounds, function ($acc, RoundPrimitive $r) use ($playerId) {
            if (($r->getOutcome() === 'ron' || $r->getOutcome() === 'tsumo') && $r->getWinnerId() == $playerId) {
                $acc = array_reduce(explode(',', $r->getYaku()), function ($acc, $yaku) {
                    if (empty($acc[$yaku])) {
                        $acc[$yaku] = 0;
                    }
                    $acc[$yaku] ++;
                    return $acc;
                }, $acc);
            }
            return $acc;
        }, []);
    }

    /**
     * Get hand value summary stats for player
     *
     * @param $playerId
     * @param $rounds
     * @return array
     */
    protected function _getHanSummary($playerId, $rounds)
    {
        return array_reduce($rounds, function ($acc, RoundPrimitive $r) use ($playerId) {
            if (($r->getOutcome() === 'ron' || $r->getOutcome() === 'tsumo') && $r->getWinnerId() == $playerId) {
                $acc = array_reduce(explode(',', $r->getHan()), function ($acc, $han) {
                    if (empty($acc[$han])) {
                        $acc[$han] = 0;
                    }
                    $acc[$han] ++;
                    return $acc;
                }, $acc);
            }
            return $acc;
        }, []);
    }

    /**
     * @param $games
     * @return RoundPrimitive[]
     */
    protected function _fetchRounds($games)
    {
        $sessionIds = array_map(function ($item) {
            /** @var $session SessionPrimitive */
            $session = $item['session'];
            return $session->getId();
        }, $games);

        return RoundPrimitive::findBySessionIds($this->_db, $sessionIds);
    }

    /**
     * @param EventPrimitive $event
     * @param PlayerPrimitive $player
     * @return array [ ['session' => SessionPrimitive, 'results' => SessionResultsPrimitive[] ] ... ]
     */
    protected function _fetchGamesHistory(EventPrimitive $event, PlayerPrimitive $player)
    {
        $sessions = SessionPrimitive::findByPlayerAndEvent($this->_db, $player->getId(), $event->getId());

        $sessionIds = array_map(function (SessionPrimitive $s) {
            return $s->getId();
        }, $sessions);

        $sessionResults = SessionResultsPrimitive::findBySessionId($this->_db, $sessionIds);
        $sessions = array_combine($sessionIds, $sessions);

        $fullResults = [];
        foreach ($sessionResults as $result) {
            if (empty($fullResults[$result->getSessionId()])) {
                $fullResults[$result->getSessionId()] = [
                    'session' => $sessions[$result->getSessionId()],
                    'results' => []
                ];
            }

            $fullResults[$result->getSessionId()]['results'][$result->getPlayerId()] = $result;
        }

        return $fullResults;
    }
}
