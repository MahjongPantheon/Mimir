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
require_once __DIR__ . '/../primitives/PlayerRegistration.php';
require_once __DIR__ . '/../primitives/PlayerEnrollment.php';
require_once __DIR__ . '/../primitives/PlayerHistory.php';
require_once __DIR__ . '/../primitives/Round.php';
require_once __DIR__ . '/../exceptions/InvalidParameters.php';

class EventModel extends Model
{
    /**
     * Get data of players' current seating
     *
     * @param $eventId
     * @throws InvalidParametersException
     * @return array TODO: should it be here? Looks a bit too low-level :/
     */
    public function getCurrentSeating($eventId)
    {
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }
        $startRating = $event[0]->getRuleset()->startRating();

        // get data from primitives, and some raw data
        $reggedPlayers = PlayerRegistrationPrimitive::findRegisteredPlayersIdsByEvent($this->_db, $eventId);
        $historyItems = PlayerHistoryPrimitive::findLastByEvent($this->_db, $eventId);
        $seatings = $this->_db->table('session_user')
            ->join('session', 'session.id = session_user.session_id')
            ->join('user', 'user.id = session_user.user_id')
            ->select('session_user.order')
            ->select('session_user.user_id')
            ->select('session_user.session_id')
            ->select('user.display_name')
            ->select('session.table_index')
            ->where('session.event_id', $eventId)
            ->orderByDesc('session.id')
            ->orderByAsc('order')
            ->limit(count($reggedPlayers))
            ->findArray();

        // merge it all together
        $ratings = [];
        foreach ($reggedPlayers as $reg) {
            $ratings[$reg] = $startRating;
        }
        foreach ($historyItems as $item) { // overwrite with real values
            if (!empty($item->getRating())) {
                $ratings[$item->getPlayerId()] = $item->getRating();
            }
        }
        
        return array_map(function ($seat) use (&$ratings) {
            $seat['rating'] = $ratings[$seat['user_id']];
            return $seat;
        }, $seatings);
    }
    
    /**
     * Find out currently playing tables state (for tournaments only)
     * @param integer $eventId
     * @return array
     */
    public function getTablesState($eventId)
    {
        $reggedPlayers = PlayerRegistrationPrimitive::findRegisteredPlayersIdsByEvent($this->_db, $eventId);
        $tablesCount = count($reggedPlayers) / 4;

        $lastGames = SessionPrimitive::findByEventAndStatus($this->_db, $eventId, ['finished', 'inprogress'], 0, $tablesCount);
        $output = [];
        foreach ($lastGames as $game) {
            /** @var RoundPrimitive $lastRound */
            $lastRound = array_reduce( // TODO: do it on db side
                RoundPrimitive::findBySessionIds($this->_db, [$game->getId()]),
                function ($acc, RoundPrimitive $r) {
                    /** @var RoundPrimitive $acc */
                    // find max id
                    return (!$acc || $r->getId() > $acc->getId()) ? $r : $acc;
                },
                null
            );

            $output []= [
                'status' => $game->getStatus(),
                'hash' => $game->getRepresentationalHash(),
                'penalties' => $game->getCurrentState()->getPenaltiesLog(),
                'table_index' => $game->getTableIndex(),
                'last_round' => $lastRound ? [
                    'outcome' => $lastRound->getOutcome(),
                    'winner'  => $lastRound->getWinnerId(),
                    'loser'   => $lastRound->getLoserId(),
                    'tempai'  => $lastRound->getTempaiIds(),
                    'riichi'  => $lastRound->getRiichiIds(),
                    'han'     => $lastRound->getHan(),
                    'fu'      => $lastRound->getFu()
                ] : [],
                'players' => array_map(function (PlayerPrimitive $p) {
                    return [
                        'id' => $p->getId(),
                        'display_name' => $p->getDisplayName()
                    ];
                }, $game->getPlayers())
            ];
        }
        
        return $output;
    }

    
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
                'penalties' => $session->getCurrentState()->getPenaltiesLog(),
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
                    if (abs($el1->getRating() - $el2->getRating()) < 0.0001) {
                        return $el2->getAvgPlace() - $el1->getAvgPlace(); // lower avg place is better, so invert
                    }
                    if ($el1->getRating() - $el2->getRating() < 0) { // higher rating is better
                        return -1;  // usort casts return result to int, so pass explicit int here.
                    } else {
                        return 1;
                    }
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

    // --------- Auth & reg related ------------

    /**
     * Enroll player to event
     *
     * @param $eventId
     * @param $playerId
     * @throws AuthFailedException
     * @throws BadActionException
     * @throws InvalidParametersException
     * @return string secret pin code
     */
    public function enrollPlayer($eventId, $playerId)
    {
        if (!$this->checkAdminToken()) {
            throw new AuthFailedException('Only administrators are allowed to enroll players to event');
        }

        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }
        $player = PlayerPrimitive::findById($this->_db, [$playerId]);
        if (empty($player)) {
            throw new InvalidParametersException('Player id#' . $playerId . ' not found in DB');
        }

        $regItem = (new PlayerEnrollmentPrimitive($this->_db))
            ->setReg($player[0], $event[0]);
        $success = $regItem->save();

        if (!$success) {
            throw new BadActionException('Something went wrong: enrollment failed while saving to db');
        }

        return $regItem->getPin();
    }

    /**
     * Register player to event
     *
     * @param $playerId
     * @param $eventId
     * @throws BadActionException
     * @throws InvalidParametersException
     * @return bool success?
     */
    public function registerPlayer($playerId, $eventId)
    {
        $player = PlayerPrimitive::findById($this->_db, [$playerId]);
        if (empty($player)) {
            throw new InvalidParametersException('Player id#' . $playerId . ' not found in DB');
        }
        $event = EventPrimitive::findById($this->_db, [$eventId]);
        if (empty($event)) {
            throw new InvalidParametersException('Event id#' . $eventId . ' not found in DB');
        }
        $regItem = (new PlayerRegistrationPrimitive($this->_db))->setReg($player[0], $event[0]);
        $success = $regItem->save();

        $eItem = PlayerEnrollmentPrimitive::findByPlayerAndEvent($this->_db, $playerId, $eventId);
        if ($success) {
            $eItem->drop();
        }
        return $success;
    }

    /**
     * Unregister player from event
     *
     * @param $playerId
     * @param $eventId
     * @throws BadActionException
     * @throws InvalidParametersException
     * @return void
     */
    public function unregisterPlayer($playerId, $eventId)
    {
        $regItem = PlayerRegistrationPrimitive::findByPlayerAndEvent($this->_db, $playerId, $eventId);
        if (empty($regItem)) {
            return;
        }

        $regItem->drop();
    }

    /**
     * Self-register player to event by pin
     *
     * @param $pin
     * @throws BadActionException
     * @return string auth token
     */
    public function registerPlayerPin($pin)
    {
        $success = false;
        $token = null;

        $eItem = PlayerEnrollmentPrimitive::findByPin($this->_db, $pin);
        if ($eItem) {
            $event = EventPrimitive::findById($this->_db, [$eItem->getEventId()]);

            if ($event[0]->getType() === 'offline_interactive_tournament') {
                $reggedItems = PlayerRegistrationPrimitive::findByPlayerAndEvent($this->_db, $eItem->getPlayerId(), $event[0]->getId());
                // check that games are not started yet
                if ($event[0]->getLastTimer() && empty($reggedItems)) {
                    // do not allow new users to enter already tournament
                    // but allow to reenroll/reenter pin for already participating people
                    throw new BadActionException('Pin is expired: game sessions are already started.');
                }
            }

            $player = PlayerPrimitive::findById($this->_db, [$eItem->getPlayerId()]);
            $regItem = (new PlayerRegistrationPrimitive($this->_db))
                ->setReg($player[0], $event[0]);
            $success = $regItem->save();
            $token = $regItem->getToken();
        }
        if (!$success || empty($regItem)) {
            throw new BadActionException('Something went wrong: registration failed while saving to db');
        }

        $eItem->drop();
        return $token;
    }

    /**
     * Checks if token is ok.
     * Reads token value from _SERVER['HTTP_X_AUTH_TOKEN']
     *
     * Also should return true to admin-level token to allow everything
     *
     * @param $playerId
     * @param $eventId
     * @return bool
     */
    public function checkToken($playerId, $eventId)
    {
        if ($this->checkAdminToken()) {
            return true;
        }

        $regItem = $this->dataFromToken();
        return $regItem
            && $regItem->getEventId() == $eventId
            && $regItem->getPlayerId() == $playerId;
    }

    /**
     * Get player and event ids by auth token
     * @return null|PlayerRegistrationPrimitive
     */
    public function dataFromToken()
    {
        $token = empty($_SERVER['HTTP_X_AUTH_TOKEN']) ? '' : $_SERVER['HTTP_X_AUTH_TOKEN'];
        return PlayerRegistrationPrimitive::findEventAndPlayerByToken($this->_db, $token);
    }

    /**
     * Check if token allows administrative operations
     * @return bool
     */
    public function checkAdminToken()
    {
        $token = empty($_SERVER['HTTP_X_AUTH_TOKEN']) ? '' : $_SERVER['HTTP_X_AUTH_TOKEN'];
        return $token === $this->_config->getValue('admin.god_token');
    }
}
