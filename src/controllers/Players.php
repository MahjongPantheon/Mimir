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
require_once __DIR__ . '/../primitives/Player.php';
require_once __DIR__ . '/../models/PlayerStat.php';
require_once __DIR__ . '/../models/Event.php';

/**
 * Class PlayersController
 * For user management.
 * Don't do any ACL here - it's expected to be managed by client app.
 *
 * @package Riichi
 */
class PlayersController extends Controller
{
    /**
     * @param string $ident oauth ident, if any
     * @param string $alias textlog alias for quicker enter
     * @param string $displayName how to display user in stats
     * @param string $tenhouId tenhou username
     * @throws MalformedPayloadException
     * @throws InvalidUserException
     * @return int user id
     */
    public function add($ident, $alias, $displayName, $tenhouId)
    {
        $this->_log->addInfo('Adding new player');
        if (empty($ident) || empty($displayName)) {
            throw new MalformedPayloadException('Fields #ident and #displayName should not be empty');
        }

        $player = (new PlayerPrimitive($this->_db))
            ->setAlias($alias)
            ->setDisplayName($displayName)
            ->setIdent($ident)
            ->setTenhouId($tenhouId);

        try {
            $player->save();
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') {
                // duplicate entry
                throw new InvalidUserException(
                    'User ident #' . $ident . ' already exists in DB'
                );
            }
            throw $e;
        }
        $this->_log->addInfo('Successfully added new player id=' . $player->getId());
        return $player->getId();
    }

    /**
     * @param int $id user to update
     * @param string $ident oauth ident, if any
     * @param string $alias textlog alias for quicker enter
     * @param string $displayName how to display user in stats
     * @param string $tenhouId tenhou username
     * @throws EntityNotFoundException
     * @throws MalformedPayloadException
     * @return int user id
     */
    public function update($id, $ident, $alias, $displayName, $tenhouId)
    {
        $this->_log->addInfo('Updating player id #' . $id);
        $player = PlayerPrimitive::findById($this->_db, [$id]);
        if (empty($player)) {
            throw new EntityNotFoundException('No user with id #' . $id . ' found');
        }

        if (empty($ident) || empty($displayName)) {
            throw new MalformedPayloadException('Fields #ident and #displayName should not be empty');
        }

        $player = $player[0]
            ->setAlias($alias)
            ->setDisplayName($displayName)
            ->setIdent($ident)
            ->setTenhouId($tenhouId);
        $player->save();

        $this->_log->addInfo('Successfully updated player id #' . $player->getId());
        return $player->getId();
    }

    /**
     * Get user info by id
     * @param int $id
     * @throws EntityNotFoundException
     * @return array
     */
    public function get($id)
    {
        $this->_log->addInfo('Fetching info of player id #' . $id);
        $player = PlayerPrimitive::findById($this->_db, [$id]);
        if (empty($player)) {
            throw new EntityNotFoundException('No user with id #' . $id . ' found');
        }

        $this->_log->addInfo('Successfully fetched info of player id #' . $id);
        return [
            'id'            => $player[0]->getId(),
            'alias'         => $player[0]->getAlias(),
            'display_name'  => $player[0]->getDisplayName(),
            'ident'         => $player[0]->getIdent(),
            'tenhou_id'     => $player[0]->getTenhouId()
        ];
    }

    /**
     * Get user info by id
     * @throws InvalidParametersException
     * @throws EntityNotFoundException
     * @return array
     */
    public function getFromToken()
    {
        $this->_log->addInfo('Getting info of player (by token)');
        $data = (new EventModel($this->_db, $this->_config))->dataFromToken();
        if (empty($data)) {
            throw new InvalidParametersException('Invalid user token');
        }
        return $this->get($data->getPlayerId());
    }


    /**
     * @param int $playerId player to get stats for
     * @param int $eventId event to get stats for
     * @throws EntityNotFoundException
     * @return array of statistics
     */
    public function getStats($playerId, $eventId)
    {
        $this->_log->addInfo('Getting stats for player id #' . $playerId . ' at event id #' . $eventId);
        $stats = (new PlayerStatModel($this->_db, $this->_config))
            ->getStats($eventId, $playerId);
        $this->_log->addInfo('Successfully got stats for player id #' . $playerId . ' at event id #' . $eventId);
        return $stats;
    }

    /**
     * @param int $playerId
     * @param int $eventId
     * @throws AuthFailedException
     * @return array of session data
     */
    public function getCurrentSessions($playerId, $eventId)
    {
        $this->_log->addInfo('Getting current sessions for player id #' . $playerId . ' at event id #' . $eventId);
        if (!(new EventModel($this->_db, $this->_config))->checkToken($playerId, $eventId)) {
            throw new AuthFailedException('Authentication failed! Ask for some assistance from admin team', 403);
        }

        $sessions = SessionPrimitive::findByPlayerAndEvent($this->_db, $playerId, $eventId, 'inprogress');

        $result = array_map(function (SessionPrimitive $session) {
            return [
                'hashcode'    => $session->getRepresentationalHash(),
                'status'      => $session->getStatus(),
                'table_index' => $session->getTableIndex(),
                'players'     => array_map(function (PlayerPrimitive $p, $score) use (&$session) {
                    return [
                        'id'            => $p->getId(),
                        'alias'         => $p->getAlias(),
                        'ident'         => $p->getIdent(),
                        'display_name'  => $p->getDisplayName(),
                        'score'         => $score
                    ];
                }, $session->getPlayers(), $session->getCurrentState()->getScores())
            ];
        }, $sessions);

        $this->_log->addInfo('Successfully got current sessions for player id #' . $playerId . ' at event id #' . $eventId);
        return $result;
    }

    /**
     * @throws AuthFailedException
     * @throws InvalidParametersException
     * @return array of session data
     */
    public function getCurrentSessionsFromToken()
    {
        $this->_log->addInfo('Getting current sessions (by token)');
        $data = (new EventModel($this->_db, $this->_config))->dataFromToken();
        if (empty($data)) {
            throw new InvalidParametersException('Invalid user token');
        }
        return $this->getCurrentSessions($data->getPlayerId(), $data->getEventId());
    }

    /**
     * Get last game results of user in event
     *
     * @param int $playerId
     * @param int $eventId
     * @throws EntityNotFoundException
     * @return array|null
     */
    public function getLastResults($playerId, $eventId)
    {
        $this->_log->addInfo('Getting last session results for player id #' . $playerId . ' at event id #' . $eventId);
        $session = SessionPrimitive::findLastByPlayerAndEvent($this->_db, $playerId, $eventId, 'finished');
        if (empty($session)) {
            return null;
        }

        $tmpResults = SessionResultsPrimitive::findByPlayersAndSession(
            $this->_db,
            $session->getId(),
            $session->getPlayersIds()
        );

        /** @var SessionResultsPrimitive[] $sessionResults */
        $sessionResults = [];
        foreach ($tmpResults as $sr) {
            $sessionResults[$sr->getPlayerId()] = $sr;
        }

        $result = array_map(function (PlayerPrimitive $p) use (&$session, &$sessionResults) {
            return [
                'id'            => $p->getId(),
                'alias'         => $p->getAlias(),
                'ident'         => $p->getIdent(),
                'display_name'  => $p->getDisplayName(),
                'score'         => $sessionResults[$p->getId()]->getScore(),
                'rating_delta'  => $sessionResults[$p->getId()]->getRatingDelta(),
            ];
        }, $session->getPlayers());

        $this->_log->addInfo('Successfully got last session results for player id #' . $playerId . ' at event id #' . $eventId);
        return $result;
    }

    /**
     * Get last game results of user in event
     *
     * @throws InvalidParametersException
     * @throws EntityNotFoundException
     * @return array|null
     */
    public function getLastResultsFromToken()
    {
        $this->_log->addInfo('Getting last session results (by token)');
        $data = (new EventModel($this->_db, $this->_config))->dataFromToken();
        if (empty($data)) {
            throw new InvalidParametersException('Invalid user token');
        }
        return $this->getLastResults($data->getPlayerId(), $data->getEventId());
    }

    /**
     * @param string $playerIdent unique identifying string
     * @throws EntityNotFoundException
     * @return int player id
     */
    public function getIdByIdent($playerIdent)
    {
        $this->_log->addInfo('Getting id for player #' . $playerIdent);
        $player = PlayerPrimitive::findByIdent($this->_db, [$playerIdent]);
        if (empty($player)) {
            throw new EntityNotFoundException('No user with ident #' . $playerIdent . ' found');
        }

        $this->_log->addInfo('Successfully got id for player #' . $playerIdent);
        return $player[0]->getId();
    }
}
